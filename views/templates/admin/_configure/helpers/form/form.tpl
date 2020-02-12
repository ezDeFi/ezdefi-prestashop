{extends 'helpers/form/form.tpl'}

{block name="fieldset"}
  {if isset($fieldset.form) }
    {$smarty.block.parent}
  {elseif isset($fieldset.form_submit_button) }
    <button type="submit" class="btn btn-primary">Save</button>
  {/if}
{/block}

{block name="input"}
  {if $input.type == 'ezdefi_method_checkbox'}
    <div class="ezdefi-method">
      <fieldset>
        <label for="EZDEFI_AMOUNT_ID">
        <input type="checkbox" {if $fields_value['EZDEFI_AMOUNT_ID'] == '1'}checked="checked"{/if}" name="EZDEFI_AMOUNT_ID" id="EZDEFI_AMOUNT_ID" value="1"> Pay with any crypto wallet</label>
        <p class="help-block">This method will adjust payment amount of each order by an acceptable number to help payment gateway identifying the uniqueness of that order.</p>
      </fieldset>
      <fieldset>
        <label for="EZDEFI_EZDEFI_WALLET">
        <input type="checkbox" {if $fields_value['EZDEFI_EZDEFI_WALLET'] == '1'}checked="checked"{/if}" name="EZDEFI_EZDEFI_WALLET" id="EZDEFI_EZDEFI_WALLET" value="1"> Pay with ezDeFi wallet</label>
        <p class="help-block">This method is more powerful when amount uniqueness of above method reaches allowable limit. Users need to install ezDeFi wallet then import their private key to pay using QR Code.</p>
      </fieldset>
    </div>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="input_row"}
  {if $input.type == 'currency_table'}
    {assign var='ezdefiCurrency' value=$fields_value[$input.name]}
    <table class="table" id="ezdefi-currency-table">
      <thead class="thead-default">
      <tr class="column-headers">
        <th scope="col" class="sortable-zone"></th>
        <th scope="col" class="logo"></th>
        <th scope="col" class="name"><strong>Name</strong></th>
        <th scope="col" class="discount"><strong>Discount</strong></th>
        <th scope="col" class="lifetime"><strong>Expiration (minuties)</strong></th>
        <th scope="col" class="wallet"><strong>Wallet Address</strong></th>
        <th scope="col" class="block-confirm"><strong>Block Confirmation</strong></th>
        <th scope="col" class="decimal"><strong>Decimal</strong></th>
      </tr>
      </thead>
      <tbody>
      {foreach $ezdefiCurrency as $index => $currency}
        <tr data-saved="1">
          <td class="sortable-handle">
            <span><i class="icon icon-bars" aria-hidden="true"></i></span>
          </td>
          <td class="logo">
            <img src="{$currency['logo']}" class="ezdefi-currency-logo" alt="">
          </td>
          <td class="name">
            <input class="currency-symbol" type="hidden" value="{$currency['symbol']}" name="EZDEFI_CURRENCY[{$index}][symbol]">
            <input class="currency-name" type="hidden" value="{$currency['name']}" name="EZDEFI_CURRENCY[{$index}][name]">
            <input class="currency-logo" type="hidden" value="{$currency['logo']}" name="EZDEFI_CURRENCY[{$index}][logo]">
            <input class="currency-desc" type="hidden" value="{$currency['desc']}" name="EZDEFI_CURRENCY[{$index}][desc]">
            <div class="view">
              <span>{$currency['name']}</span>
              <div class="actions">
                <a href="" class="editBtn">Edit</a>
                |
                <a href="" class="deleteBtn">Delete</a>
              </div>
            </div>
            <div class="edit">
              <select name="EZDEFI_CURRENCY[{$index}][select]" class="select-select2">
                <option value="{$currency['symbol']}" selected="selected">{$currency['name']}</option>
              </select>
              <div class="actions">
                <a href="" class="cancelBtn">Cancel</a>
              </div>
            </div>
          </td>
          <td class="discount">
            <div class="view">
              {(empty($currency['discount'])) ? 0 : $currency['discount']}%
            </div>
            <div class="edit">
              <input type="number" name="EZDEFI_CURRENCY[{$index}][discount]" value="{$currency['discount']}"><span> %</span>
            </div>
          </td>
          <td class="lifetime">
            <div class="view">
              {if not empty($currency['lifetime'])}
                {$currency['lifetime']}m
              {/if}
            </div>
            <div class="edit">
              <input type="number" name="EZDEFI_CURRENCY[{$index}][lifetime]" value="{$currency['lifetime']}"><span> m</span>
            </div>
          </td>
          <td class="wallet">
            <div class="view">
              {(empty($currency['wallet_address'])) ? '' : $currency['wallet_address']}
            </div>
            <div class="edit">
              <input type="text" class="currency-wallet" placeholder="Wallet Address" name="EZDEFI_CURRENCY[{$index}][wallet_address]" value="{$currency['wallet_address']}">
            </div>
          </td>
          <td class="block_confirm">
            <div class="view">
              {(empty($currency['block_confirm'])) ? '' : $currency['block_confirm']}
            </div>
            <div class="edit">
              <input type="number" name="EZDEFI_CURRENCY[{$index}][block_confirm]" value="{$currency['block_confirm']}">
            </div>
          </td>
          <td class="decimal">
            <div class="view">
              {(empty($currency['decimal'])) ? '' : $currency['decimal']}
            </div>
            <div class="edit">
              <input type="number" class="currency-decimal" name="EZDEFI_CURRENCY[{$index}][decimal]" value="{$currency['decimal']}">
              <input type="hidden" class="currency-decimal-max" name="EZDEFI_CURRENCY[{$index}][decimal_max]" value="{$currency['decimal_max']}">
            </div>
          </td>
        </tr>
      {/foreach}
      </tbody>
      <tfoot>
      <tr>
        <td colspan="8">
          <a href="" class="addBtn btn btn-default">
            Add Currency
          </a>
        </td>
      </tr>
      </tfoot>
    </table>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
