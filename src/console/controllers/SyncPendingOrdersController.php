<?php

namespace davidcasini\craftplexintegration\console\controllers;

use Craft;
use craft\console\Controller;
use yii\console\ExitCode;
use \craft\commerce\elements\Order;
use craft\commerce\models\Discount;
use \craft\elements\Address;
use \craft\elements\User;
use craft\commerce\services\Transactions;
use craft\commerce\Plugin;
use GuzzleHttp\Client;

use craft\commerce\models\OrderStatus;
use davidcasini\craftplexintegration\PlexIntegration;
use craft\helpers\App;

/**
 * Sync Pending Orders controller
 */
class SyncPendingOrdersController extends Controller
{
    public $defaultAction = 'index';

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
        }
        return $options;
    }

    /**
     * plex-integration/sync-pending-orders command
     */
    public function actionIndex(): int
    {
        $orders = Order::find()->orderStatusId(1)->all();
        //$orders = Order::find()->id(235821)->all();
        foreach ($orders as $order) {
            $customer = User::find()->id($order->customerId)->one();
            $shippingAddress = Address::find()->id($order->shippingAddressId)->one();
            $billingAddress = Address::find()->id($order->billingAddressId)->one();

            $plexOrder = [
                'customer' => [
                    'email' => $customer->email,
                    'firstName' => $customer->firstName,
                    'lastName' => $customer->lastName
                ],
                'deliveryAddress' => [
                    'addressInternalId' => $shippingAddress->id,
					'customerName' => $shippingAddress->fullName,
                    'address' => $shippingAddress->addressLine1,
                    'address2' => $shippingAddress->addressLine2,
					'city' => $shippingAddress->locality,
					'state' => ($shippingAddress->administrativeArea ? $shippingAddress->administrativeArea : ''),
					'zipCode' => $shippingAddress->postalCode,
					'country' => $shippingAddress->countryCode == 'US' ? 'USA' : '',
					'phone' => $shippingAddress->getFieldValue('phoneNumber')
                ],
                'billingAddress' => [
                    'addressInternalId' => $billingAddress->id,
                    'customerName' => $billingAddress->fullName,
                    'address' => $billingAddress->addressLine1,
                    'address2' => $billingAddress->addressLine2,
					'city' => $billingAddress->locality,
					'state' => ($billingAddress->administrativeArea ? $billingAddress->administrativeArea : ''),
					'zipCode' => $billingAddress->postalCode,
					'country' => $billingAddress->countryCode == 'US' ? 'USA' : '',
					'phone' => $billingAddress->getFieldValue('phoneNumber')
                ],
                'order' => [
                    'reference' => $order->reference,
                    'total' => (float) number_format($order->storedTotalPaid, 2, '.', ''),
                    'taxTotal' => (float) number_format($order->storedTotalTax, 2, '.', ''),
                    'shippingTotal' => (float) number_format($order->storedTotalShippingCost, 2, '.', ''),
                    'discountAmount' => (float) number_format($order->storedTotalDiscount, 2, '.', ''),
                    'paymentMethod' => $this->getPaymentMethod($order)['method'],
                    'paymentId' => $this->getPaymentMethod($order)['id'],
                    'shippingMethod' => $order->shippingMethodHandle ?? 'FEDEX_GROUND' ,
                    'products' => $this->getProducts($order),
                    'discounts' => $this->getDiscounts($order)
                ]
            ];

            try {
                $middlewareUser = App::parseEnv(PlexIntegration::$plugin->getSettings()->middlewareUser);
                $middlewarePass = App::parseEnv(PlexIntegration::$plugin->getSettings()->middlewarePass);
                $middlewareUrl = App::parseEnv(PlexIntegration::$plugin->getSettings()->middlewareUrl);

                $client = new Client([
                    'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Basic '.base64_encode($middlewareUser.":".$middlewarePass) ]
                ]);

                $response = $client->post($middlewareUrl,
                    ['body' => json_encode($plexOrder)]
                );
                
                $result = json_decode($response->getBody()->getContents());

                if($result && $result->status == 'processed'){
                    $order->orderStatusId = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle('syncedWithMiddleware')->id;
                }else{
                    $order->orderStatusId = Plugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle('syncError')->id;
                }

                $order->setFieldValue('plexmiddlewareid', $result->result->orderId);
                Craft::$app->getElements()->saveElement($order, false);
            } catch (\Throwable $th) {
                throw $th;
            }
        }
        return ExitCode::OK;
    }

    public function getDiscounts($order){
        return collect($order->getAdjustments())->filter(function ($v) { return $v->type == 'discount'; })->values()->map(function($v) {
            return [
                'code' => $v->name,
                'amount' => $v->amount
            ];
        })->toArray();
    }

    public function getPaymentMethod($order){
        //$paymentReference = Plugin::getInstance()->getTransactions()->getAllTransactionsByOrderId($order);
        $paymentReference = $order->getLastTransaction();
		if($paymentReference){
            $gateway = Plugin::getInstance()->getGateways()->getGatewayById($paymentReference->gatewayId);
			return ['method' => $gateway->name, 'id' => $paymentReference->reference];
        }
        return ['method' => 'N/A', 'id' => 'N/A'];
    }

    public function getProducts($order){
        $products = $order->getLineItems();
        $line = [];
        if($products){
            foreach ($products as $product) {
                if($order->storedItemTotal != 0)
                    $productPrice = ((float) number_format(($product['salePrice'] - (($product['salePrice'])/$order->storedItemSubtotal) * abs($order->storedTotalDiscount)), 2, '.', ''));
                else
                    $productPrice = 0;
                $line[]=[
                    'partNumber' => $product['sku'],
                    'price' => $productPrice,
                    'quantity' => $product['qty']
                ];
            }
        }
        return $line;
    }
}
