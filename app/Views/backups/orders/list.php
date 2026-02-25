<style>
    tr.order_received td {
        
    }
    tr.out_for_delivery td { 
         background: wheat;
    }
    
    tr.delivered td { 
        background: darkseagreen;
    }
</style>
<div class="row g-1" >
    <div class="col-md-6"><h3 class="h6 bold">All Orders</h3></div>
    <div class="col-md-6"><a href="<?=site_url('orders/add');?>" class="btn btn-primary float-end mb-3" ><i class="fa fa-plus-circle" ></i> Add Orders</a></div>
</div>


<table class="table table-bordered">
  <thead class="table-dark">
    <tr>
      <th class="py-1" >#</th>
      <th class="py-1" >Ref No</th>
      <th class="py-1" >Contact </th>
      <th class="py-1" >Address </th>
      <th class="py-1" >Order Details</th>
      
      <th class="py-1" >Status</th>
     
      <th class="py-1" >Created</th>  
      <th class="py-1" ></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($orders as $index => $order): ?>
      <tr class="<?=$order['status']?> small">
        <td class="py-1" ><?=$index+1; ?></td>
        <td class="py-1"><?= esc($order['order_ref_no']); ?></td>
        <td class="py-1"><i class="fa fa-phone fa-sm" ></i>  <?php echo $order['contact_no']; ?></td>  
        <td class="py-1" ><?php echo nl2br($order['delivery_address']); ?>
          <?php echo isset($order['order_note'])?'<br><span style="background:yellow">'.nl2br($order['order_note'].'</span>'):''; ?>
          
          </td>
        <td class="py-1 <?=($order['payment_status'] === 'Pay By Credit / Debit Card')?'text-success':'text-danger'?>">
            MVR : <span class="fw-bold"><?php echo $order['total_amount']; ?></span><br>
            <i class="fa fa-credit-card-alt fa-sm" ></i> :  <strong class="small "><?php echo $order['payment_status']; ?></strong>
          
          </td>
        
        <td class="py-1">
          <form method="post" action="/orders/updateStatus/<?= $order['id'] ?>">
            <?php //if($order['status'] != 'delivered') { ?>  
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="order_received" <?php if($order['status'] == 'order_received') echo 'selected'; ?>>Order Received</option>
              <option value="out_for_delivery" <?php if($order['status'] == 'out_for_delivery') echo 'selected'; ?>>Out for Delivery</option>
              <option value="delivered" <?php if($order['status'] == 'delivered') echo 'selected'; ?>>Delivered</option>
            </select>
            <?php //}else{ ?>
    
           <?php //} ?>
          </form>
        </td>
          
        <td class="py-1">
            <i class="fa fa-calendar"></i> : <?php echo date('d.M.Y',strtotime($order['created_at'])); ?><br>
            <i class="fa fa-user-circle"></i> :  <?php echo $order['created_source']; ?>
          
        </td>
        <td class="py-1">
            <?php if($order['status'] == 'order_received') { ?>
              <a href="<?php echo base_url('orders/edit/'.$order['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-sm fa-pencil"></i> </a>
            <?php }?>
        </td>
          
        
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>