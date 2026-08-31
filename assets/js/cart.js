async function cartRequest(action,productId,quantity,variantId=0,cartKey=''){
 const data=new FormData();data.append('action',action);data.append('product_id',productId);data.append('csrf_token',CSRF_TOKEN);if(quantity!==undefined)data.append('quantity',quantity);if(variantId)data.append('variant_id',variantId);if(cartKey)data.append('cart_key',cartKey);
 const response=await fetch(`${BASE_URL}api/cart.php`,{method:'POST',body:data}),result=await response.json();if(!response.ok||result.status!=='success')throw new Error(result.message||'ไม่สามารถจัดการตะกร้าได้');return result;
}
async function addToCart(id,qty=1,variantId=0){try{const r=await cartRequest('add',id,qty,variantId);updateCartBadge(r.data.total_items);renderCartPreview(r.data,true);showToast(r.message,'success')}catch(e){showToast(e.message,'error')}}
async function updateCartQuantity(id,qty,cartKey=''){try{const r=await cartRequest('update',id,qty,0,cartKey);updateCartBadge(r.data.total_items);renderCartPreview(r.data);if(typeof renderCartPage==='function')renderCartPage(r.data);showToast(r.message,'success')}catch(e){showToast(e.message,'error')}}
async function removeFromCart(id,cartKey=''){try{const r=await cartRequest('remove',id,undefined,0,cartKey);updateCartBadge(r.data.total_items);renderCartPreview(r.data);if(typeof renderCartPage==='function')renderCartPage(r.data);showToast(r.message,'success')}catch(e){showToast(e.message,'error')}}
function updateCartBadge(count){const badge=document.getElementById('cartCountBadge');if(badge){badge.textContent=count;badge.style.transform='scale(1.25)';setTimeout(()=>badge.style.transform='scale(1)',200)}}

function cartImageUrl(path){path=String(path||'');return /^https?:\/\//i.test(path)?path:`${BASE_URL}${path.replace(/^\/+/, '')}`}
function cartMoney(value){return new Intl.NumberFormat('th-TH',{style:'currency',currency:'THB',minimumFractionDigits:2}).format(Number(value)||0)}
function cartElement(tag,className,text){const node=document.createElement(tag);if(className)node.className=className;if(text!==undefined)node.textContent=text;return node}
function renderCartPreview(data,reveal=false){
 const dropdown=document.querySelector('.cart-dropdown');if(!dropdown||!data)return;
 let header=dropdown.querySelector(':scope > header');if(!header){header=document.createElement('header');header.append(cartElement('strong','', 'ตะกร้าสินค้าของคุณ'),cartElement('span'));dropdown.prepend(header)}
 const count=Number(data.total_items)||0;const countText=header.querySelector('span');if(countText)countText.textContent=`${count} ชิ้น`;
 Array.from(dropdown.children).forEach(child=>{if(child!==header)child.remove()});
 const items=Array.isArray(data.items)?data.items:[];
 if(!items.length){const empty=cartElement('div','cart-preview-empty');const icon=cartElement('i','fa-solid fa-basket-shopping');const title=cartElement('strong','', 'ตะกร้ายังว่างอยู่');const detail=cartElement('p','', 'เลือกของดีเข้าครัวได้เลย');const shop=cartElement('a','', 'เลือกซื้อสินค้า');shop.href=`${BASE_URL}products.php`;empty.append(icon,title,detail,shop);dropdown.append(empty)}else{
  const list=cartElement('div','cart-preview-list');items.slice(0,3).forEach(item=>{const link=cartElement('a','cart-preview-item');link.href=`${BASE_URL}product-detail.php?id=${encodeURIComponent(item.id)}`;const img=document.createElement('img');img.src=cartImageUrl(item.image_url);img.alt=String(item.name||'สินค้า');const details=document.createElement('span');details.append(cartElement('strong','',String(item.name||'สินค้า')),cartElement('small','',`${Number(item.quantity)||0} ชิ้น · ${item.formatted_price||cartMoney(item.price)}`));link.append(img,details,cartElement('b','',item.formatted_subtotal||cartMoney(item.subtotal)));list.append(link)});dropdown.append(list);
  if(items.length>3)dropdown.append(cartElement('p','cart-more-items',`และอีก ${items.length-3} รายการ`));const total=cartElement('div','cart-preview-total');total.append(cartElement('span','', 'ยอดรวม'),cartElement('strong','',data.formatted_grand_total||cartMoney(data.grand_total)));dropdown.append(total);const actions=cartElement('div','cart-dropdown-actions');const view=cartElement('a','cart-view-link','ดูตะกร้าสินค้า');view.href=`${BASE_URL}cart.php`;const checkout=cartElement('a','cart-checkout-link','ชำระเงิน →');checkout.href=`${BASE_URL}checkout.php`;actions.append(view,checkout);dropdown.append(actions)
 }
 if(reveal){const menu=dropdown.closest('.cart-menu');menu?.classList.add('cart-preview-updated');setTimeout(()=>menu?.classList.remove('cart-preview-updated'),1800)}
}
async function refreshCartPreview(){try{const response=await fetch(`${BASE_URL}api/cart.php?action=get`,{headers:{Accept:'application/json'}});const result=await response.json();if(response.ok&&result.status==='success'){updateCartBadge(result.data.total_items);renderCartPreview(result.data)}}catch(error){/* Keep the last usable preview when offline. */}}
document.addEventListener('DOMContentLoaded',()=>{const menu=document.querySelector('.cart-menu');if(menu)menu.addEventListener('pointerenter',refreshCartPreview)});
