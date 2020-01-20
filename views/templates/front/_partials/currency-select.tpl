{*{foreach $currencies as $c}*}
{*  {assign var="discount" value=$c['discount']}*}
{*  {assign var="index" value=array_search($c['symbol'], array_column($exchanges, 'token'))}*}
{*  {assign var="amount" value=$exchanges[$index]['amount']}*}
{*  {assign var="discounted" value=$amount-($amount*($discount/100))}*}
{*  {assign var="price" value=number_format($discounted, 8)}*}
{*  <div class="currency-item__wrap">*}
{*    <div class="currency-item {(isset($selected_currency) && $c['symbol'] === $selected_currency['symbol']) ? 'selected' : ''}" data-symbol="{$c['symbol']}" >*}
{*      <div class="item__logo">*}
{*        <img src="{$c['logo']}" alt="">*}
{*          {if not empty($c['desc'])}*}
{*            <div class="item__desc">*}
{*                {$c['desc']}*}
{*            </div>*}
{*          {/if}*}
{*      </div>*}
{*      <div class="item__text">*}
{*        <div class="item__price">*}
{*            {$price}*}
{*        </div>*}
{*        <div class="item__info">*}
{*          <div class="item__symbol">*}
{*              {$c['symbol']}*}
{*          </div>*}
{*          <div class="item__discount">*}
{*            - {$discount}%*}
{*          </div>*}
{*        </div>*}
{*      </div>*}
{*    </div>*}
{*  </div>*}
{*{/foreach}*}

{*{$currencies|@var_dump}*}

asfassfasf