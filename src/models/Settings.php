<?php

namespace davidcasini\craftplexintegration\models;

use craft\base\Model;
use davidcasini\craftplexintegration\records\PlexWebhookCall;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public string $middlewareUser = '';
    public string $middlewarePass = '';
    public string $signingSecret = '';
    public string $middlewareUrl = '';
    public string $middlewareFedexClientId = '';
    public string $middlewareFedexClientSecret = '';

    /** @var array */
    public $jobs = [
        'update_plexorder',
        'update_shippinginfo'
    ];

    /** @var string */
    public $model = PlexWebhookCall::class;

    /** @var string */
    public $endpoint = 'plexmiddleware-webhooks';


    public function rules(): array
    {
        return [
            [['middlewareUser', 'middlewarePass', 'middlewareUrl', 'signingSecret', 'middlewareFedexClientId', 'middlewareFedexClientSecret'], 'required'],
        ];
    }

    public function getMiddlewareUser(bool $parse = true): string
    {
        return $parse ? App::parseEnv($this->middlewareUser) : $this->middlewareUser;
    }

    public function getMiddlewarePass(bool $parse = true): string
    {
        return $parse ? App::parseEnv($this->middlewarePass) : $this->middlewarePass;
    }

    public function getSigningSecret(bool $parse = true): string
    {
        return $parse ? App::parseEnv($this->signingSecret) : $this->signingSecret;
    }

    public function getMiddlewareUrl(bool $parse = true): string
    {
        return $parse ? App::parseEnv($this->middlewareUrl) : $this->middlewareUrl;
    }

    public function getMiddlewareFedexClientId(bool $parse = true): string
    {
        return $parse ? App::parseEnv($this->middlewareFedexClientId) : $this->middlewareFedexClientId;
    }

    public function getMiddlewareFedexClientSecret(bool $parse = true): string
    {
        return $parse ? App::parseEnv($this->middlewareFedexClientSecret) : $this->middlewareFedexClientSecret;
    }
    

    public function getRedirectUrl(): string
    {
        return UrlHelper::siteUrl($this->endpoint);
    }
}