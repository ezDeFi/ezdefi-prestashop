<section id="ezdefi-currency-select">
  {foreach $coins as $c}
    {assign var="discount" value=$c['discount']}
    {assign var="index" value=array_search($c['token']['symbol'], array_column($exchanges, 'token'))}
    {assign var="amount" value=$exchanges[$index]['amount']}
    {assign var="discounted" value=$amount-($amount*($discount/100))}
    {assign var="price" value=number_format($discounted, 8)}
    <div class="currency-item__wrap">
      <div class="currency-item" data-id="{$c['_id']}" data-symbol="{$c['token']['symbol']}">
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
  <link rel="stylesheet" href="{$modulePath}views/css/currency-select.css">
  <script src="{$modulePath}views/js/checkout.js" defer></script>
</section>