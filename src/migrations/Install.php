<?php


namespace davidcasini\craftplexintegration\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

use craft\commerce\models\OrderStatus;
use craft\commerce\Plugin as Commerce;


class Install extends Migration
{
    public $driver;

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
            $this->createMissingOrderStatuses();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%plexwebhooks_plexwebhookcall}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%plexwebhooks_plexwebhookcall}}',
                [
                    'id'          => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid'         => $this->uid(),
                    'siteId'      => $this->integer()->notNull(),
                    'type'        => $this->string(255),
                    'payload'     => $this->text(),
                    'exception'   => $this->text(),
                ]
            );
        }

        return $tablesCreated;
    }

    protected function createIndexes()
    {
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%plexwebhooks_plexwebhookcall}}', 'siteId'),
            '{{%plexwebhooks_plexwebhookcall}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    protected function insertDefaultData()
    {
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%plexwebhooks_plexwebhookcall}}');
    }

    protected function createMissingOrderStatuses(){
        $statuses = [
            'syncedWithMiddleware' => ['Synced with Middleware','syncedWithMiddleware','green','Order sent to the middleware for processing'],
            'syncError' => ['Sync failed','syncError','red','Order failed to sync'],
            'syncedWithPlex' => ['Synced with Plex','syncedWithPlex','green','Order sent to Plex'],
            'syncErrorPlex' => ['Sync Error Plex','syncErrorPlex','red','Order failed to sync to Plex']
        ];

        foreach ($statuses as $handle => $cStatus) {
            $currentStatuses = Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses();
            if(!Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($handle)){
                echo "Order handle".$handle. "does not exist";
                $status = new OrderStatus();
                $status->name = $cStatus[0];
                $status->handle = $cStatus[1];
                $status->color = $cStatus[2];
                $status->description = $cStatus[3];

                if (Commerce::getInstance()->getOrderStatuses()->saveOrderStatus($status)) {
                    echo 'Order status created successfully.';
                } else {
                    return false;
                }
            }  
        }
        return true;
    }
}