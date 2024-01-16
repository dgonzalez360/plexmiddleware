<?php

namespace davidcasini\craftplexintegration\services;

use davidcasini\craftplexintegration\PlexIntegration;
use davidcasini\craftplexintegration\services\TrackingService;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use \craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use tasdev\orderfulfillments\OrderFulfillments;
use tasdev\orderfulfillments\models\Fulfillment;
use \craft\elements\Address;
use \craft\elements\User;
use craft\commerce\services\Transactions;
use craft\commerce\Plugin;
use craft\commerce\Plugin as Commerce;

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
                $failedSync = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle('syncErrorPlex')->id;
                $successfulSync = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle('syncedWithPlex')->id;
                $order->orderStatusId = ($payload['status'] == 'synced' ? $successfulSync : $failedSync);
                
                Craft::$app->getElements()->saveElement($order, false); 
                return true;
            }
        }
        return false;
    }

    public function updateShipping(array $payload): bool
    {
        if($payload['id']){
            $order = Order::find()->shortNumber($payload['id'])->one();
            if($order){
                $partial = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle('partiallyFulfilled')->id;
                $shipped = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle('fulfilled')->id;
                $products = $payload['products'];

                $fulfillmentLinesService = OrderFulfillments::getInstance()->getFulfillmentLines();
                $lineItems = $order->getLineItems();

                foreach ($products as $sku => $shippmentData) {
                    foreach($lineItems as $li){
                        if($li['sku'] == $sku){
                            
                            $fulfillment = OrderFulfillments::getInstance()->getFulfillments()->createFulfillment($order->id);
                            $fulfillment->trackingNumber = $shippmentData['tracking'][0];
                            $fulfillment->trackingCarrierClass = 'tasdev\orderfulfillments\carriers\FedEx';

                            $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($li->id);

                            $fulfillmentLine = $fulfillmentLinesService->createFulfillmentLine($lineItem, intval($shippmentData['quantity']));
                            $fulfillment->addFulfillmentLine($fulfillmentLine);
                            $fulfillment->validate();
                            OrderFulfillments::getInstance()->getFulfillments()->saveFulfillment($fulfillment, false);
                            
                            $trackingService = new TrackingService();
                            $trackingService->saveTrackingInfo([
                                'tracking_number' => $shippmentData['tracking'][0],
                                'carrier' => $shippmentData['carrier'],
                                'estimated_delivery' => $shippmentData['estimated_delivery']
                            ]);
                            
                        }
                    }
                }
            }
        }
        return true;
    }
}