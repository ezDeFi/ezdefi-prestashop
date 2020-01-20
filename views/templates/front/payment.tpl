{assign var=total value=$order->total_paid_tax_incl}
{assign var=discount value=$currencyData['discount']}
{assign var=total value=($total - ($total * ($discount / 100)))}
<div class="ezdefi-payment" data-paymentid="{$payment['_id']}">
  {if not $payment}
    Can not get payment
  {else}
    {if isset($payment['amountId']) && ($payment['amountId'] === true)}
      {assign var=value value=$payment['originValue']}
    {else}
      {assign var=value value=($payment['value'] / pow(10, $payment['decimal']))}
    {/if}
    {assign var=notation value=explode('E', $value)}
    {if count($notation) eq 2}
      {assign var=exp value=(abs(end($notation)) + strlen($notation[0]))}
      {assign var=decimal value=(number_format($value, $exp))}
      {assign var=value value=(rtrim($decimal, '.0'))}
    {/if}
    <p class="exchange">
      <span>{$fiat} {$total}</span>
      <img width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAQAAAAAYLlVAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjChgQMyxZjA7+AAACP0lEQVRo3u2YvWsUQRTAf8nFQs5LCEY0aCGIB1ErRVMoFpYGTGNlo2AnBxHlrLQJKVSwiV//gqCV4gemEGJhiBYXRAtBDIhICiUGL8GP3Fjs7rs5vN0o5M1LsW+a2XkDv9/MvF12t4B2dDDODqbVOan46zgaVKzwN3A4O4VuarGAo8EZC4VeXnoKJruQK+QKa12hI2VyFyUFhY08Ymfcd1S49feU7VSZ5DPL4qrXGpxuhW/iJj8DgJutTrGJ38vHoPCobUnwg9QN8HeTItzGNP2yF7M85D11lTvhLAPSn2CYpah7R5zmOUmnChrgsrf6p6xPhvfRiAe/slsNnoqHcRketsDDbDw8ZYPvlsR5CzwMSGpICT+WhYdBSR4Ov3p9gbGV8Hr3PEAPx6XvPXZC7sBm3qSvPoRApJCB71KB+jHHERbab34YAZjLSuoW4T+EuYBNHJXC32W+A2taYAN9lgJFHjDZfGsNHUWe4XC8VVHwirD9hBLPZcpM+mN0NQTaHUGR+xySq3vpj1Gd8FfvuKjCyDiC5OyjdklpkSeE0N+aCLF6gNGY8IuCBb4zfklxzFjg4ZRQRi3wB/guB1AOjV9HhUXh3Ibo87zEYw7KpFqUWPUoUWaIrXL9gf18iRSeGPyamGdPYlI2wL/zflPQx4+g8CWu0tN6OiNBwL/5xAQjXhWQFCFc4IqMvOYY3xSKcIHlrPQ5z/UVvSr3wQqRK+QKuYIfVU9hSuGt+L924ZoFvqmgji+kZl6wSI2qtsAfm/EoPAbFFD0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTAtMjRUMTY6NTE6NDQrMDA6MDBiAik3AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEwLTI0VDE2OjUxOjQ0KzAwOjAwE1+RiwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=" />
      <span class="currency">{$value} {$payment['currency']}</span>
    </p>
    <p>You have <span class="count-down" data-endtime="{$payment['expiredTime']}"></span> to scan this QR Code</p>
    <p>
      {if isset($payment['amountId']) && ($payment['amountId'] === true)}
        {assign var=deepLink value=$payment['deepLink']}
      {else}
        {assign var=deepLink value="ezdefi://`$payment['deepLink']`"}
      {/if}
      <a class="qrcode {(time() > strtotime($payment['expiredTime'])) ? 'expired' : ''}" href="{$deepLink}" target="_blank">
        <img class="main" src="{$payment['qr']}" />
        {if isset($payment['amountId']) && ($payment['amountId'] === true)}
        <img class="alt" style="display: none" src="https://chart.googleapis.com/chart?cht=qr&chl={$payment['to']}&chs=200x200&chld=L|0'" alt="">
        {/if}
      </a>
    </p>
    {if isset($payment['amountId']) && ($payment['amountId'] === true)}
      <p class="receive-address">
        <strong>Address:</strong>
        <span class="copy-to-clipboard" data-clipboard-text="{$payment['to']}" title="Copy to clipboard">
          <span class="copy-content">{$payment['to']}</span>
          <img src="{$modulePath}/views/images/copy-icon.svg" />
        </span>
      </p>
      <p class="payment-amount">
        <strong>Amount:</strong>
        <span class="copy-to-clipboard" data-clipboard-text="{$value}" title="Copy to clipboard">
          <span class="copy-content">{$value}</span>
          <span class="amount">{$payment['token']['symbol']}</span>
          <img src="{$modulePath}/views/images/copy-icon.svg" />
        </span>
      </p>
      <div class="qrcode__info--main">
        <p class="note">
          If you get error when scanning this QR Code, please use
          <a href="" class="changeQrcodeBtn"> alternative QR Code</a>
        </p>
      </div>
      <div class="qrcode__info--alt" style="display: none">
        <p class="note">
          You have to pay exact amount so that your order can be handled properly.<br/>
        </p>
        <p class="note">
          If you have difficulty for sending exact amount, try <a href="" class="ezdefiEnableBtn">ezDeFi Wallet</a>
        </p>
        <p class="changeQrcode">
          <a class="changeQrcodeBtn" href="">Use original QR Code</a>
        </p>
      </div>
    {else}
    <p class="app-link-list">
      <a target="_blank" href="https://ezdefi.com/ios?utm_source=woocommerce-download"><img src="{$modulePath}/views/images/ios-icon.png" /></a>
      <a target="_blank" href="https://ezdefi.com/android?utm_source=woocommerce-download"><img src="{$modulePath}/views/images/android-icon.png" /></a>
    </p>
    {/if}
  {/if}
</div>