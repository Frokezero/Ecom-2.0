(() => {
  function selectPayment(option) {
    const root=option.closest('.payment-method-selector')||document;
    root.querySelectorAll('.payment-option').forEach(item=>item.classList.remove('active'));
    option.classList.add('active');
    const method=option.dataset.method;
    const input=document.getElementById('paymentMethodInput');
    if(input)input.value=method;
    const promptpay=document.getElementById('promptPayDetails');
    const cod=document.getElementById('codDetails');
    if(promptpay)promptpay.style.display=method==='promptpay'?'block':'none';
    if(cod)cod.style.display=method==='cod'?'block':'none';
  }

  function initPaymentOptions(scope=document) {
    scope.querySelectorAll('.payment-option').forEach(option=>{
      if(option.dataset.paymentReady)return;
      option.dataset.paymentReady='1';
      option.setAttribute('role','button');
      option.tabIndex=0;
      option.addEventListener('click',()=>selectPayment(option));
      option.addEventListener('keydown',event=>{
        if(event.key==='Enter'||event.key===' '){event.preventDefault();selectPayment(option)}
      });
    });
  }

  window.initPaymentOptions=initPaymentOptions;
  document.addEventListener('DOMContentLoaded',()=>initPaymentOptions());
  document.addEventListener('ajax:page-loaded',()=>initPaymentOptions());
})();
