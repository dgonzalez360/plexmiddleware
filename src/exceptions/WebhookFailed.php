<?php

namespace davidcasini\craftplexintegration\exceptions;

use Exception;
use davidcasini\craftplexintegration\records\PlexWebhookCall;

class WebhookFailed extends Exception
{
    public static function missingSignature()
    {
        return new static('The request did not contain a header named `Middleware-Signature`.');
    }

    public static function invalidSignature($signature)
    {
        return new static("The signature `{$signature}` found in the header named `Middleware-Signature` is invalid.");
    }

    public static function signingSecretNotSet()
    {
        return new static('The Plex webhook signing secret is not set.');
    }

    public static function jobClassDoesNotExist(string $jobClass, PlexWebhookCall $webhookCall)
    {
        return new static("Could not process webhook id `{$webhookCall->id}` of type `{$webhookCall->type} because the configured jobclass `$jobClass` does not exist.");
    }

    public static function missingType(PlexWebhookCall $webhookCall)
    {
        return new static("Webhook call id `{$webhookCall->id}` did not contain a type.");
    }

    public function render($request)
    {
        return response(['error' => $this->getMessage()], 400);
    }
}