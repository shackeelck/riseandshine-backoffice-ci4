<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; }
    .logo { width: 120px; margin-bottom: 8px; }
    .title { text-align: center; font-size: 18px; font-weight: bold; padding: 8px 0; border-bottom: 1px solid #ddd; margin: 12px 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; }
    th { background: #f5f5f5; text-align: left; }
    .right { text-align: right; }
    .muted { color: #555; }
    .totals { margin-top: 10px; width: 40%; margin-left: auto; }
  </style>
</head>
<body>

  <div class="header">
    <div>
      
        <img src="./logo.png" alt="Rise &amp; Shine Logo" class="logo" style ="width:90px;">
      
      <div style="font-size:16px;font-weight:bold;">Rise &amp; Shine Hotel</div>
      <div class="muted">HM LOT NO. 20015</div>
      <div class="muted">Nikagas Magu</div>
      <div class="muted">Maldives</div>
    </div>
    <div class="right">
      <div><b>Proforma No:</b> <?= esc($proformaNo) ?></div>
      <div><b>Invoice Date:</b> <?= esc($p['invoice_date'] ?? '-') ?></div>
      <div><b>Due Date:</b> <?= esc($p['due_date'] ?? '-') ?></div>
      <div><b>Currency:</b> <?= esc($p['currency'] ?? '-') ?></div>
    </div>
  </div>

  <div class="title">PROFORMA INVOICE</div>

  <div style="margin-bottom:10px;">
    <b>To:</b> <?= esc($p['customer_name'] ?? '-') ?><br>
    <?php if (!empty($p['customer_email'])): ?>
      <span class="muted"><?= esc($p['customer_email']) ?></span>
    <?php endif; ?>
  </div>
    
    <?php if (!empty($bookings)): ?>
    <h3 style="margin-top:16px;"> Booking Details</h3>
    <table>
      <thead>
        <tr>
          <th>Booking No</th>
          <th>Booking Date</th>
          <th>Pax Name</th>
          <th>Room Category</th>
          <th>Check-In</th>
          <th>Check-Out</th>
          <th>No. of Nights</th>
          <th>No. of Pax</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= esc($b['booking_no'] ?? '-') ?></td>
            <td><?= esc($b['booking_date'] ?? '-') ?></td>
            <td><?= esc($b['primary_pax_name'] ?? '-') ?></td>
            <td><?= esc($b['booked_room_category'] ?? '-') ?></td>
            <td><?= esc($b['check_in'] ?? '-') ?></td>
            <td><?= esc($b['check_out'] ?? '-') ?></td>
            <td class="right"><?= esc($b['no_of_nights'] ?? 0) ?></td>
            <td class="right"><?= esc($b['pax'] ?? 0) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  
   <h3 style="margin-top:16px;"> Invoice Particulars</h3> 
  <table>
    <thead>
      <tr>
        <th style="width:55%;">Description</th>
        <th style="width:15%;">Unit Type</th>
        <th class="right" style="width:10%;">Units</th>
        <th class="right" style="width:10%;">Unit Price</th>
        <th class="right" style="width:10%;">Line Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $sum = 0; ?>
      <?php foreach ($items as $it): ?>
        <?php $lt = (float)($it['line_total'] ?? 0); $sum += $lt; ?>
        <tr>
          <td><?= esc($it['description'] ?? '') ?></td>
          <td><?= esc($it['unit_type'] ?? '') ?></td>
          <td class="right"><?= esc($it['units'] ?? 0) ?></td>
          <td class="right"><?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
          <td class="right"><?= number_format($lt, 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <th>Total</th>
      <th class="right"><?= number_format((float)($p['total'] ?? $sum), 2) ?></th>
    </tr>
  </table>

  

  <?php if (!empty($defaultBankAccount)): ?>
    <div style="margin-top:16px;">
      <h3 style="margin:0 0 6px 0;">Remittance to be made to</h3>
      <?php /*?><div><b>Bank:</b> <?= esc($defaultBankAccount['bank_name'] ?? '-') ?></div>
      <div><b>Account No:</b> <?= esc($defaultBankAccount['account_no'] ?? '-') ?></div>
      <?php if (!empty($defaultBankAccount['bank_code'])): ?>
        <div><b>Bank Code:</b> <?= esc($defaultBankAccount['bank_code']) ?></div>
      <?php endif; ?>
      <?php if (!empty($defaultBankAccount['currency'])): ?>
        <div><b>Currency:</b> <?= esc($defaultBankAccount['currency']) ?></div>
      <?php endif; ?><?php */?>
      <?php if (!empty($defaultBankAccount['bank_details'])): ?>
        <div> <?= nl2br(esc($defaultBankAccount['bank_details'])) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</body>
</html>
