jQuery(function($) {
  var Checkout = function() {
    this.$currencySelect = $('#ezdefi-currency-select');

    var onSelectItem = this.onSelectItem.bind(this);

    $(this.$currencySelect).on('click', '.currency-item__wrap', onSelectItem);
  };

  Checkout.prototype.onSelectItem = function(e) {
    this.$currencySelect.find('.currency-item').removeClass('selected');

    var target = $(e.target);
    var selected;

    if (target.is('.currency-item__wrap')) {
      selected = target.find('.currency-item');
    } else {
      selected = target.closest('.currency-item__wrap').find('.currency-item');
    }

    selected.addClass('selected');

    var coinId = selected.attr('data-id');

    if (!coinId || coinId.length === 0) {
      return false;
    }

    $('input[name="ezdefi_coin"]').val(coinId);
  };

  new Checkout();
});
