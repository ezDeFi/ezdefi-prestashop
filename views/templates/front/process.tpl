{extends file='page.tpl'}

{block name='page_content_container'}
  <section id="ezdefi-process-payment" class="card">
    <div class="card-block">
      <div class="row">
        <div class="col-md-12">
          <h3 class="h1 card-title">
            Pay with cryptocurrencies
          </h3>
          <div id="ezdefi-currency-select">
            {foreach $coins as $c}
              {assign var="discount" value=$c['discount']}
              {assign var="index" value=array_search($c['token']['symbol'], array_column($exchanges, 'token'))}
              {assign var="amount" value=$exchanges[$index]['amount']}
              {assign var="discounted" value=$amount-($amount*($discount/100))}
              {assign var="price" value=number_format($discounted, 8)}
              <div class="currency-item__wrap">
                <div class="currency-item {(isset($selectedCurrency) && $c['_id'] === $selectedCurrency['_id']) ? 'selected' : ''}" data-id="{$c['_id']}" data-symbol="{$c['token']['symbol']}" >
                  <script type="application/json">
                    {$c['json_data']|@json_encode nofilter}
                  </script>
                  <div class="item__logo">
                    <img src="{$c['token']['logo']}" alt="">
                      {if not empty($c['token']['desc'])}
                        <div class="item__desc">
                            {$c['token']['desc']}
                        </div>
                      {/if}
                  </div>
                  <div class="item__text">
                    <div class="item__price">
                        {$price}
                    </div>
                    <div class="item__info">
                      <div class="item__symbol">
                          {$c['token']['symbol']}
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
              {if $website_config['website']['payAnyWallet']}
                <li>
                  <a href="#amount_id" id="tab-amount_id">
                    <span class="large-screen">Pay with any crypto wallet</span>
                    <span class="small-screen">Any crypto wallet</span>
                  </a>
                </li>
              {/if}
              {if $website_config['website']['payEzdefiWallet']}
                <li>
                  {assign var='icon_url' value="`$modulePath`/views/images/ezdefi-icon.png"}
                  <a href="#ezdefi_wallet" id="tab-ezdefi_wallet" style="background-image: url({$icon_url})">
                    <span class="large-screen">Pay with ezDeFi wallet</span>
                    <span class="small-screen" style="background-image: url({$icon_url})">ezDeFi wallet</span>
                  </a>
                </li>
              {/if}
            </ul>
            {if $website_config['website']['payAnyWallet']}
              <div id="amount_id" class="ezdefi-process-panel"></div>
            {/if}
            {if $website_config['website']['payEzdefiWallet']}
              <div id="ezdefi_wallet" class="ezdefi-process-panel"></div>
            {/if}
          </div>
        </div>
      </div>
    </div>
    <script type="application/json" id="ezdefi-process-data">{$processData|@json_encode nofilter}</script>
  </section>
{/block}