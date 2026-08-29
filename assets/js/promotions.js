(function(){
  const box=document.getElementById('floatingPromo'); if(!box) return;
  const closedAt=Number(localStorage.getItem('km_promo_closed_at')||0);if(closedAt&&Date.now()-closedAt<86400000)return;
  const esc=value=>String(value??'').replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
  const url=value=>/^https?:\/\//i.test(value||'')?value:`${BASE_URL}${String(value||'products.php').replace(/^\//,'')}`;
  fetch(`${BASE_URL}api/promotions.php?action=banners&placement=floating`).then(r=>r.json()).then(x=>{
    const b=x?.data?.banners?.[0]; if(!b) return;
    box.hidden=false;
    box.innerHTML=`<button class="floating-promo-close" aria-label="ปิดโปรโมชั่น">×</button><a class="floating-promo-image" href="${esc(url(b.target_url))}"><picture>${b.image_mobile?`<source media="(max-width:620px)" srcset="${esc(url(b.image_mobile))}">`:''}<img src="${esc(url(b.image_desktop))}" alt="${esc(b.title||'โปรโมชั่น')}"></picture></a><span><b>${esc(b.title)}</b>${b.subtitle?`<small>${esc(b.subtitle)}</small>`:''}${b.coupon_id?`<button type="button" class="floating-promo-claim" data-coupon="${Number(b.coupon_id)}">${esc(b.button_label||'รับคูปอง')} <i class="fa-solid fa-ticket"></i></button>`:`<a class="floating-promo-link" href="${esc(url(b.target_url))}">${esc(b.button_label||'ดูโปรโมชั่น')} <i class="fa-solid fa-arrow-right"></i></a>`}</span>`;
    box.querySelector('.floating-promo-close').addEventListener('click',()=>{box.hidden=true;localStorage.setItem('km_promo_closed_at',String(Date.now()));});
    box.querySelector('.floating-promo-claim')?.addEventListener('click',async event=>{const button=event.currentTarget;button.disabled=true;const body=new URLSearchParams({action:'claim',coupon_id:button.dataset.coupon,csrf_token:CSRF_TOKEN});const response=await fetch(`${BASE_URL}api/promotions.php`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});const result=await response.json();if(response.status===401){location.href=`${BASE_URL}login.php`;return;}if(result.status==='success'){button.textContent='รับคูปองแล้ว';button.classList.add('claimed');if(typeof showToast==='function')showToast(result.message,'success');}else{button.disabled=false;if(typeof showToast==='function')showToast(result.message||'รับคูปองไม่ได้','error');}});
  }).catch(()=>{});
})();
