{extends file='page.tpl'}

{block name='page_content_container'}
  <section id="ezdefi-process-payment" class="card">
    <div class="card-block">
      <div class="row">
        <div class="col-md-12">
          <h3 class="h1 card-title">
            Pay with cryptocurrency
          </h3>
          <div id="ezdefi-currency-select">
            {foreach $acceptedCurrencies as $c}
              {assign var="discount" value=$c['discount']}
              {assign var="index" value=array_search($c['symbol'], array_column($exchanges, 'token'))}
              {assign var="amount" value=$exchanges[$index]['amount']}
              {assign var="discounted" value=$amount-($amount*($discount/100))}
              {assign var="price" value=number_format($discounted, 8)}
              <div class="currency-item__wrap">
                <div class="currency-item {(isset($selectedCurrency) && $c['symbol'] === $selectedCurrency) ? 'selected' : ''}" data-symbol="{$c['symbol']}" >
                  <div class="item__logo">
                    <img src="{$c['logo']}" alt="">
                      {if not empty($c['desc'])}
                        <div class="item__desc">
                            {$c['desc']}
                        </div>
                      {/if}
                  </div>
                  <div class="item__text">
                    <div class="item__price">
                        {$price}
                    </div>
                    <div class="item__info">
                      <div class="item__symbol">
                          {$c['symbol']}
                      </div>
                      <div class="item__discount">
                        - {$discount}%
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            {/foreach}
          </div>
          <div id="ezdefi-process-loader"></div>
          <div id="ezdefi-process-tabs" style="display: none">
            <ul>
              {foreach $paymentMethods as $method}
                {if $method eq 'amount_id'}
                  <li>
                    <a href="#amount_id" id="tab-amount_id">
                      <span class="large-screen">Pay with any crypto wallet</span>
                      <span class="small-screen">Any crypto wallet</span>
                    </a>
                  </li>
                {elseif $method eq 'ezdefi_wallet'}
                  <li>
                    {assign var='icon_url' value="`$modulePath`/views/images/ezdefi-icon.png"}
                    <a href="#ezdefi_wallet" id="tab-ezdefi_wallet" style="background-image: url({$icon_url})">
                      <span class="large-screen">Pay with ezDeFi wallet</span>
                      <span class="small-screen" style="background-image: url({$icon_url})">ezDeFi wallet</span>
                    </a>
                  </li>
                {/if}
              {/foreach}
            </ul>
            {foreach $paymentMethods as $method}
              <div id="{$method}" class="ezdefi-process-panel"></div>
            {/foreach}
          </div>
        </div>
      </div>
    </div>
    <script type="application/json" id="ezdefi-process-data">{$processData|@json_encode nofilter}</script>
  </section>
{/block}