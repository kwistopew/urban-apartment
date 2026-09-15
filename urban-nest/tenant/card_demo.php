<?php
require_once '../includes/auth.php';
require_role('tenant');
$pageTitle = 'Card Payment Demo';
$pageSubtitle = 'Secure demo payment for your apartment rent.';
$amount = max(0, (float)($_GET['amount'] ?? 0));
$reference = preg_replace('/[^A-Za-z0-9-]/', '', (string)($_GET['ref'] ?? 'DEMO'));
require '../includes/header.php';
?>
<section class="demo-card-page">
  <div class="demo-card-checkout">
    <div class="demo-card-header">
      <div>
        <span class="demo-step">DEMO PAYMENT</span>
        <h2>Pay Rent</h2>
      </div>
      <span class="demo-lock">🔒 Demo</span>
    </div>

    <div class="demo-card-brands" aria-label="Accepted cards">
      <span class="visa-mark">VISA</span>
      <span class="mc-mark"><i></i><b>MASTERCARD</b></span>
    </div>

    <div class="demo-payment-amount">
      <span>Payment amount</span>
      <strong><?= money($amount) ?></strong>
    </div>

    <div class="demo-reference-row">
      <span>Reference</span>
      <strong><?= e($reference) ?></strong>
    </div>

    <div class="demo-warning">Demo only — do not enter a real card. No real charge will be made.</div>

    <form id="demoCardForm" novalidate>
      <div class="demo-form-field">
        <label for="card_name">Name on card</label>
        <input id="card_name" type="text" autocomplete="off" placeholder="Juan Dela Cruz">
      </div>

      <div class="demo-form-field">
        <label for="card_number">Card number</label>
        <input id="card_number" type="text" inputmode="numeric" maxlength="19" autocomplete="off" placeholder="4111 1111 1111 1111">
      </div>

      <div class="demo-form-row">
        <div class="demo-form-field">
          <label for="card_expiry">Expires</label>
          <input id="card_expiry" type="text" inputmode="numeric" maxlength="5" autocomplete="off" placeholder="MM / YY">
        </div>
        <div class="demo-form-field">
          <label for="card_cvv">Security code</label>
          <input id="card_cvv" type="text" inputmode="numeric" maxlength="4" autocomplete="off" placeholder="CVV">
        </div>
      </div>

      <div class="demo-form-field">
        <label for="card_zip">ZIP code</label>
        <input id="card_zip" type="text" inputmode="numeric" maxlength="10" autocomplete="off" placeholder="0000">
      </div>

      <button class="demo-pay-button" type="submit">🔒 Pay <?= money($amount) ?></button>
      <p class="demo-secure-note">This is a simulated payment page for your apartment rental project.</p>
    </form>

    <div class="demo-card-success" id="cardSuccess" hidden>
      <div class="demo-success-icon">✓</div>
      <h3>Payment Successful</h3>
      <p>Your demo card payment has been completed. Return to the payment page.</p>
    </div>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form=document.getElementById('demoCardForm');
  const success=document.getElementById('cardSuccess');
  const number=document.getElementById('card_number');
  const expiry=document.getElementById('card_expiry');
  const cvv=document.getElementById('card_cvv');
  const ref=<?= json_encode($reference) ?>;
  const amount=<?= json_encode(number_format($amount,2,'.','')) ?>;

  number.addEventListener('input',function(){
    const digits=this.value.replace(/\D/g,'').slice(0,16);
    this.value=digits.replace(/(.{4})/g,'$1 ').trim();
  });
  expiry.addEventListener('input',function(){
    let digits=this.value.replace(/\D/g,'').slice(0,4);
    this.value=digits.length>2 ? digits.slice(0,2)+' / '+digits.slice(2) : digits;
  });
  cvv.addEventListener('input',function(){ this.value=this.value.replace(/\D/g,'').slice(0,4); });

  form.addEventListener('submit',function(e){
    e.preventDefault();
    const digits=number.value.replace(/\D/g,'');
    const exp=expiry.value.replace(/\D/g,'');
    const code=cvv.value.replace(/\D/g,'');
    if(!document.getElementById('card_name').value.trim()){ alert('Enter a demo cardholder name.'); return; }
    if(digits.length!==16){ alert('Enter a 16-digit DEMO card number. Do not use a real card.'); return; }
    if(exp.length!==4){ alert('Enter a demo expiry in MM/YY format.'); return; }
    if(code.length<3){ alert('Enter a 3 or 4 digit demo security code.'); return; }
    if(window.opener && !window.opener.closed && typeof window.opener.completeCardDemoFromPopup==='function'){
      window.opener.completeCardDemoFromPopup(ref);
    }
    form.hidden=true;
    success.hidden=false;
  });
});
</script>
<?php require '../includes/footer.php'; ?>
