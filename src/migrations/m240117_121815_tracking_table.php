<?php

namespace davidcasini\craftplexintegration\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

use craft\commerce\models\OrderStatus;
use craft\commerce\Plugin as Commerce;

/**
 * m240117_121815_tracking_table migration.
 */
class m240117_121815_tracking_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $shippingSchema = Craft::$app->db->schema->getTableSchema('{{%plexshipping_info}}');
        if ($shippingSchema === null) {
            $this->createTable(
                '{{%plexshipping_info}}',
                [
                    'id'                => $this->primaryKey(),
                    'tracking_number'   => $this->string(255),
                    'carrier'           => $this->string(255),
                    'estimated_delivery'=> $this->string(255),
                ]
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240117_121815_tracking_table cannot be reverted.\n";
        return false;
    }
}
