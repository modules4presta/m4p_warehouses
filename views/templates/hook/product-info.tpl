{**
 * m4p_warehouses
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 *}
<div class="m4p-warehouses card">
    <div class="card-body">
        <p class="m4p-total">
            <strong>{l s='Availability' d='Modules.M4pwarehouses.Shop'}:</strong>
            <span id="m4p-total">{$m4p_total|intval}</span> {l s='in stock' d='Modules.M4pwarehouses.Shop'}
        </p>
        <ul class="m4p-wh-list">
            {foreach from=$m4p_list item=w}
                <li data-warehouse="{$w.id_warehouse|intval}">
                    <span class="m4p-wh-name">{$w.name|escape:'html':'UTF-8'}</span>:
                    <span class="m4p-wh-qty">{$w.qty|intval}</span> {l s='in stock' d='Modules.M4pwarehouses.Shop'}
                    {if $w.delivery_time}
                        <small class="m4p-wh-delivery">- {l s='delivery' d='Modules.M4pwarehouses.Shop'}: {$w.delivery_time|escape:'html':'UTF-8'}</small>
                    {/if}
                </li>
            {/foreach}
        </ul>
    </div>
    <script type="application/json" id="m4p-wh-data">{$m4p_map_json nofilter}</script>
</div>
