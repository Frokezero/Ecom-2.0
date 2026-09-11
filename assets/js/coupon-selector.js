(function(){
  const money=value=>`฿${Number(value||0).toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
  function initCouponSelectors(scope=document){
    scope.querySelectorAll('[data-coupon-selector]').forEach(root=>{
      if(root.dataset.couponInitialized==='1')return;
      root.dataset.couponInitialized='1';
      const total=document.getElementById(root.dataset.totalTarget),discount=document.getElementById(root.dataset.discountTarget),discountRow=document.getElementById(root.dataset.discountRow),shipping=document.getElementById(root.dataset.shippingTarget),tax=document.getElementById(root.dataset.taxTarget);
      let baseTotal=Number(root.dataset.baseTotal||root.dataset.subtotal||0),baseShipping=Number(root.dataset.baseShipping||0),baseTax=Number(root.dataset.baseTax||0);
      const slots={};
      root.querySelectorAll('[data-coupon-slot]').forEach(box=>{const type=box.dataset.couponSlot;slots[type]={select:box.querySelector('[data-coupon-select]'),input:box.querySelector('[data-coupon-input]'),button:box.querySelector('[data-coupon-apply]'),message:box.querySelector('[data-coupon-message]')};});
      const modal=root.querySelector('[data-coupon-modal]'),summary=root.querySelector('[data-coupon-summary]');
      const updateSummary=()=>{const codes=Object.entries(slots).filter(([,slot])=>slot.select.value).map(([type,slot])=>`${type==='shipping'?'ส่งฟรี':'ส่วนลด'}: ${slot.select.value}`);if(summary)summary.textContent=codes.length?codes.join(' · '):'เลือกส่วนลดสินค้าและค่าจัดส่ง';};
      const syncPayable=value=>document.querySelectorAll('[data-payable-total]').forEach(el=>el.textContent=money(value));
      const renderTotals=data=>{if(discount)discount.textContent=`-${money(data.discount)}`;if(discountRow)discountRow.style.display=Number(data.discount)>0?'flex':'none';if(shipping)shipping.textContent=Number(data.shipping_amount)>0?money(data.shipping_amount):'ฟรี';if(tax)tax.textContent=money(data.tax_amount);if(total)total.textContent=money(data.total);syncPayable(data.total);};
      const resetTotals=()=>renderTotals({discount:0,shipping_amount:baseShipping,tax_amount:baseTax,total:baseTotal});
      const post=async params=>{const body=new URLSearchParams({...params,csrf_token:CSRF_TOKEN});const response=await fetch(`${BASE_URL}api/promotions.php`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});return {response,result:await response.json()};};
      const setMessage=(slot,text,error=false)=>{slot.message.textContent=text;slot.message.style.color=error?'#b83b32':'#28704a';};
      const useCode=async(type,code)=>{const slot=slots[type];code=String(code||'').trim();if(!code)return clearCoupon(type);setMessage(slot,'กำลังตรวจสอบคูปอง...');const {response,result}=await post({action:'validate',code,slot:type});if(response.status===401){setMessage(slot,'เข้าสู่ระบบเพื่อใช้คูปอง',true);return;}if(result.status!=='success'){setMessage(slot,result.message||'ใช้คูปองไม่ได้',true);return;}slot.input.value=result.data.code;slot.select.value=result.data.code;setMessage(slot,`ใช้ ${result.data.code} แล้ว`);renderTotals(result.data);updateSummary();};
      const clearCoupon=async type=>{const slot=slots[type];const {result}=await post({action:'clear',slot:type}).catch(()=>({result:null}));slot.select.value='';slot.input.value='';setMessage(slot,'ยังไม่ได้ใช้คูปอง');if(result?.status==='success')renderTotals(result.data);else resetTotals();updateSummary();};
      Object.entries(slots).forEach(([type,slot])=>{slot.select.addEventListener('change',()=>slot.select.value?useCode(type,slot.select.value):clearCoupon(type));slot.button.addEventListener('click',()=>slot.input.value.trim()?useCode(type,slot.input.value):clearCoupon(type));});
      root.querySelector('[data-coupon-open]')?.addEventListener('click',()=>{modal?.classList.add('active');document.body.style.overflow='hidden';});
      root.querySelectorAll('[data-coupon-close],[data-coupon-confirm]').forEach(button=>button.addEventListener('click',()=>{modal?.classList.remove('active');document.body.style.overflow='';updateSummary();}));
      modal?.addEventListener('click',event=>{if(event.target===modal){modal.classList.remove('active');document.body.style.overflow='';}});
      root.addEventListener('cart:updated',event=>{baseTotal=Number(event.detail?.total||0);baseShipping=Number(event.detail?.shipping||0);baseTax=Number(event.detail?.tax||0);const active=Object.entries(slots).find(([,slot])=>slot.select.value||slot.input.value);active?useCode(active[0],active[1].select.value||active[1].input.value):resetTotals();});
      fetch(`${BASE_URL}api/promotions.php?action=mine`).then(async response=>({response,result:await response.json()})).then(({response,result})=>{if(response.status===401){Object.values(slots).forEach(slot=>{slot.select.hidden=true;setMessage(slot,'เข้าสู่ระบบเพื่อเลือกคูปอง',true);});return;}const coupons=result?.data?.coupons||[];Object.entries(slots).forEach(([type,slot])=>{coupons.filter(c=>type==='shipping'?c.discount_type==='free_shipping':c.discount_type!=='free_shipping').forEach(coupon=>{const option=document.createElement('option');option.value=coupon.code;const benefit=coupon.discount_type==='percent'?`ลด ${Number(coupon.discount_value)}%`:`ลด ${money(coupon.discount_value)}`;option.textContent=`${coupon.title} · ${type==='shipping'?'ส่งฟรี':benefit} · ${coupon.code}`;slot.select.append(option);});const code=result?.data?.selected_slots?.[type]||'';if(code){slot.select.value=code;slot.input.value=code;setMessage(slot,`ใช้ ${code} แล้ว`);}else setMessage(slot,'ยังไม่ได้ใช้คูปอง');});updateSummary();const active=Object.entries(slots).reverse().find(([,slot])=>slot.select.value);active?useCode(active[0],active[1].select.value):resetTotals();}).catch(()=>Object.values(slots).forEach(slot=>setMessage(slot,'โหลดคูปองไม่สำเร็จ',true)));
    });
  }
  window.initCouponSelectors=initCouponSelectors;
  initCouponSelectors();
})();
