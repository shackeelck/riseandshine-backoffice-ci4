<form method="post">
<div class="card">
  <div class="card-header">Add/Edit Order</div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Order Ref </label>
        <input type="text" name="order_ref_no" value="<?= $order['order_ref_no'] ?? '' ?>" class="form-control" placeholder="Order No or Contact No or Name" required autocomplete="off">
      </div>
      
      <div class="col-md-4">
        <label class="form-label">Payment Status</label>
        <select name="payment_status" class="form-select">
          <option value="Pay By Credit / Debit Card" <?= isset($order) && $order['payment_status'] === 'Pay By Credit / Debit Card' ? 'selected' : '' ?>>Pay By Credit / Debit Card</option>
          <option value="paid to bank account" <?= isset($order) && $order['payment_status'] === 'paid to bank account' ? 'selected' : '' ?>>Paid to Bank Account</option>
          <option value="cash on delivery" <?= isset($order) && $order['payment_status'] === 'cash on delivery' ? 'selected' : '' ?>>Cash on Delivery</option>
        </select>
      </div>
        
    <div class="col-md-4">
        <label class="form-label">Order Total Amount</label>
        <input type="number" name="total_amount" value="<?=$order['total_amount'] ?? '' ?>" class="form-control" required autocomplete="off">
      </div>
        
     <div class="col-md-4">
        <label class="form-label">Customer Name</label>
        <input type="text" name="customer_name" value="<?=$order['customer_name'] ?? '' ?>" class="form-control" autocomplete="off">
      </div>
        
     <div class="col-md-4">
        <label class="form-label">Contact No</label>
        <input type="text" name="contact_no" value="<?=$order['contact_no'] ?? '' ?>" class="form-control" autocomplete="off">
      </div>
        
       <div class="col-md-4">
        <label class="form-label">Email Address</label>
        <input type="email" name="email_address" value="<?=$order['email_address'] ?? '' ?>" class="form-control" autocomplete="off">
      </div>
        
      <div class="col-md-4">
        <label class="form-label">Delivery Details &amp; Address</label>
        <textarea name="delivery_address" class="form-control" style="height: 180px" ><?= $order['delivery_address'] ?? '' ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Product Details</label>
        <textarea name="product_details" class="form-control" style="height: 180px" ><?= $order['product_details'] ?? '' ?></textarea>
      </div>
      
      
      <div class="col-md-4">
        <label class="form-label">Order Note</label>
        <textarea name="order_note" class="form-control" style="height: 180px" ><?= $order['order_note'] ?? '' ?></textarea>
      </div>
        
        
      <div class="col-md-6">
        <label class="form-label">Launch/Boat Details</label>
        <input type="text" name="launch_details" value="<?= $order['launch_details'] ?? '' ?>" class="form-control" autocomplete="off">
      </div>
        
    </div>
    
  </div>
    <div class="card-footer" >
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i>  Save Order</button>
        <a href="/orders/all" class="btn btn-outline-secondary btn-sm float-end" ><i class="fas fa-times-circle"></i>  Close</a>
    </div>
</div>
</form>