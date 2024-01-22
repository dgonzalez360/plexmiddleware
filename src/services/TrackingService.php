<?php

namespace davidcasini\craftplexintegration\services;
use davidcasini\craftplexintegration\records\TrackingInfo;

use davidcasini\craftplexintegration\PlexIntegration;

use DateTime;
use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use \craft\elements\User;
use craft\commerce\services\Transactions;
use craft\commerce\Plugin;
use craft\commerce\Plugin as Commerce;



class TrackingService extends Component
{
    public function getTrackingInfo(string $trackingNumber) : ?array
    {
        $fedexClientId = App::parseEnv(PlexIntegration::$plugin->getSettings()->middlewareFedexClientId);
        $fedexClientSecret = App::parseEnv(PlexIntegration::$plugin->getSettings()->middlewareFedexClientSecret);

        if($trackingNumber && $fedexClientId && $fedexClientSecret){
            
            $auth = (new \FedexRest\Authorization\Authorize())
            ->useProduction()
            ->setClientId($fedexClientId)
            ->setClientSecret($fedexClientSecret)
            ->authorize();
            
            $response = (new \FedexRest\Services\Track\TrackByTrackingNumberRequest())
            ->useProduction()
            ->setTrackingNumber($trackingNumber)
            ->setAccessToken($auth->access_token)
            ->request();

            return $response->output->completeTrackResults[0]->trackResults[0] ? (array) $response->output->completeTrackResults[0]->trackResults[0] : [];

        }

        return [];
    }

    public function getEstimatedArrival(string $trackingNumber) : ?string 
    {
        if($trackingNumber){
            $trackingInfo = $this->getStoredTrackingInfo($trackingNumber);
            if($trackingInfo && isset($trackingInfo[0])){
                $dateTime = new DateTime($trackingInfo[0]['estimated_delivery']);
                return $dateTime->format('F jS');
            }
            return 'Not Available';

        }
        return 'Not Available';
    }

    public function getEstimatedArrivalFromFedex(string $trackingNumber) : ?string 
    {
        if($trackingNumber){
            $trackingInfo = $this->getTrackingInfo($trackingNumber);
            if($trackingInfo){
                if($trackingInfo['dateAndTimes'][0]->dateTime){
                    $dateTime = new DateTime($trackingInfo['dateAndTimes'][0]->dateTime);
                    return $dateTime->format('F jS');
                }
                return 'Not available';
            } 
        }
        return 'Not Available';
    }

    public function saveTrackingInfo(array $data){
        
        $record = new TrackingInfo();
        $record->tracking_number = $data['tracking_number'];
        $record->carrier = $data['carrier'];
        $record->estimated_delivery = $data['estimated_delivery'];
        return $record->save();

    }

    public function getStoredTrackingInfo(string $trackingNumber): ?array
    {
        return (new Query())
            ->select([
                'carrier',
                'estimated_delivery',
            ])
            ->from(['{{%plexshipping_info}}'])
            ->where(['tracking_number' => $trackingNumber])
            ->all();
    }
    
}