{addJsDef ezdefiAdminUrl=$ezdefiAdminUrl}

<ul class="nav nav-tabs" role="tablist">
  <li">
    <a href="#ezdefi-settings" role="tab" >{l s="Settings" m="ezdefi"}</a>
  </li>
  <li">
    <a href="#ezdefi-logs" role="tab" >{l s="Transaction Logs" m="ezdefi"}</a>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane {if $activeTab === 'ezdefi-settings'}active{/if}" id="ezdefi-settings">
    {include file='./settings.tpl'}
  </div>
  <div class="tab-pane {if $activeTab === 'ezdefi-logs'}active{/if}" id="ezdefi-logs">
    {include file='./logs.tpl'}
  </div>
</div>