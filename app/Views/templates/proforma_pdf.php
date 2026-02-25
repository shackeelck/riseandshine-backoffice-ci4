<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; }
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

</body>
</html>
