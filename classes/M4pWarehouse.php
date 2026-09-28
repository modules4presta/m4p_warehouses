<?php

declare(strict_types=1);

/**
 * m4p_warehouses
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class M4pWarehouse extends ObjectModel
{
    /** @var int */
    public $id_warehouse;

    /** @var string */
    public $name;

    /** @var string */
    public $delivery_time = '';

    /** @var bool */
    public $active = true;

    /** @var int */
    public $position = 0;

    public static $definition = [
        'table' => 'm4p_warehouse',
        'primary' => 'id_warehouse',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'delivery_time' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'm4p_warehouse`'
            . ($activeOnly ? ' WHERE `active` = 1' : '')
            . ' ORDER BY `position` ASC, `id_warehouse` ASC';

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }
}
