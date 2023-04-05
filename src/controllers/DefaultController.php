<?php

namespace davidcasini\craftplexintegration\controllers;

use Craft;
use craft\web\Controller;
use davidcasini\craftplexintegration\exceptions\WebhookFailed;
use davidcasini\craftplexintegration\PlexIntegration;
use craft\web\Response;
use craft\web\View;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\JsonResponseFormatter;

class DefaultController extends Controller
{

    protected array|bool|int $allowAnonymous = ['index'];

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config = []);
        $this->enableCsrfValidation = false;
    }

    public function actionIndex()
    {
        //$this->requirePostRequest();
        $this->verifySignature();

        $eventPayload = json_decode(Craft::$app->getRequest()->getRawBody());
        $modelClass = PlexIntegration::$plugin->settings->model;

        $plexWebhookCall = new $modelClass([
            'siteId'  => Craft::$app->getSites()->getCurrentSite()->id,
            'type'    => $eventPayload->type ?? '',
            'payload' => json_encode($eventPayload),
        ]);
        $plexWebhookCall->save(false);

        try {
            $response = $plexWebhookCall->process();

            $formatter = new JsonResponseFormatter([
                'contentType' => 'application/feed+json',
                'useJsonp' => false,
                'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                'prettyPrint' => true,
            ]);

            $this->response->data = [
                'success' => $response,
            ];

            $formatter->format($this->response);
            $this->response->data = null;
            $this->response->format = Response::FORMAT_RAW;
            $this->response->setStatusCode(($response ? 200 : 400));
            return $this->response;

        } catch (Exception $exception) {
            $plexWebhookCall->saveException($exception);

            throw $exception;
        }
    }

    protected function verifySignature()
    {
        $signature = Craft::$app->getRequest()->getHeaders()->get('Middleware-Signature');
        $secret = PlexIntegration::$plugin->getSettings()->signingSecret;
        $payload = Craft::$app->getRequest()->getRawBody();

        if (!$signature) {
            throw WebhookFailed::missingSignature();
        }

        try {
            // Eval the request with the signature
            if($signature == $secret){
                return true;
            }
            else{
                throw WebhookFailed::invalidSignature($signature);
            }

        } catch (Exception $exception) {
            throw WebhookFailed::invalidSignature($signature);
        }

        if (empty($secret)) {
            throw WebhookFailed::signingSecretNotSet();
        }

        return true;
    }
}