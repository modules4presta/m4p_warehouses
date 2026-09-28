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

require_once __DIR__ . '/classes/M4pWarehouse.php';

class M4p_Warehouses extends Module
{
    public function __construct()
    {
        $this->name = 'm4p_warehouses';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Warehouses and stock', [], 'Modules.M4pwarehouses.Admin');
        $this->description = $this->trans('Several warehouses with their own stock per combination and a delivery time shown to the customer.', [], 'Modules.M4pwarehouses.Admin');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->installDb()
            && $this->installTab()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionObjectProductDeleteAfter')
            && $this->registerHook('actionValidateOrder');
    }

    public function uninstall(): bool
    {
        return $this->uninstallTab()
            && $this->uninstallDb()
            && parent::uninstall();
    }

    protected function installDb(): bool
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4p_warehouse` (
                `id_warehouse` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `delivery_time` VARCHAR(255) NOT NULL DEFAULT \'\',
                `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `position` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_warehouse`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'm4p_warehouse_stock` (
                `id_warehouse` INT UNSIGNED NOT NULL,
                `id_product` INT UNSIGNED NOT NULL,
                `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
                `quantity` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_warehouse`, `id_product`, `id_product_attribute`),
                KEY `product` (`id_product`, `id_product_attribute`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;',
        ];

        foreach ($queries as $sql) {
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }

        return true;
    }

    protected function uninstallDb(): bool
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4p_warehouse_stock`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'm4p_warehouse`');

        return true;
    }

    protected function installTab(): bool
    {
        if (Tab::getIdFromClassName('AdminM4pWarehouses')) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = 'AdminM4pWarehouses';
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->icon = 'store';
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Magazyny (M4P)';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab(): bool
    {
        $idTab = (int) Tab::getIdFromClassName('AdminM4pWarehouses');
        if ($idTab) {
            $tab = new Tab($idTab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * Back office panel on the product page: a grid of warehouses by combinations.
     *
     * @param array<string, mixed> $params
     */
    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $idProduct = (int) ($params['id_product'] ?? Tools::getValue('id_product'));
        if (!$idProduct) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $product = new Product($idProduct, false, $idLang);

        $this->context->smarty->assign([
            'm4p_warehouses' => M4pWarehouse::getAll(),
            'm4p_combinations' => $this->getProductCombinations($product, $idLang),
            'm4p_stock' => $this->getProductStockMap($idProduct),
            'm4p_id_product' => $idProduct,
            'm4p_ajax_url' => $this->context->link->getAdminLink('AdminM4pWarehouses'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product-extra.tpl');
    }

    /**
     * Front office: availability per warehouse with its delivery time, plus the total.
     *
     * @param array<string, mixed> $params
     */
    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        $product = $params['product'] ?? null;
        $idProduct = (int) (is_array($product) ? ($product['id_product'] ?? 0) : (is_object($product) ? $product->id : 0));
        if (!$idProduct) {
            return '';
        }

        $warehouses = M4pWarehouse::getAll(true);
        if (!$warehouses) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $product = new Product($idProduct, false, $idLang);
        $combinations = $this->getProductCombinations($product, $idLang);
        $stockMap = $this->getProductStockMap($idProduct);

        // A product nobody has assigned to a warehouse yet would be shown as zero
        // everywhere, contradicting the quantity the shop is actually selling.
        if (!$stockMap) {
            return '';
        }

        $current = (int) (is_array($params['product'] ?? null) ? ($params['product']['id_product_attribute'] ?? 0) : 0);
        if (!$current) {
            $current = (int) Product::getDefaultAttribute($idProduct);
        }

        // Active reservations from m4p_reservations reduce what is shown, the same way
        // they reduce ps_stock_available. That module does not have to be installed.
        $reserved = $this->getReservedByAttribute($idProduct);

        // The list for the combination currently selected.
        $list = [];
        $remainingReserved = (int) ($reserved[$current] ?? 0);
        foreach ($warehouses as $w) {
            $idWh = (int) $w['id_warehouse'];
            $qty = (int) ($stockMap[$idWh][$current] ?? $stockMap[$idWh][0] ?? 0);
            if ($remainingReserved > 0 && $qty > 0) {
                $cut = min($qty, $remainingReserved);
                $qty -= $cut;
                $remainingReserved -= $cut;
            }
            $list[] = [
                'id_warehouse' => $idWh,
                'name' => $w['name'],
                'delivery_time' => $w['delivery_time'],
                'qty' => $qty,
            ];
        }

        // Map handed to the JS, which swaps the list when the combination changes.
        $map = [];
        foreach ($combinations as $combo) {
            $aid = (int) $combo['id'];
            $entry = ['total' => 0, 'wh' => []];
            $remainingForCombo = (int) ($reserved[$aid] ?? 0);
            foreach ($warehouses as $w) {
                $idWh = (int) $w['id_warehouse'];
                $qty = (int) ($stockMap[$idWh][$aid] ?? 0);
                if ($remainingForCombo > 0 && $qty > 0) {
                    $cut = min($qty, $remainingForCombo);
                    $qty -= $cut;
                    $remainingForCombo -= $cut;
                }
                $entry['wh'][$idWh] = $qty;
                $entry['total'] += $qty;
            }
            $map[$aid] = $entry;
        }

        $this->context->smarty->assign([
            'm4p_list' => $list,
            'm4p_total' => array_sum(array_column($list, 'qty')),
            'm4p_map_json' => json_encode($map),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product-info.tpl');
    }

    /**
     * Active reservations of a product per combination, from m4p_reservations.
     * Zwraca pusta mape, gdy modul nie jest zainstalowany/wlaczony.
     *
     * @return array<int, int> [id_product_attribute => reserved_quantity]
     */
    private function getReservedByAttribute(int $idProduct): array
    {
        $file = _PS_MODULE_DIR_ . 'm4p_reservations/m4p_reservations.php';
        if (!is_file($file) || !Module::isEnabled('m4p_reservations')) {
            return [];
        }

        require_once $file;
        if (!method_exists('M4p_Reservations', 'getReservedByAttribute')) {
            return [];
        }

        return M4p_Reservations::getReservedByAttribute($idProduct);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (($this->context->controller->php_self ?? '') !== 'product') {
            return;
        }

        $this->context->controller->registerStylesheet(
            'm4p-warehouses',
            'modules/' . $this->name . '/views/css/front.css'
        );
        $this->context->controller->registerJavascript(
            'm4p-warehouses',
            'modules/' . $this->name . '/views/js/front.js',
            ['position' => 'bottom', 'priority' => 200]
        );
    }

    /**
     * Removes the warehouse rows of a deleted product.
     *
     * @param array<string, mixed> $params
     */
    /**
     * Takes the ordered quantity off the warehouses, fullest first, so the
     * warehouse figures stay true after a sale. PrestaShop has already reduced
     * its own stock at this point, so that is deliberately left alone.
     */
    public function hookActionValidateOrder(array $params): void
    {
        $order = $params['order'] ?? null;
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        foreach ($order->getProducts() as $line) {
            $this->deductFromWarehouses(
                (int) $line['product_id'],
                (int) $line['product_attribute_id'],
                (int) $line['product_quantity']
            );
        }
    }

    private function deductFromWarehouses(int $idProduct, int $idAttribute, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_warehouse`, `quantity` FROM `' . _DB_PREFIX_ . 'm4p_warehouse_stock`
            WHERE `id_product` = ' . $idProduct . ' AND `id_product_attribute` = ' . $idAttribute . '
              AND `quantity` > 0
            ORDER BY `quantity` DESC'
        );

        foreach ($rows ?: [] as $row) {
            if ($quantity <= 0) {
                break;
            }

            $take = min((int) $row['quantity'], $quantity);
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'm4p_warehouse_stock`
                SET `quantity` = `quantity` - ' . $take . '
                WHERE `id_warehouse` = ' . (int) $row['id_warehouse'] . '
                  AND `id_product` = ' . $idProduct . ' AND `id_product_attribute` = ' . $idAttribute
            );
            $quantity -= $take;
        }
    }

    public function hookActionObjectProductDeleteAfter(array $params): void
    {
        $object = $params['object'] ?? null;
        $idProduct = (int) (is_object($object) ? $object->id : 0);
        if ($idProduct) {
            Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'm4p_warehouse_stock` WHERE `id_product` = ' . $idProduct
            );
        }
    }

    /**
     * The product's combinations, or a single "0" entry when it has none.
     *
     * @return array<int, array{id: int, name: string}>
     */
    protected function getProductCombinations(Product $product, int $idLang): array
    {
        $resume = $product->getAttributesResume($idLang);
        if (!$resume) {
            return [['id' => 0, 'name' => $this->trans('Product (no combinations)', [], 'Modules.M4pwarehouses.Admin')]];
        }

        $out = [];
        foreach ($resume as $combo) {
            $out[] = [
                'id' => (int) $combo['id_product_attribute'],
                'name' => $combo['attribute_designation'],
            ];
        }

        return $out;
    }

    /**
     * Stock map: [id_warehouse][id_product_attribute] => quantity.
     *
     * @return array<int, array<int, int>>
     */
    protected function getProductStockMap(int $idProduct): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_warehouse`, `id_product_attribute`, `quantity`
             FROM `' . _DB_PREFIX_ . 'm4p_warehouse_stock`
             WHERE `id_product` = ' . $idProduct
        );

        $map = [];
        foreach ((array) $rows as $r) {
            $map[(int) $r['id_warehouse']][(int) $r['id_product_attribute']] = (int) $r['quantity'];
        }

        return $map;
    }
}
