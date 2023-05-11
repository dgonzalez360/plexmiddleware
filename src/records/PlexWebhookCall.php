<?php


namespace davidcasini\craftplexintegration\records;

use Craft;
use craft\db\ActiveRecord;
use davidcasini\craftplexintegration\events\WebhookEvent;
use davidcasini\craftplexintegration\exceptions\WebhookFailed;
use davidcasini\craftplexintegration\PlexIntegration;
use davidcasini\craftplexintegration\services\Service;


class PlexWebhookCall extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%plexwebhooks_plexwebhookcall}}';
    }

    public function getPayload()
    {
        return json_decode($this->payload, true);
    }

    public function getException()
    {
        return json_decode($this->exception);
    }

    public function process()
    {
        $this->clearException();

        if ($this->type === '') {
            throw WebhookFailed::missingType($this);
        }

        $jobClass = $this->determineJobClass($this->type);
        
        if ($jobClass === '') {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this);
            return;
        }

        $service = new Service();

        switch ($this->type) {
            case 'update_plexorder':
                $update = $service->updateOrder($this->getPayload());
                break;

            case 'update_shippinginfo':
                $update = $service->updateShipping($this->getPayload());
                break;
            
            default:
                throw WebhookFailed::jobClassDoesNotExist($jobClass, $this);
                $update = '';
                break;
        }
        // Type exist, we execute. 
        
        return $update;

    }

    public function saveException(Exception $exception)
    {
        $this->exception = json_encode([
            'code'    => $exception->getCode(),
            'message' => $exception->getMessage(),
            'trace'   => $exception->getTraceAsString(),
        ]);

        $this->save();

        return $this;
    }

    protected function determineJobClass(string $eventType): string
    {
        $availableMethods = [
            'update_plexorder',
            'update_shippinginfo'
        ];
        return in_array($eventType, $availableMethods) ?? '';
    }

    protected function clearException()
    {
        $this->exception = null;
        $this->save();

        return $this;
    }
}