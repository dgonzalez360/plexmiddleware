<?php

namespace davidcasini\craftplexintegration\services;

use davidcasini\craftplexintegration\PlexIntegration;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use \craft\commerce\elements\Order;
use \craft\elements\Address;
use \craft\elements\User;
use craft\commerce\services\Transactions;
use craft\commerce\Plugin;

class Service extends Component
{
    public function updateOrder(array $payload): bool
    {
        if($payload['id']){
            $order = Order::find()->shortNumber($payload['id'])->one();
            if($order){
                // Order Synced we update with the new status
                if(is_array($payload['plex_orders'])){
                    $records = [];
                    foreach ($payload['plex_orders'] as $plexOrder) {
                       $records[] = ['col1' => $plexOrder];
                    }
                    $order->setFieldValues(['plexId' => $records]);
                }
                $order->orderStatusId = ($payload['status'] == 'synced' ? 6 : 7);
                
                Craft::$app->getElements()->saveElement($order, false); 
                return true;
            }
        }
        return false;
    }
}