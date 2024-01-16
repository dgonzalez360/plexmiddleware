<?php

namespace davidcasini\craftplexintegration\behaviors;
use davidcasini\craftplexintegration\services\TrackingService;

use Craft;
use craft\commerce\elements\Order;
use yii\base\Behavior;

class OrderTrackingBehavior extends Behavior
{
    private ?string $_tracking = '';

    public function getEstimatedArrival(string $trackingNumber = ''): ?string
    {
        $trackingService = new TrackingService();
        $estimated = $trackingService->getEstimatedArrival($trackingNumber);
        return $estimated;
    }
}
