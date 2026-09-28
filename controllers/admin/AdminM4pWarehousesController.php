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

class AdminM4pWarehousesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'm4p_warehouse';
        $this->className = 'M4pWarehouse';
        $this->identifier = 'id_warehouse';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = [
            'id_warehouse' => [
                'title' => $this->trans('ID', [], 'Modules.M4pwarehouses.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Name', [], 'Modules.M4pwarehouses.Admin'),
            ],
            'delivery_time' => [
                'title' => $this->trans('Delivery time', [], 'Modules.M4pwarehouses.Admin'),
            ],
            'total_units' => [
                'title' => $this->trans('Total stock', [], 'Modules.M4pwarehouses.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'badge_success' => true,
                'search' => false,
                'havingFilter' => false,
                'orderby' => false,
            ],
            'active' => [
                'title' => $this->trans('Enabled', [], 'Modules.M4pwarehouses.Admin'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ],
        ];

        // Correlated subquery: total pieces across every product and combination of the warehouse.
        $this->_select = '(
            SELECT COALESCE(SUM(s.`quantity`), 0)
            FROM `' . _DB_PREFIX_ . 'm4p_warehouse_stock` s
            WHERE s.`id_warehouse` = a.`id_warehouse`
        ) AS `total_units`';

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Modules.M4pwarehouses.Admin'),
                'confirm' => $this->trans('Delete the selected warehouses?', [], 'Modules.M4pwarehouses.Admin'),
            ],
        ];
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Warehouse', [], 'Modules.M4pwarehouses.Admin'),
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Name', [], 'Modules.M4pwarehouses.Admin'),
                    'name' => 'name',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Delivery time', [], 'Modules.M4pwarehouses.Admin'),
                    'name' => 'delivery_time',
                    'desc' => $this->trans('For example "2-3 working days" — shown to the customer on the product page', [], 'Modules.M4pwarehouses.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Enabled', [], 'Modules.M4pwarehouses.Admin'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.M4pwarehouses.Admin')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.M4pwarehouses.Admin')],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Modules.M4pwarehouses.Admin'),
            ],
        ];

        return parent::renderForm();
    }

    /**
     * AJAX: saves the stock grid from the product page.
     * Body: id_product=INT, stock=JSON { id_warehouse: { id_product_attribute: qty } }
     */
    public function ajaxProcessSaveProductStock(): void
    {
        $idProduct = (int) Tools::getValue('id_product');
        $stock = json_decode((string) Tools::getValue('stock'), true);

        if (!$idProduct || !is_array($stock)) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Bad data']));
        }

        $affectedAttributes = [];

        foreach ($stock as $idWarehouse => $byAttribute) {
            $idWarehouse = (int) $idWarehouse;
            if (!$idWarehouse || !is_array($byAttribute)) {
                continue;
            }

            foreach ($byAttribute as $idAttribute => $quantity) {
                $idAttribute = (int) $idAttribute;
                $quantity = (int) $quantity;

                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'm4p_warehouse_stock`
                        (`id_warehouse`, `id_product`, `id_product_attribute`, `quantity`)
                     VALUES (' . $idWarehouse . ', ' . $idProduct . ', ' . $idAttribute . ', ' . $quantity . ')
                     ON DUPLICATE KEY UPDATE `quantity` = ' . $quantity
                );

                $affectedAttributes[$idAttribute] = true;
            }
        }

        // The sum per combination becomes the quantity PrestaShop sells.
        foreach (array_keys($affectedAttributes) as $idAttribute) {
            $sum = (int) Db::getInstance()->getValue(
                'SELECT COALESCE(SUM(`quantity`), 0)
                 FROM `' . _DB_PREFIX_ . 'm4p_warehouse_stock`
                 WHERE `id_product` = ' . $idProduct . '
                   AND `id_product_attribute` = ' . (int) $idAttribute
            );

            StockAvailable::setQuantity($idProduct, (int) $idAttribute, $sum);
        }

        $this->ajaxDie(json_encode(['success' => true]));
    }
}
