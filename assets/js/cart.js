async function cartRequest(action, productId, quantity) {
    const data=new FormData(); data.append('action',action); data.append('product_id',productId); data.append('csrf_token',CSRF_TOKEN);
    if (quantity !== undefined) data.append('quantity',quantity);
    const response=await fetch(`${BASE_URL}api/cart.php`,{method:'POST',body:data}); const result=await response.json();
    if (!response.ok || result.status!=='success') throw new Error(result.message || 'ไม่สามารถจัดการตะกร้าได้'); return result;
}
async function addToCart(id,qty=1){try{const r=await cartRequest('add',id,qty);updateCartBadge(r.data.total_items);showToast(r.message,'success')}catch(e){showToast(e.message,'error')}}
async function updateCartQuantity(id,qty){try{const r=await cartRequest('update',id,qty);updateCartBadge(r.data.total_items);if(typeof renderCartPage==='function')renderCartPage(r.data);else location.reload();showToast(r.message,'success')}catch(e){showToast(e.message,'error')}}
async function removeFromCart(id){try{const r=await cartRequest('remove',id);updateCartBadge(r.data.total_items);if(typeof renderCartPage==='function')renderCartPage(r.data);else location.reload();showToast(r.message,'success')}catch(e){showToast(e.message,'error')}}
function updateCartBadge(count){const badge=document.getElementById('cartCountBadge');if(badge){badge.textContent=count;badge.style.transform='scale(1.25)';setTimeout(()=>badge.style.transform='scale(1)',200)}}
