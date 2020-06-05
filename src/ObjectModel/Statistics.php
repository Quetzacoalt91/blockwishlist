<?php

namespace PrestaShop\Module\BlockWishList\ObjectModel;

class Statistics extends \ObjectModel
{
    /** @var int ID */
    public $id;

    /** @var int module_ID */
    public $module_ID;

    /** @var bool is_adding_product */
    public $is_adding_product = false;

    /** @var bool is_removing_product */
    public $is_removing_product = false;

    /** @var datetime created_at */
    public $created_at;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'wishlist_statistics',
        'primary' => 'id',
        'fields' => [
            'module_ID' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'is_adding_product' => ['type' => self::TYPE_BOOL, 'validate' => 'isBoolean', 'required' => true],
            'is_removing_product' => ['type' => self::TYPE_BOOL, 'validate' => 'isBoolean', 'required' => true],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateTime', 'required' => true],
        ],
    ];

    public function __construct() {
        $this->createdAt = new DateTime('now');
    }
}
