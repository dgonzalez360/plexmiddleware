<?php


namespace davidcasini\craftplexintegration\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $tracking_number
 * @property string $carrier
 * @property string $estimated_delivery
 */

class TrackingInfo extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%plexshipping_info}}';
    }
}
