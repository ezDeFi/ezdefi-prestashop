jQuery(function($) {
  var selectors = {
    container: '#ezdefi-process-payment',
    select: '#ezdefi-currency-select',
    itemWrap: '.currency-item__wrap',
    item: '.currency-item',
    selected: '.selected-currency',
    processData: '#ezdefi-process-data',
    ezdefiPayment: '.ezdefi-payment',
    tabs: '#ezdefi-process-tabs',
    tabsNavLi: '#ezdefi-process-tabs > ul > li',
    panel: '.ezdefi-process-panel',
    ezdefiEnableBtn: '.ezdefiEnableBtn',
    loader: '#ezdefi-process-loader',
    copy: '.copy-to-clipboard',
    qrcode: '.qrcode',
    changeQrcodeBtn: '.changeQrcodeBtn'
  };

  var Process = function() {
    this.$container = $(selectors.container);
    console.log(this.$container.find(selectors.processData).text());
    this.$loader = this.$container.find(selectors.loader);
    this.$tabs = this.$container.find(selectors.tabs);
    this.$currencySelect = this.$container.find(selectors.select);
    this.processData = JSON.parse(this.$container.find(selectors.processData).text());

    var init = this.init.bind(this);
    var onSelectItem = this.onSelectItem.bind(this);
    var onChangeTab = this.onChangeTab.bind(this);
    var onClickEzdefiLink = this.onClickEzdefiLink.bind(this);
    var onUseAltQrcode = this.onUseAltQrcode.bind(this);
    var onClickQrcode = this.onClickQrcode.bind(this);

    init();

    $(document.body)
      .on('click', selectors.item, onSelectItem)
      .on('click', selectors.tabsNavLi, onChangeTab)
      .on('click', selectors.ezdefiEnableBtn, onClickEzdefiLink)
      .on('click', selectors.qrcode, onClickQrcode)
      .on('click', selectors.changeQrcodeBtn, onUseAltQrcode);
  };

  Process.prototype.init = function() {
    var tabNavLi = this.$tabs.find('ul li').first();
    tabNavLi.addClass('active');
    var id = tabNavLi.find('a').attr('href');
    this.$tabs.find(selectors.panel).each(function() {
      $(this).hide();
      $(this).removeClass('active');
    });
    var activePanel = this.$tabs.find(id);
    activePanel.show();
    activePanel.addClass('active');
    this.createEzdefiPayment.call(this, activePanel);
    this.initClipboard.call(this);
  };

  Process.prototype.initClipboard = function() {
    var clipboard = new ClipboardJS(selectors.copy);
    clipboard.on('success', function(e) {
      var trigger = $(e.trigger)[0];
      trigger.classList.add('copied');
      setTimeout(function() {
        trigger.classList.remove('copied');
      }, 2000);
    });
  };

  Process.prototype.createEzdefiPayment = function(panel = null) {
    var self = this;
    var active = panel ? panel : this.$tabs.find(selectors.panel + '.active');
    var method = active.attr('id');
    var selectedCoin = this.$currencySelect.find('.selected');
    var coin_data = JSON.parse(selectedCoin.find('script[type="application/json"]').html());
    $.ajax({
      url: self.processData.ajaxUrl,
      method: 'post',
      data: {
        action: 'create_payment',
        uoid: self.processData.uoid,
        coin_data: coin_data,
        method: method
      },
      beforeSend: function() {
        self.$loader.show();
        self.$tabs.hide();
        self.$currencySelect.hide();
      },
      success: function(response) {
        active.html($(response.data));
        self.setTimeRemaining.call(self, active);
        self.$loader.hide();
        self.$tabs.show();
        self.$currencySelect.show();
        self.checkOrderStatus.call(self);
      }
    });
  };

  Process.prototype.onSelectItem = function(e) {
    this.$currencySelect.find(selectors.item).removeClass('selected');
    var selected = $(e.currentTarget);
    selected.addClass('selected');
    this.$tabs.find(selectors.panel).empty();
    this.createEzdefiPayment.call(this);
  };

  Process.prototype.onChangeTab = function(e) {
    this.$tabs.find('> ul > li').each(function() {
      $(this).removeClass('active');
    });
    this.$tabs.find('> div').each(function() {
      $(this).hide();
      $(this).removeClass('active');
    });
    var target = $(e.target).is('li') ? $(e.target) : $(e.target).closest('li');
    target.addClass('active');
    var id = target.find('a').attr('href'),
      activePanel = this.$tabs.find(id);
    activePanel.show();
    activePanel.addClass('active');
    if (activePanel.is(':empty')) {
      this.createEzdefiPayment.call(this, activePanel);
    }
  };

  Process.prototype.onClickEzdefiLink = function(e) {
    var self = this;
    e.preventDefault();
    this.$tabs.find('li').removeClass('active');
    this.$tabs.find(selectors.panel).removeClass('active');
    this.$tabs
      .find('a#tab-ezdefi_wallet')
      .closest('li')
      .trigger('click');
  };

  Process.prototype.onUseAltQrcode = function(e) {
    e.preventDefault();
    this.$tabs.find('#amount_id .qrcode img.main').toggle();
    this.$tabs.find('#amount_id .qrcode__info--main').toggle();
    this.$tabs.find('#amount_id .qrcode img.alt').toggle();
    this.$tabs.find('#amount_id .qrcode__info--alt').toggle();
  };

  Process.prototype.onClickQrcode = function(e) {
    var self = this;
    var target = $(e.target);
    if (!target.hasClass('expired')) {
      return;
    } else {
      e.preventDefault();
      self.$currencySelect.find('.selected').click();
    }
  };

  Process.prototype.checkOrderStatus = function() {
    var self = this;

    $.ajax({
      url: self.processData.ajaxUrl,
      method: 'post',
      data: {
        action: 'check_order_status',
        uoid: self.processData.uoid
      }
    }).done(function(response) {
      if (response['data'] == 'done') {
        self.success();
      } else {
        var checkOrderStatus = self.checkOrderStatus.bind(self);
        setTimeout(checkOrderStatus, 600);
      }
    });
  };

  Process.prototype.setTimeRemaining = function(panel) {
    var self = this;
    var timeLoop = setInterval(function() {
      var endTime = panel.find('.count-down').attr('data-endtime');
      var t = self.getTimeRemaining(endTime);
      var countDown = panel.find(selectors.ezdefiPayment).find('.count-down');

      if (t.total < 0) {
        clearInterval(timeLoop);
        countDown.text('0:0');
        self.timeout(panel);
      } else {
        countDown.text(t.text);
      }
    }, 1000);
  };

  Process.prototype.getTimeRemaining = function(endTime) {
    var t = new Date(endTime).getTime() - new Date().getTime();
    var minutes = Math.floor(t / 60000);
    var seconds = ((t % 60000) / 1000).toFixed(0);
    return {
      total: t,
      text:
        seconds == 60 ? minutes + 1 + ':00' : minutes + ':' + (seconds < 10 ? '0' : '') + seconds
    };
  };

  Process.prototype.success = function() {
    location.replace(this.processData.orderConfirmUrl);
  };

  Process.prototype.timeout = function(panel) {
    panel.find('.qrcode').addClass('expired');
  };

  new Process();
});
