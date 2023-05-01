<?php

namespace davidcasini\craftplexintegration;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use davidcasini\craftplexintegration\models\Settings;
use davidcasini\craftplexintegration\services\Service;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

use craft\fields\PlainText;
use craft\fields\Table;
use craft\models\FieldGroup;

use craft\events\PluginEvent;
use craft\services\Plugins;

/**
 * plex_integration plugin
 *
 * @method static PlexIntegration getInstance()
 * @method Settings getSettings()
 * @author David Casini <davidg@mwcompany.com>
 * @copyright David Casini
 * @license https://craftcms.github.io/license/ Craft License
 */
class PlexIntegration extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public static $plugin;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'service' => Service::class
        ]);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('plex-integration/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['middleware/webhook'] = 'plex-integration/default';
            }
        );

        // Register after plugin install listener
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    $this->onAfterInstall();
                }
            }
        );
    }

    public function onAfterInstall()
    {
        if (Craft::$app->config->env === 'test') {
            return;
        }
        
        $commerce = Plugin::getInstance();

        // Create an "Middleware field group"
        $group = new FieldGroup();
        $group->name = 'Middleware';

        if( Craft::$app->fields->saveGroup($group) )
        {
            Craft::info('Middleware field group created successfully.', 'PlexIntegration');

            $field = Craft::$app->fields->createField([
                'groupId'        => $group->id,
                'name'           => 'Plex ID',
                'handle'         => 'plexId',
                'type'           => Table::class,
                'instructions'   => '',
                'columnType'   => 'text',
                'settings' => [
                    'columns' => [
                        'col1' => [
                            'heading' => '',
                            'handle' => '',
                            'type' => 'singleline',
                        ]
                    ],
                    'defaults'   => [
                        'col1' => ''
                    ],
                ]
            ]);

            if (Craft::$app->fields->saveField($field))
            {
                Craft::info('plexId field created successfully.', 'PlexIntegration');
            }
            else
            {
                Craft::warning('Could not save the plexId field.', 'PlexIntegration');
            }

            $field = Craft::$app->fields->createField([
                'groupId'        => $group->id,
                'name'           => 'Plex Middleware UUID',
                'handle'         => 'plexmiddlewareid',
                'type'           => PlainText::class,
                'instructions'   => '',
                'settings' => [
                    'placeholder' => '',
                    'multiline'   => false,
                    'initialRows' => '4',
                    'charLimit'   => null,
                ]
            ]);

            if (Craft::$app->fields->saveField($field))
            {
                Craft::info('Plex Middleware UUID field created successfully.', 'PlexIntegration');
            }
            else
            {
                Craft::warning('Could not save the Plex Middleware UUID field.', 'PlexIntegration');
            }
        }
        else
        {
            Craft::warning('Could not save the Middleware field group. ', 'PlexIntegration');
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

    }
}
