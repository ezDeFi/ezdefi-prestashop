<div class="panel col-lg-12">
  <div id="">
    <form action="" id="ezdefi-transaction-filter">
      <div class="filter-container">
        <div class="filter-rows">
          <label for="">Amount</label>
          <input type="number" name="amount_id" placeholder="Amount" class="form-control">
        </div>
        <div class="filter-rows">
          <label for="">Currency</label>
          <input type="text" name="currency" placeholder="Currency" class="form-control">
        </div>
        <div class="filter-rows">
          <label for="">Order ID</label>
          <input type="number" name="order_id" placeholder="Order ID" class="form-control">
        </div>
        <div class="filter-rows">
          <label for="">Email</label>
          <input type="text" name="email" placeholder="Email" class="form-control">
        </div>
        <div class="filter-rows">
          <label for="">Payment Method</label>
          <select name="payment_method" id="" class="form-control">
            <option value="" selected="">Any Payment Method</option>
            <option value="ezdefi_wallet">Pay with ezDeFi wallet</option>
            <option value="amount_id">Pay with any crypto wallet</option>
          </select>
        </div>
        <div class="filter-rows">
          <label for="">Status</label>
          <select name="status" id="" class="form-control">
            <option value="" selected="">Any Status</option>
            <option value="expired_done">Paid after expired</option>
            <option value="not_paid">Not paid</option>
            <option value="done">Paid on time</option>
          </select>
        </div>
        <div class="filter-rows">
          <button class="btn btn-primary filterBtn">Search</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="panel col-lg-12">
  <div class="panel-heading">Ezdefi Exceptions</div>
  <table class="table" id="ezdefi-transaction-logs">
    <thead class="thead-default">
    <tr class="column-headers">
      <th><strong>#</strong></th>
      <th><strong>Received Amount</strong></th>
      <th><strong>Currency</strong></th>
      <th><strong>Order</strong></th>
      <th><strong>Actions</strong></th>
    </tr>
    </thead>
    <tbody>
      <tr class="spinner-row" style="display: none;">
        <td colspan="5"><i class="icon-refresh icon-spin icon-fw"></i></td>
      </tr>
    </tbody>
  </table>
  <ul id="ezdefi-logs-pagination" class="pagination"></ul>
</div>
