jQuery(function($) {
  var Admin = function() {
    this.$form = $('form.ezdefi');

    this.$form.find("input[name='EZDEFI_API_KEY']").attr('autocomplete', 'off');

    this.initValidation.call(this);

    var onChangeApiKey = this.onChangeApiKey.bind(this);
    var onChangePublicKey = this.onChangePublicKey.bind(this);

    $(document.body)
      .on('change', "input[name='EZDEFI_API_KEY']", onChangeApiKey)
      .on('change', "input[name='EZDEFI_PUBLIC_KEY']", onChangePublicKey)
      .on('click', '.nav-tabs a', this.onClickNavTabLink);
  };

  Admin.prototype.onClickNavTabLink = function(e) {
    e.preventDefault();
    $(this).tab('show');
    history.pushState({}, '', this.hash);
  };

  Admin.prototype.initValidation = function() {
    var self = this;
    self.$form.validate({
      ignore: [],
      errorElement: 'span',
      errorClass: 'error',
      errorPlacement: function(error, element) {
        error.insertAfter(element);
      },
      rules: {
        EZDEFI_API_URL: {
          required: true,
          url: true
        },
        EZDEFI_API_KEY: {
          required: true
        },
        EZDEFI_PUBLIC_KEY: {
          required: true
        }
      }
    });
  };

  Admin.prototype.onChangeApiKey = function(e) {
    var self = this;
    var $input = $(e.target);
    $input.rules('add', {
      remote: {
        url: ezdefiAdminUrl,
        type: 'POST',
        data: {
          action: 'CheckApiKey',
          api_url: function() {
            return self.$form.find('input[name="EZDEFI_API_URL"]').val();
          },
          api_key: function() {
            return self.$form.find('input[name="EZDEFI_API_KEY"]').val();
          }
        },
        beforeSend: function() {
          self.$form.find('input[name="EZDEFI_API_KEY"]').addClass('pending');
        },
        complete: function(data) {
          self.$form.find('input[name="EZDEFI_API_KEY"]').removeClass('pending');
          var response = data.responseText;
          var $inputWrapper = self.$form.find('input[name="EZDEFI_API_KEY"]').closest('div');
          if (response === 'true') {
            $inputWrapper.append('<span class="correct">Correct</span>');
            window.setTimeout(function() {
              $inputWrapper.find('.correct').remove();
            }, 1000);
          }
        }
      },
      messages: {
        remote: 'API Key is not correct. Please check again'
      }
    });
  };

  Admin.prototype.onChangePublicKey = function(e) {
    var self = this;
    var $input = $(e.target);
    $input.rules('add', {
      remote: {
        url: ezdefiAdminUrl,
        type: 'POST',
        data: {
          action: 'CheckPublicKey',
          api_url: function() {
            return self.$form.find('input[name="EZDEFI_API_URL"]').val();
          },
          api_key: function() {
            return self.$form.find('input[name="EZDEFI_API_KEY"]').val();
          },
          public_key: function() {
            return self.$form.find('input[name="EZDEFI_PUBLIC_KEY"]').val();
          }
        },
        beforeSend: function() {
          self.$form.find('input[name="EZDEFI_PUBLIC_KEY"]').addClass('pending');
        },
        complete: function(data) {
          self.$form.find('input[name="EZDEFI_PUBLIC_KEY"]').removeClass('pending');
          var response = data.responseText;
          var $inputWrapper = self.$form.find('input[name="EZDEFI_PUBLIC_KEY"]').closest('div');
          if (response === 'true') {
            $inputWrapper.append('<span class="correct">Correct</span>');
            window.setTimeout(function() {
              $inputWrapper.find('.correct').remove();
            }, 1000);
          }
        }
      },
      messages: {
        remote: 'Website\' ID is not correct. Please check again'
      }
    });
  };

  new Admin();
});
