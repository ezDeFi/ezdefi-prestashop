jQuery(function($) {
  "use strict";

  var selectors = {
    table: "#ezdefi-transaction-logs",
    tab: '#ezdefi-transaction-tab',
    select: "#order-select",
    expceptionIdInput: '.exception-id-input',
    amountIdInput: ".amount-id-input",
    currencyInput: ".currency-input",
    orderIdInput: ".order-id-input",
    oldOrderIdInput: ".old-order-id-input",
    assignBtn: ".assignBtn",
    removeBtn: ".removeBtn",
    reverseBtn: ".reverseBtn",
    filterBtn: ".filterBtn",
    filterForm: "#ezdefi-transaction-filter",
    pagination: "#ezdefi-logs-pagination",
    showSelectBtn: ".showSelectBtn",
    hideSelectBtn: ".hideSelectBtn",
    savedOrder: ".saved-order",
    selectOrder: ".select-order"
  };

  var Logs = function() {
    this.$table = $(selectors.table);
    this.$pagination = $(selectors.pagination);
    this.$tab = $(selectors.tab);

    var init = this.init.bind(this);
    var onAssign = this.onAssign.bind(this);
    var onRemove = this.onRemove.bind(this);
    var onReverse = this.onReverse.bind(this);
    var onApplyFilter = this.onApplyFilter.bind(this);
    var onShowOrderSelect = this.onShowOrderSelect.bind(this);
    var onHideOrderSelect = this.onHideOrderSelect.bind(this);
    var onNext = this.onNext.bind(this);
    var onPrev = this.onPrev.bind(this);
    var onChangePage = this.onChangePage.bind(this);

    init();

    $(document.body)
      .on("click", selectors.assignBtn, onAssign)
      .on("click", selectors.removeBtn, onRemove)
      .on("click", selectors.reverseBtn, onReverse)
      .on("click", selectors.filterBtn, onApplyFilter)
      .on("click", selectors.showSelectBtn, onShowOrderSelect)
      .on("click", selectors.hideSelectBtn, onHideOrderSelect)
      .on("click", ".next", onNext)
      .on("click", ".prev", onPrev)
      .on("click", ".pagination a", onChangePage);
  };

  Logs.prototype.init = function() {
    var data = this.getAjaxData();
    this.getException.call(this, data);
  };

  Logs.prototype.onShowOrderSelect = function(e) {
    e.preventDefault();
    var column = $(e.target).closest("td");

    column.find(selectors.showSelectBtn).hide();
    column.find(selectors.hideSelectBtn).show();
    column.find(selectors.savedOrder).hide();
    column.find(selectors.selectOrder).show();

    this.initSelect2.call(this, column.find("select"));
  };

  Logs.prototype.initSelect2 = function(select) {
    var self = this;
    select.select2({
      width: "100%",
      ajax: {
        url: ezdefiAdminUrl,
        type: "POST",
        data: function(params) {
          var query = {
            ajax: true,
            action: "GetOrders",
            keyword: params.term
          };

          return query;
        },
        processResults: function(data) {
          return {
            results: data
          };
        },
        cache: true,
        dataType: "json",
        delay: 250
      },
      placeholder: "Select Order",
      templateResult: self.formatOrderOption,
      templateSelection: self.formatOrderSelection
    });
    select.on("select2:select", this.onSelect2Select);
  };

  Logs.prototype.onSelect2Select = function(e) {
    var column = $(e.target).closest("td");
    var data = e.params.data;

    column.find(selectors.orderIdInput).val(data.id);
  };

  Logs.prototype.onHideOrderSelect = function(e) {
    e.preventDefault();
    var self = this;
    var column = $(e.target).closest("td");

    column.find(selectors.showSelectBtn).show();
    column.find(selectors.hideSelectBtn).hide();
    column.find(selectors.savedOrder).show();
    column.find(selectors.selectOrder).hide();

    column.find("select").select2("destroy");

    var savedOrderId = column.find("#saved-order-id").text();
    column.find(selectors.orderIdInput).val(savedOrderId);
  };

  Logs.prototype.onApplyFilter = function(e) {
    e.preventDefault();
    var data = this.getAjaxData();
    this.getException.call(this, data);
  };

  Logs.prototype.getAjaxData = function() {
    var form = $(selectors.filterForm);
    var data = {
      ajax: true,
      action: "GetExceptions",
      type: this.$tab.find('.active').attr('data-type')
    };
    form.find("input, select").each(function() {
      var val = "";
      if ($(this).is("input")) {
        val = $(this).val();
      }

      if ($(this).is("select")) {
        val = $(this)
          .find("option:selected")
          .val();
      }

      if (val.length > 0) {
        data[$(this).attr("name")] = $(this).val();
      }
    });
    return data;
  };

  Logs.prototype.onChangePage = function(e) {
    e.preventDefault();

    var target = $(e.target);

    if (target.hasClass("next") || target.hasClass("prev")) {
      return false;
    }

    if (target.closest("li").hasClass("disabled") || target.closest("li").hasClass("active")) {
      return false;
    }

    var page = target.text();

    var data = this.getAjaxData();
    data["page"] = page;

    this.getException(data);
  };

  Logs.prototype.onNext = function(e) {
    e.preventDefault();
    var target = $(e.target);
    if (target.closest("li").hasClass("disabled")) {
      return false;
    }
    var current_page = parseInt(this.$table.attr("data-current-page"));
    var page = current_page + 1;

    var data = this.getAjaxData();
    data["page"] = page;

    this.getException(data);
  };

  Logs.prototype.onPrev = function(e) {
    e.preventDefault();
    var target = $(e.target);
    if (target.closest("li").hasClass("disabled")) {
      return false;
    }
    var current_page = parseInt(this.$table.attr("data-current-page"));
    var page = current_page - 1;
    var data = this.getAjaxData();
    data["page"] = page;
    this.getException(data);
  };

  Logs.prototype.getException = function(data) {
    var self = this;
    $.ajax({
      url: ezdefiAdminUrl,
      method: "post",
      data: data,
      beforeSend: function() {
        self.$table
          .find("tbody tr")
          .not(".spinner-row")
          .remove();
        self.$table.find("tbody tr.spinner-row").show();
        self.$pagination.hide();
      },
      success: function(response) {
        self.$table.find("tbody tr.spinner-row").hide();
        self.renderHtml.call(self, response.data);
        self.renderPagination.call(self, response.data.meta_data);
      }
    });
  };

  Logs.prototype.renderHtml = function(data) {
    var self = this;
    var rows = data["data"];
    this.$table.find('tbody tr').not('.spinner-row').remove();
    if (rows.length === 0) {
      self.$table.append("<tr><td colspan='5'>Not found</td></tr>");
    }
    for (var i = 0; i < rows.length; i++) {
      var number = i + 1 + data["meta_data"]["per_page"] * (data["meta_data"]["current_page"] - 1);
      var row = rows[i];
      var status;
      var payment_method;
      switch (row["status"]) {
        case "not_paid":
          status = "Not paid";
          break;
        case "expired_done":
          status = "Paid after expired";
          break;
        case "done":
          status = "Paid on time";
          break;
      }
      switch (row["payment_method"]) {
        case "amount_id":
          payment_method = "Pay with any crypto wallet";
          break;
        case "ezdefi_wallet":
          payment_method = "<strong>Pay with ezDeFi wallet</strong>";
          break;
      }
      var html = $(
        "<tr>" +
          "<td>" +
          number +
          "</td>" +
          "<td class='amount-id-column'>" +
          "<span>" +
          row["amount_id"] * 1 +
          "</span>" +
          "<input type='hidden' class='amount-id-input' value='" +
          row["amount_id"] +
          "' >" +
          "<input type='hidden' class='exception-id-input' value='" + row['id'] + "' >" +
          "</td>" +
          "<td>" +
          "<span class='symbol'>" +
          row["currency"] +
          "</span>" +
          "<input type='hidden' class='currency-input' value='" +
          row["currency"] +
          "' >" +
          "</td>" +
          "<td class='order-column'>" +
          "<input type='hidden' class='old-order-id-input' value='" +
          (row["order_id"] ? row["order_id"] : "") +
          "' >" +
          "<input type='hidden' class='order-id-input' value='" +
          (row["order_id"] ? row["order_id"] : "") +
          "' >" +
          "<div class='saved-order'>" +
          "<div>Order ID: <span id='saved-order-id'>" +
          row["order_id"] +
          "</span></div>" +
          "<div>Email: " +
          row["email"] +
          "</div>" +
          "<div>Status: " +
          status +
          "</div>" +
          "<div>Payment method: " +
          payment_method +
          "</div>" +
          "</div>" +
          "<div class='select-order' style='display: none'>" +
          "<select name='' id=''></select>" +
          "</div>" +
          "<div class='actions'>" +
          "<a href='' class='showSelectBtn button'>Assign to different order</a>" +
          "<a href='' class='hideSelectBtn button' style='display: none'>Cancel</a>" +
          "</div>" +
          "</td>" +
          "</tr>"
      );
      if (row["explorer_url"] && row["explorer_url"].length > 0) {
        var explore = $(
          "<a target='_blank' class='explorer-url' href='" + row["explorer_url"] + "'>View Transaction Detail</a>"
        );
        html.find("td.amount-id-column").append(explore);
      } else {
        html.find('td.order-column .actions').remove();
        html.find('td.order-column .select-order').remove();
      }

      if (row["order_id"] == null) {
        html.find("td.order-column .saved-order, td.order-column .actions").remove();
        html.find("td.order-column .select-order").show();
        self.initSelect2.call(self, html.find("td.order-column select"));
      }

      var last_td;

      if (row['confirmed'] == 1) {
        html.find('td.order-column .actions').remove();
        html.find('td.order-column .select-order').remove();
        last_td = $(
          "<td>" +
            "<button class='btn btn-primary reverseBtn'>Reverse</button> " +
            "<button class='btn btn-danger removeBtn'>Remove</button>" +
            "</td>"
        );
      } else {
        last_td = $(
          "<td>" +
            "<button class='btn btn-primary assignBtn'>Confirm Paid</button> " +
            "<button class='btn btn-danger removeBtn'>Remove</button>" +
            "</td>"
        );
      }
      html.append(last_td);
      html.appendTo(self.$table.find("tbody"));
    }
    self.$table.attr("data-current-page", data["meta_data"]["current_page"]);
    self.$table.attr("data-last-page", data["meta_data"]["total_pages"]);
  };

  Logs.prototype.renderPagination = function(data) {
    var self = this;
    var pagination = "";
    var total_page = data["total_pages"];
    if (total_page == 0) {
      self.$pagination.empty().append(pagination);
      self.$pagination.show();
      return;
    }
    var current_page = parseInt(data["current_page"]);
    if (current_page == 1) {
      pagination += "<li class='disabled'><a href='' class='prev'><</a></li>";
    } else {
      pagination += "<li><a href='' class='prev'><</a></li>";
    }
    if (current_page > 2) {
      pagination += "<li><a href=''>1</a></li>";
    }
    if (current_page > 3) {
      pagination += "<li class='disabled'><a href=''>...</a></li>";
    }
    if (current_page - 1 > 0) {
      pagination += "<li><a href=''>" + (current_page - 1) + "</a></li>";
    }
    pagination += "<li class='active'><a href=''>" + current_page + "</a></li>";
    if (current_page + 1 < total_page) {
      pagination += "<li><a href=''>" + (current_page + 1) + "</a></li>";
    }
    if (current_page < total_page) {
      if (current_page < total_page - 2) {
        pagination += "<li class='disabled'><a href=''>...</a></li>";
      }
      pagination += "<li><a href=''>" + total_page + "</a></li>";
      if (current_page == total_page) {
        pagination += "<li class='disabled'><a href='' class='next'>></a></li>";
      } else {
        pagination += "<li><a href='' class='next'>></a></li>";
      }
    }
    self.$pagination.empty().append(pagination);
    self.$pagination.show();
  };

  Logs.prototype.formatOrderOption = function(order) {
    if (order.loading) {
      return "Loading";
    }

    var $container = $(
      "<div class='select2-order'>" +
        "<div class='select2-order__row'>" +
        "<div class='left'><strong>Order ID:</strong></div>" +
        "<div class='right'>" +
        order["id"] +
        "</div>" +
        "</div>" +
        "<div class='select2-order__row'>" +
        "<div class='left'><strong>Total:</strong></div>" +
        "<div class='right'>" +
        order["total"] * 1 +
        " " +
        order["currency"] +
        "</div>" +
        "</div>" +
        "<div class='select2-order__row'>" +
        "<div class='left'><strong>Email:</strong></div>" +
        "<div class='right'>" +
        order["email"] +
        "</div>" +
        "</div>" +
        "<div class='select2-order__row'>" +
        "<div class='left'><strong>Date:</strong></div>" +
        "<div class='right'>" +
        order["date"] +
        "</div>" +
        "</div>" +
        "</div>"
    );
    return $container;
  };

  Logs.prototype.formatOrderSelection = function(order) {
    return "Order ID: " + order["id"];
  };

  Logs.prototype.onAssign = function(e) {
    e.preventDefault();
    var self = this;
    var row = $(e.target).closest("tr");
    var order_id = row.find(selectors.orderIdInput).val();
    var old_order_id = row.find(selectors.oldOrderIdInput).val();
    var exception_id = row.find(selectors.expceptionIdInput).val();
    var data = {
      ajax: true,
      action: "AssignAmountId",
      order_id: order_id,
      old_order_id: old_order_id,
      exception_id: exception_id
    };
    this.callAjax.call(this, data).success(function() {
      var data = self.getAjaxData();
      self.getException(data);
    });
  };

  Logs.prototype.onReverse = function(e) {
    e.preventDefault();
    var self = this;
    var row = $(e.target).closest("tr");
    var order_id = row.find(selectors.orderIdInput).val();
    var exception_id = row.find(selectors.expceptionIdInput).val();
    var data = {
      ajax: true,
      action: "ReverseOrder",
      order_id: order_id,
      exception_id: exception_id
    };
    this.callAjax.call(this, data).success(function() {
      var data = self.getAjaxData();
      self.getException(data);
    });
  };

  Logs.prototype.onRemove = function(e) {
    e.preventDefault();
    if (!confirm("Do you want to delete this amount ID ?")) {
      return false;
    }
    var self = this;
    var row = $(e.target).closest("tr");
    var exception_id = row.find(selectors.expceptionIdInput).val();
    var data = {
      ajax: true,
      action: "DeleteAmountId",
      exception_id: exception_id
    };
    this.callAjax.call(this, data).success(function() {
      var data = self.getAjaxData();
      self.getException(data);
    });
  };

  Logs.prototype.callAjax = function(data) {
    var self = this;
    return $.ajax({
      url: ezdefiAdminUrl,
      method: "post",
      data: data,
      beforeSend: function() {
        self.$table
          .find("tbody tr")
          .not(".spinner-row")
          .remove();
        self.$table.find("tbody tr.spinner-row").show();
        self.$pagination.hide();
      }
    });
  };

  new Logs();
});
