jQuery(function($) {
  var Admin = function() {
    this.$form = $("form.ezdefi");
    this.$table = $("#ezdefi-currency-table");

    var init = this.init.bind(this);
    var toggleVariationSetting = this.toggleVariationSetting.bind(this);
    var addCurrency = this.addCurrency.bind(this);
    var removeCurrency = this.removeCurrency.bind(this);
    var onToggleEdit = this.onToggleEdit.bind(this);
    var onChangeDecimal = this.onChangeDecimal.bind(this);
    var onBlurDecimal = this.onBlurDecimal.bind(this);
    var onChangeApiKey = this.onChangeApiKey.bind(this);

    $(document.body)
      .on("click", ".editBtn", onToggleEdit)
      .on("click", ".cancelBtn", onToggleEdit)
      .on("click", ".addBtn", addCurrency)
      .on("click", ".deleteBtn", removeCurrency)
      .on("focus", ".currency-input", onChangeDecimal)
      .on("blur", ".currency-input", onBlurDecimal)
      .on("change", "input[name='EZDEFI_AMOUNT_ID']", toggleVariationSetting)
      .on("change", "input[name='EZDEFI_API_KEY']", onChangeApiKey);

    init();
  };

  Admin.prototype.init = function() {
    var self = this;

    this.$form.find("input[name='EZDEFI_API_KEY']").attr("autocomplete", "off");

    this.toggleVariationSetting.call(this);
    this.customValidationRule();
    this.initSort.call(this);
    this.initValidation.call(this);
    this.toggleVariationSetting.call(this);

    this.$table.find("select").each(function() {
      self.initCurrencySelect.call(self, $(this));
    });
  };

  Admin.prototype.customValidationRule = function() {
    jQuery.validator.addMethod(
      "greaterThanZero",
      function(value, element) {
        return parseFloat(value) > 0;
      },
      "Please enter a value greater than 0"
    );
    jQuery.validator.addMethod(
      "enabledMethod",
      function(value, element) {
        return parseInt(value) === 1;
      },
      "Please select at least one payment method"
    );
  };

  Admin.prototype.initSort = function() {
    var self = this;
    this.$table.find("tbody").sortable({
      handle: ".sortable-handle span",
      stop: function() {
        $(this)
          .find("tr")
          .each(function(rowIndex) {
            var row = $(this);
            self.updateAttr(row, rowIndex);
            row.find(".currency-decimal").rules("remove", "max");
            row.find(".currency-decimal").rules("add", {
              max: parseInt(row.find(".currency-decimal-max").val()),
              messages: {
                max: jQuery.validator.format("Max: {0}")
              }
            });
            row.find(".currency-decimal").valid();
          });
      }
    });
  };

  Admin.prototype.initValidation = function() {
    var self = this;
    self.$form.validate({
      ignore: [],
      errorElement: "span",
      errorClass: "error",
      errorPlacement: function(error, element) {
        if (element.closest(".ezdefi-method").length) {
          error.insertAfter(element.closest(".ezdefi-method"));
        } else {
          if (element.closest("#ezdefi-currency-table").length == 1) {
            if (element.hasClass("select-select2")) {
              error.insertAfter(element.closest(".edit").find(".select2-container"));
            } else {
              if (element.closest("td").find("span.error").length === 0) {
                error.appendTo(element.closest("td"));
              }
            }
          } else {
            error.insertAfter(element);
          }
        }
      },
      rules: {
        EZDEFI_API_URL: {
          required: true,
          url: true
        },
        EZDEFI_API_KEY: {
          required: true
        },
        EZDEFI_ACCEPTABLE_VARIATION: {
          required: {
            depends: function(element) {
              return self.$form.find('input[name="EZDEFI_AMOUNT_ID"]:checked').val() == 1;
            }
          },
          number: true,
          greaterThanZero: true,
          max: 100
        },
        EZDEFI_AMOUNT_ID: {
          enabledMethod: {
            depends: function(element) {
              return !self.$form.find("#EZDEFI_EZDEFI_WALLET").is(":checked");
            }
          }
        },
        EZDEFI_EZDEFI_WALLET: {
          enabledMethod: {
            depends: function(element) {
              return !self.$form.find("#EZDEFI_AMOUNT_ID").is(":checked");
            }
          }
        }
      }
    });

    this.$table.find("tbody tr").each(function() {
      var row = $(this);
      self.addValidationRule(row);
    });
  };

  Admin.prototype.addValidationRule = function($row) {
    var self = this;
    $row.find("input, select").each(function() {
      var name = $(this).attr("name");

      if (name.indexOf("discount") > 0) {
        $('input[name="' + name + '"]').rules("add", {
          min: 0,
          max: 100,
          messages: {
            min: jQuery.validator.format("Min: {0}"),
            max: jQuery.validator.format("Max: {0}")
          }
        });
      }

      if (name.indexOf("select") > 0) {
        var $select = $('select[name="' + name + '"]');
        $select.rules("add", {
          required: true,
          messages: {
            required: "Please select currency"
          }
        });
        $select.on("select2:close", function() {
          $(this).valid();
        });
      }

      if (name.indexOf("wallet") > 0) {
        var $input = $('input[name="' + name + '"]');
        $input.rules("add", {
          required: true,
          messages: {
            required: "Please enter wallet address"
          }
        });
      }

      if (name.indexOf("lifetime") > 0) {
        var $input = $('input[name="' + name + '"]');
        $input.rules("add", {
          min: 0
        });
      }

      if (name.indexOf("block_confirm") > 0) {
        var $input = $('input[name="' + name + '"]');
        $input.rules("add", {
          min: 0,
          messages: {
            min: jQuery.validator.format("Min: {0}")
          }
        });
      }

      if (name.indexOf("decimal") > 0 && name.indexOf("decimal_max") < 0) {
        var $input = $('input[name="' + name + '"]');
        var decimal_max = parseInt(
          $input
            .parent()
            .find(".currency-decimal-max")
            .val()
        );
        $input.rules("add", {
          required: true,
          min: 2,
          max: decimal_max,
          messages: {
            min: jQuery.validator.format("Min: {0}"),
            max: jQuery.validator.format("Max: {0}"),
            required: "Required"
          }
        });
      }
    });
  };

  Admin.prototype.toggleVariationSetting = function(e) {
    var value = this.$form.find('input[name="EZDEFI_AMOUNT_ID"]:checked').val();
    if (value == 1) {
      this.$form
        .find("#EZDEFI_ACCEPTABLE_VARIATION")
        .closest(".form-group")
        .show();
    } else {
      this.$form
        .find("#EZDEFI_ACCEPTABLE_VARIATION")
        .closest(".form-group")
        .hide();
    }
  };

  Admin.prototype.initCurrencySelect = function(element) {
    var self = this;
    element.select2({
      width: "100%",
      ajax: {
        url: ezdefiAdminUrl,
        type: "POST",
        data: function(params) {
          var query = {
            ajax: true,
            action: "GetTokens",
            api_url: self.$form.find('input[name="EZDEFI_API_URL"]').val(),
            api_key: self.$form.find('input[name="EZDEFI_API_KEY"]').val(),
            keyword: params.term
          };
          return query;
        },
        processResults: function(data) {
          return {
            results: data.data
          };
        },
        cache: true,
        dataType: "json",
        delay: 250
      },
      placeholder: "Select currency",
      minimumInputLength: 1,
      templateResult: self.formatCurrencyOption,
      templateSelection: self.formatCurrencySelection
    });
    element.on("select2:select", self.onSelect2Select);
  };

  Admin.prototype.formatCurrencyOption = function(currency) {
    if (currency.loading) {
      return currency.text;
    }

    var excludes = [];

    $("#ezdefi-currency-table")
      .find("tbody tr")
      .each(function() {
        var symbol = $(this)
          .find(".currency-symbol")
          .val();
        if (symbol && symbol.length > 0) {
          excludes.push(symbol);
        }
      });

    if (excludes.includes(currency.symbol)) {
      return;
    }

    var $container = $(
      "<div class='select2-currency'>" +
        "<div class='select2-currency__icon'><img src='" +
        currency.logo +
        "' /></div>" +
        "<div class='select2-currency__name'>" +
        currency.name +
        "</div>" +
        "</div>"
    );

    return $container;
  };

  Admin.prototype.formatCurrencySelection = function(currency) {
    return currency.name || currency.text;
  };

  Admin.prototype.onSelect2Select = function(e) {
    var td = $(e.target).closest("td");
    var tr = $(e.target).closest("tr");
    var data = e.params.data;
    td.find(".currency-symbol").val(data.symbol);
    td.find(".currency-name").val(data.name);
    td.find(".currency-logo").val(data.logo);
    td.find(".currency-chain").val(data.chain.network_type);
    if (data.description) {
      td.find(".currency-desc").val(data.description);
    } else {
      td.find(".currency-desc").val("");
    }
    tr.find(".logo img").attr("src", data.logo);
    tr.find(".currency-decimal").val(data.suggestedDecimal);
    tr.find(".currency-decimal")
      .closest("td")
      .find(".view")
      .text(data.suggestedDecimal);
    tr.find(".currency-decimal").rules("remove", "max");
    tr.find(".currency-decimal-max").val(data.decimal);
    tr.find(".currency-decimal").rules("add", {
      max: data.decimal,
      messages: {
        max: jQuery.validator.format("Max: {0}")
      }
    });
    tr.find(".currency-decimal").valid();
    td.find(".view span").text(data.name);
  };

  Admin.prototype.onToggleEdit = function(e) {
    e.preventDefault();

    var self = this;
    var $row = $(e.target).closest("tr");

    if ($row.find(".currency-symbol").val() === "") {
      self.removeCurrency(e);
    } else {
      $row.toggleClass("editing");
    }
  };

  Admin.prototype.addCurrency = function(e) {
    e.preventDefault();
    var $row = this.$table.find("tbody tr:last");
    var $clone = $row.clone();
    var count = this.$table.find("tbody tr").length;
    var selectName = $clone.find("select").attr("name");
    var $select = $('<select name="' + selectName + '" class="select-select2"></select>');

    $clone.attr("data-saved", "0");
    $clone.find("select, .select2-container").remove();
    $clone.find(".logo img").attr("src", "");
    $clone.find(".name .view span").empty();
    $clone.find(".name .edit").prepend($select);
    $clone.find("input").each(function() {
      var input = $(this);
      var td = input.closest("td");
      if (input.is('input[name*="discount"]')) {
        input.val(0);
      } else if (input.is('input[name*="lifetime"]')) {
        input.val(15);
      } else if (input.is('input[name*="block_confirm"]')) {
        input.val(1);
      } else {
        input.val("");
      }
      if (!td.hasClass("name")) {
        td.find(".view").empty();
      }
    });
    $clone.find("td").each(function() {
      $(this).removeClass("form-invalid");
      $(this)
        .find(".error")
        .remove();
    });
    this.updateAttr($clone, count);
    this.removeAttr($clone);
    $clone.insertAfter($row);
    this.initCurrencySelect($select);
    this.addValidationRule($clone);
    $clone.addClass("editing");
    return false;
  };

  Admin.prototype.removeCurrency = function(e) {
    e.preventDefault();

    var self = this;

    if (self.$table.find("tbody tr").length === 1) {
      alert("You must select at least 1 accepted currency");
      return false;
    }

    if (confirm("Do you want to delete this row")) {
      $(e.target)
        .closest("tr")
        .remove();
      self.$table.find("tr").each(function(rowIndex) {
        $(this)
          .find(".select2-container")
          .remove();
        var $select = $(this).find(".select-select2");
        self.initCurrencySelect($select);

        if ($(this).hasClass("editing")) {
          var name = $(this)
            .find(".currency-name")
            .val();
          $(this)
            .find(".select2-selection__rendered")
            .attr("title", name);
          $(this)
            .find(".select2-selection__rendered")
            .text(name);
        }

        var row = $(this);
        var number = rowIndex - 1;
        self.updateAttr(row, number);
      });
    }
    return false;
  };

  Admin.prototype.updateAttr = function(row, number) {
    row.find("input, select").each(function() {
      var name = $(this).attr("name");
      name = name.replace(/\[(\d+)\]/, "[" + parseInt(number) + "]");
      $(this)
        .attr("name", name)
        .attr("id", name);
    });
  };

  Admin.prototype.removeAttr = function(row) {
    row.find("input, select").each(function() {
      $(this)
        .removeAttr("aria-describedby")
        .removeAttr("aria-invalid");
    });
  };

  Admin.prototype.onChangeDecimal = function(e) {
    var input = $(e.target);
    if (input.val().length > 0) {
      var td = $(e.target).closest("td");
      if (td.find("span.error").length === 0 && input.closest("tr").attr("data-saved") == "1") {
        td.find(".edit").append(
          '<span class="error decimal-warning">Changing decimal can cause to payment interruption</span>'
        );
      }
    }
  };

  Admin.prototype.onBlurDecimal = function(e) {
    var td = $(e.target).closest("td");
    td.find(".edit")
      .find(".decimal-warning")
      .remove();
  };

  Admin.prototype.onChangeApiKey = function(e) {
    var self = this;
    var $input = $(e.target);
    $input.rules("add", {
      remote: {
        url: ezdefiAdminUrl,
        type: "POST",
        data: {
          action: "CheckApiKey",
          api_url: function() {
            return self.$form.find('input[name="EZDEFI_API_URL"]').val();
          },
          api_key: function() {
            return self.$form.find('input[name="EZDEFI_API_KEY"]').val();
          }
        },
        beforeSend: function() {
          self.$form.find('input[name="EZDEFI_API_KEY"]').addClass("pending");
        },
        complete: function(data) {
          self.$form.find('input[name="EZDEFI_API_KEY"]').removeClass("pending");
          var response = data.responseText;
          var $inputWrapper = self.$form.find('input[name="EZDEFI_API_KEY"]').closest("div");
          if (response === "true") {
            $inputWrapper.append('<span class="correct">Correct</span>');
            window.setTimeout(function() {
              $inputWrapper.find(".correct").remove();
            }, 1000);
          }
        }
      },
      messages: {
        remote: "API Key is not correct. Please check again"
      }
    });
  };

  new Admin();
});
