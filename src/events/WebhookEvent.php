<?php

namespace davidcasini\craftplexintegration\events;

use davidcasini\craftplexintegration\records\PlexWebhookCall;
use yii\base\Event;

class WebhookEvent extends Event
{
    /** @var PlexWebhookCall */
    public $model;
}