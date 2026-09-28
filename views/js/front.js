/**
 * m4p_warehouses
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

(function () {
    'use strict';

    function getMap() {
        var node = document.getElementById('m4p-wh-data');
        if (!node) {
            return {};
        }
        try {
            return JSON.parse(node.textContent) || {};
        } catch (e) {
            return {};
        }
    }

    function render(idAttribute) {
        var map = getMap();
        var entry = map[idAttribute] || map[0] || { total: 0, wh: {} };

        var total = document.getElementById('m4p-total');
        if (total) {
            total.textContent = entry.total || 0;
        }

        document.querySelectorAll('.m4p-wh-list li').forEach(function (li) {
            var id = li.getAttribute('data-warehouse');
            var qty = (entry.wh && entry.wh[id] != null) ? entry.wh[id] : 0;
            var span = li.querySelector('.m4p-wh-qty');
            if (span) {
                span.textContent = qty;
            }
        });
    }

    function currentAttribute(event) {
        if (event && event.id_product_attribute) {
            return event.id_product_attribute;
        }
        var input = document.querySelector('input[name="id_product_attribute"]');
        return input ? input.value : 0;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
            prestashop.on('updatedProduct', function (event) {
                render(currentAttribute(event));
            });
        }
    });
})();
