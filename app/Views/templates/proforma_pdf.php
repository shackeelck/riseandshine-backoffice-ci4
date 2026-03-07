<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; }
    th { background: #f5f5f5; text-align: left; }
    .right { text-align: right; }
    .muted { color: #555; }
    .title { text-align: center; font-size: 18px; font-weight: bold; padding: 8px 0; border-bottom: 1px solid #ddd; margin: 12px 0; }
    .logo { width: 90px; margin-bottom: 8px; }

    .header-table,
    .header-table td,
    .bank-seal-table,
    .bank-seal-table td {
      border: none;
      padding: 0;
    }

    .header-table td { vertical-align: top; }
    .bank-seal-table { margin-top: 16px; }
    .bank-seal-table td { vertical-align: top; }

    .totals { margin-top: 10px; width: 40%; margin-left: auto; }
    .seal { width: 140px; max-width: 100%; }
  </style>
</head>
<body>

  <table class="header-table">
    <tr>
      <td style="width:60%;">
        <?php if (!empty($logoPath)): ?>
          <img src="<?= esc($logoPath) ?>" alt="Rise &amp; Shine Logo" class="logo">
        <?php endif; ?>
        <div style="font-size:16px;font-weight:bold;">Rise &amp; Shine Hotel</div>
        <div class="muted">HM LOT NO. 20015</div>
        <div class="muted">Nikagas Magu</div>
        <div class="muted">Maldives</div>
      </td>
      <td style="width:40%;" class="right">
        <div><b>Proforma No:</b> <?= esc($proformaNo) ?></div>
        <div><b>Invoice Date:</b> <?= esc($p['invoice_date'] ?? '-') ?></div>
        <div><b>Due Date:</b> <?= esc($p['due_date'] ?? '-') ?></div>
        <div><b>Currency:</b> <?= esc($p['currency'] ?? '-') ?></div>
      </td>
    </tr>
  </table>

  <div class="title">PROFORMA INVOICE</div>

  <div style="margin-bottom:10px;">
    <b>To:</b> <?= esc($p['customer_name'] ?? '-') ?><br>
    <?php if (!empty($p['customer_email'])): ?>
      <span class="muted"><?= esc($p['customer_email']) ?></span>
    <?php endif; ?>
  </div>

  <?php if (!empty($bookings)): ?>
    <h3 style="margin-top:16px;">Booking Details</h3>
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

  <h3 style="margin-top:16px;">Invoice Particulars</h3>
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
          <td class="right"><?= esc($it['qty'] ?? 0) ?></td>
          <td class="right"><?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
          <td class="right"><?= number_format($it['line_total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php $invoiceTotal = (float)($p['total'] ?? $sum); ?>
  <table class="totals">
    <tr>
      <th>Total</th>
      <th class="right"><?= number_format($invoiceTotal, 2) ?></th>
    </tr>
  </table>

  <div style="margin-top:8px;">
    <b>Amount in Words:</b> <?= esc(amount_to_words($invoiceTotal, (string)($p['currency'] ?? 'USD'))) ?>
  </div>

  <table class="bank-seal-table">
    <tr>
      <td style="width:70%; padding-right:10px;">
        <h3 style="margin:0 0 6px 0;">Remittance to be made to</h3>
        <?php if (!empty($defaultBankAccount['bank_details'])): ?>
          <div><?= nl2br(esc($defaultBankAccount['bank_details'])) ?></div>
        <?php else: ?>
          <div class="muted">-</div>
        <?php endif; ?>
      </td>
      <td style="width:30%; text-align:right;">
        <?php if (!empty($sealPath)): ?>
          <img src="<?= esc($sealPath) ?>" alt="Company Seal" class="seal">
        <?php endif; ?>
      </td>
    </tr>
  </table>

</body>
</html>
