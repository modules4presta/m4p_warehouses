{**
 * m4p_warehouses
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}
<div class="panel" id="m4p-warehouses-panel">
    <h3><i class="icon-store"></i> {l s='Warehouse stock' d='Modules.M4pwarehouses.Admin'}</h3>

    {if !$m4p_warehouses}
        <div class="alert alert-info">
            {l s='Add your warehouses first in' d='Modules.M4pwarehouses.Admin'}
            <a href="{$m4p_ajax_url|escape:'html':'UTF-8'}">{l s='Catalogue → Warehouses' d='Modules.M4pwarehouses.Admin'}</a>.
        </div>
    {else}
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Warehouse' d='Modules.M4pwarehouses.Admin'}</th>
                        {foreach from=$m4p_combinations item=combo}
                            <th class="text-center">{$combo.name|escape:'html':'UTF-8'}</th>
                        {/foreach}
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$m4p_warehouses item=w}
                        <tr>
                            <td>
                                <strong>{$w.name|escape:'html':'UTF-8'}</strong>
                                {if $w.delivery_time}
                                    <br><small class="text-muted">{$w.delivery_time|escape:'html':'UTF-8'}</small>
                                {/if}
                            </td>
                            {foreach from=$m4p_combinations item=combo}
                                <td>
                                    <input type="number" min="0" step="1"
                                        class="form-control m4p-stock-input"
                                        data-warehouse="{$w.id_warehouse|intval}"
                                        data-attribute="{$combo.id|intval}"
                                        value="{if isset($m4p_stock[$w.id_warehouse][$combo.id])}{$m4p_stock[$w.id_warehouse][$combo.id]|intval}{else}0{/if}">
                                </td>
                            {/foreach}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-primary" id="m4p-save-stock">
            <i class="icon-save"></i> {l s='Save stock' d='Modules.M4pwarehouses.Admin'}
        </button>
        <span id="m4p-save-msg" style="margin-left:10px;font-weight:bold;"></span>

        <script>
            var M4P_AJAX_URL = '{$m4p_ajax_url|escape:'javascript':'UTF-8'}';
            var M4P_ID_PRODUCT = {$m4p_id_product|intval};
            {literal}
            (function () {
                var btn = document.getElementById('m4p-save-stock');
                if (!btn) {
                    return;
                }
                btn.addEventListener('click', function () {
                    var inputs = document.querySelectorAll('.m4p-stock-input');
                    var stock = {};
                    inputs.forEach(function (i) {
                        var w = i.getAttribute('data-warehouse');
                        var a = i.getAttribute('data-attribute');
                        if (!stock[w]) {
                            stock[w] = {};
                        }
                        stock[w][a] = parseInt(i.value, 10) || 0;
                    });

                    var msg = document.getElementById('m4p-save-msg');
                    msg.textContent = '...';
                    msg.style.color = '';

                    var body = 'ajax=1&action=SaveProductStock'
                        + '&id_product=' + encodeURIComponent(M4P_ID_PRODUCT)
                        + '&stock=' + encodeURIComponent(JSON.stringify(stock));

                    fetch(M4P_AJAX_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            msg.style.color = 'green';
                            msg.textContent = 'OK ✓';
                        } else {
                            msg.style.color = 'red';
                            msg.textContent = (data && data.message) ? data.message : 'Error';
                        }
                    })
                    .catch(function () {
                        msg.style.color = 'red';
                        msg.textContent = 'Error';
                    });
                });
            })();
            {/literal}
        </script>
    {/if}
</div>
