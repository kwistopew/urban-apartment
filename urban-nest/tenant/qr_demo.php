<?php
require_once '../includes/auth.php';
require_role('tenant');
$pageTitle = 'QR Payment Demo';
$pageSubtitle = 'Demo payment page for the apartment rental system.';
$amount = max(0, (float)($_GET['amount'] ?? 0));
$reference = preg_replace('/[^A-Za-z0-9-]/', '', (string)($_GET['ref'] ?? 'DEMO'));
require '../includes/header.php';
?>
<section class="panel demo-qr-page">
  <div class="demo-step">GCASH-STYLE DEMO • NO REAL PAYMENT</div>
  <h2>Complete Demo QR Payment</h2>
  <p class="demo-note">This page is only for demonstrating the payment flow. It does not connect to GCash or any real financial service.</p>
  <div class="demo-qr-summary">
    <div><span>Amount</span><strong><?= money($amount) ?></strong></div>
    <div><span>Reference</span><strong><?= e($reference) ?></strong></div>
  </div>
  <div class="demo-qr-payment-box">
    <div class="demo-qr-code-wrap"><div id="demoQrCode"></div></div>
    <div class="demo-qr-payment-info">
      <div class="demo-gcash-logo">G</div>
      <h3>GCash Demo Payment</h3>
      <p>Scan this QR code or simply press <strong>Pay</strong> below to simulate a successful payment.</p>
      <div class="demo-pay-amount"><span>Amount to Pay</span><strong><?= money($amount) ?></strong></div>
      <button class="btn primary full-btn" type="button" id="demoPayButton">Pay <?= money($amount) ?></button>
      <small class="demo-secure-note">DEMO ONLY • No real money will be charged.</small>
    </div>
  </div>
  <div id="demoSuccess" class="demo-success-box" hidden>
    <div class="demo-success-icon">✓</div>
    <h3>Payment Successful</h3>
    <p>Your demo QR payment has been completed. Return to the payment page.</p>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const ref = <?= json_encode($reference) ?>;
      const amount = <?= json_encode(number_format($amount, 2, '.', '')) ?>;
      const qr = document.getElementById('demoQrCode');
      const pay = document.getElementById('demoPayButton');
      const box = document.getElementById('demoSuccess');
      const paymentBox = document.querySelector('.demo-qr-payment-box');
      const qrText = window.location.href;
      if (window.QRCode && qr) {
        new QRCode(qr, { text: qrText, width: 230, height: 230, correctLevel: QRCode.CorrectLevel.M });
      } else if (qr) {
        const img = document.createElement('img');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=' + encodeURIComponent(qrText);
        img.alt = 'Demo QR code';
        img.width = 230; img.height = 230;
        qr.appendChild(img);
      }
      if (pay) {
        pay.addEventListener('click', function () {
          if (window.opener && !window.opener.closed && typeof window.opener.completeQrDemoFromPopup === 'function') {
            window.opener.completeQrDemoFromPopup(ref);
          }
          if (paymentBox) paymentBox.hidden = true;
          if (box) box.hidden = false;
          pay.disabled = true;
          pay.textContent = 'Payment Completed';
        });
      }
    });
  </script>
</section>
<?php require '../includes/footer.php'; ?>
