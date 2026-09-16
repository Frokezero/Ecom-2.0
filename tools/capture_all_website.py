from __future__ import annotations
import json
import os
import subprocess
from pathlib import Path
from urllib.parse import urlparse
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'presentation' / 'website_capture'
BASE = 'http://127.0.0.1:8000/'
CHROME = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
PHP = r'C:\xampp\php\php.exe'

data = json.loads(subprocess.check_output([PHP, str(ROOT / 'tools' / 'create_capture_sessions.php')], cwd=ROOT, text=True))
pid, spid, oid, coid, sid = (data[x] for x in ('product_id','seller_product_id','order_id','customer_order_id','seller_id'))

sections = {
 '01_public': [
  ('01_home','index.php','หน้าแรก','แบนเนอร์ เมนูค้นหา หมวดสินค้า โปรโมชัน และจุดเริ่มต้นการเลือกซื้อ'),
  ('02_products','products.php','สินค้าทั้งหมด','รายการสินค้า ตัวกรองหมวด ช่วงราคา และตัวเลือกการเรียง'),
  ('03_product_detail',f'product-detail.php?id={pid}','รายละเอียดสินค้า','ภาพ ราคา ตัวเลือก จำนวน รีวิว และปุ่มเพิ่มตะกร้า'),
  ('04_shop',f'shop.php?id={sid}','หน้าร้านผู้ขาย','ข้อมูลร้าน รายการสินค้า และทางติดต่อผู้ขาย'),
  ('05_cart_guest','cart.php','ตะกร้าผู้เยี่ยมชม','การแสดงตะกร้าก่อนเข้าสู่ระบบหรือก่อนเพิ่มสินค้า'),
  ('06_login','login.php','เข้าสู่ระบบ','แบบฟอร์มชื่อผู้ใช้หรืออีเมลและรหัสผ่าน'),
  ('07_register','register.php','สมัครสมาชิก','แบบฟอร์มสร้างบัญชีลูกค้า'),
  ('08_seller_register','seller-register.php','สมัครผู้ขาย','แบบฟอร์มสร้างบัญชีผู้ขาย'),
  ('09_forgot_password','forgot-password.php','ลืมรหัสผ่าน','แบบฟอร์มขอลิงก์ตั้งรหัสผ่านใหม่'),
  ('09_check_email','check-email.php','ตรวจสอบอีเมล','คำแนะนำหลังสมัครและปุ่มส่งลิงก์ยืนยันใหม่'),
  ('10_privacy','privacy.php','นโยบายความเป็นส่วนตัว','ข้อมูลนโยบายและสิทธิ์ข้อมูลส่วนบุคคล'),
 ],
 '02_customer': [
  ('01_profile','profile.php','โปรไฟล์ลูกค้า','ข้อมูลบัญชีและการจัดการข้อมูลส่วนตัว'),
  ('02_address_book','address-book.php','สมุดที่อยู่','ที่อยู่จัดส่งและการเพิ่มหรือแก้ไขที่อยู่'),
  ('03_wishlist','wishlist.php','รายการโปรด','สินค้าที่บันทึกไว้สำหรับกลับมาดู'),
  ('04_cart','cart.php','ตะกร้าสินค้า','รายการในตะกร้า จำนวนสินค้า และยอดรวม'),
  ('05_checkout','checkout.php','ชำระเงิน','ที่อยู่จัดส่ง คูปอง ยอดรวม และวิธีชำระเงิน'),
  ('06_coupons','my-coupons.php','คูปองของฉัน','คูปองที่รับแล้วและเงื่อนไขการใช้งาน'),
  ('07_orders','my-orders.php','คำสั่งซื้อของฉัน','ประวัติคำสั่งซื้อและสถานะ'),
  ('08_order_detail',f'order-detail.php?id={coid}','รายละเอียดคำสั่งซื้อ','รายการสินค้า การชำระเงิน การจัดส่ง และสถานะ'),
  ('08_order_success',f'order-success.php?order_id={coid}','หน้าสั่งซื้อสำเร็จ','สรุปคำสั่งซื้อที่มีอยู่ของบัญชีหลังยืนยันรายการ'),
  ('09_receipt',f'receipt.php?id={coid}','ใบเสร็จ','เอกสารคำสั่งซื้อสำหรับออเดอร์ที่เลือก'),
  ('10_returns','my-returns.php','คำขอคืนสินค้า','ติดตามคำขอคืนและการคืนเงิน'),
  ('11_return_request',f'return-request.php?order_id={coid}','ยื่นคำขอคืนสินค้า','แบบฟอร์มระบุรายการและเหตุผลการคืน'),
  ('12_notifications','notifications.php','การแจ้งเตือน','รายการแจ้งเตือนของบัญชี'),
  ('13_messages','messages.php','ข้อความ','การสนทนากับร้านค้า'),
  ('14_support','support.php','ศูนย์ช่วยเหลือ','การส่งคำถามหรือปัญหาให้ทีมดูแล'),
 ],
 '03_seller': [
  ('01_dashboard','seller-dashboard.php','แดชบอร์ดผู้ขาย','ภาพรวมยอดขาย งานค้าง และทางลัดเพิ่มสินค้า'),
  ('02_store','seller.php','ตั้งค่าร้าน','ข้อมูลร้านและสถานะบัญชีผู้ขาย'),
  ('02_my_store','my-store.php','ร้านค้าของฉัน','ข้อมูลหน้าร้าน แบนเนอร์ โปรโมชัน และการจัดการร้าน'),
  ('03_marketplace','seller-marketplace.php','จัดการสินค้าผู้ขาย','รายการสินค้าที่ลงขายและสถานะการอนุมัติ'),
  ('04_inventory','seller-inventory.php','สต็อกสินค้า','จำนวนคงเหลือและการปรับสต็อก'),
  ('05_orders','seller-orders.php','คำสั่งซื้อของร้าน','ออเดอร์ที่ต้องดำเนินการและสถานะการจัดส่ง'),
  ('06_wallet','seller-wallet.php','กระเป๋าเงินผู้ขาย','ยอดเงิน รายการรับเงิน และการถอนเงิน'),
  ('07_product_options',f'seller-product-options.php?id={spid}','ตัวเลือกสินค้า','ตัวเลือกหรือรุ่นย่อยของสินค้าที่เลือก'),
 ],
 '04_admin': [
  ('01_dashboard','admin/index.php','แดชบอร์ดแอดมิน','ตัวเลขภาพรวมและงานที่ต้องติดตาม'),
  ('02_products','admin/products.php','สินค้าของเว็บไซต์','เพิ่ม แก้ไข และตรวจรายการสินค้าหลัก'),
  ('03_promotions','admin/promotions.php','โปรโมชันและคูปอง','แคมเปญส่วนลดและคูปอง'),
  ('04_mall_promotions','admin/mall-promotions.php','โปรโมชัน MALL','กิจกรรมส่งเสริมการขายระดับเว็บไซต์'),
  ('05_orders','admin/orders.php','คำสั่งซื้อ','รายการออเดอร์ทั้งหมดและตัวกรองสถานะ'),
  ('06_order_detail',f'admin/order-detail.php?id={oid}','รายละเอียดออเดอร์','ข้อมูลออเดอร์ การชำระเงิน และการจัดส่ง'),
  ('07_returns','admin/returns.php','คืนสินค้าและคืนเงิน','รายการคำขอและสถานะการดำเนินการ'),
  ('08_support','admin/support.php','ศูนย์ช่วยเหลือลูกค้า','เคสช่วยเหลือและข้อความจากลูกค้า'),
  ('09_sellers','admin/sellers.php','คำขอผู้ขาย','คำขอสมัครและการอนุมัติผู้ขาย'),
  ('10_stores','admin/stores.php','ร้านค้าและสินค้า','รายการร้านและสถานะร้านค้า'),
  ('11_store_detail',f'admin/store-detail.php?id={sid}','รายละเอียดร้าน','ข้อมูลร้านและรายการสินค้าที่เกี่ยวข้อง'),
  ('12_seller_products','admin/seller-products.php','ตรวจสินค้าผู้ขาย','สินค้ารออนุมัติและการตรวจสอบ'),
  ('13_product_reports','admin/product-reports.php','รายงานสินค้า','รายการสินค้าที่ถูกแจ้งรายงาน'),
  ('14_payouts','admin/payouts.php','ถอนเงินผู้ขาย','คำขอถอนและสถานะการจ่ายเงิน'),
  ('15_email_logs','admin/email-logs.php','ประวัติอีเมล','สถานะการส่งอีเมลธุรกรรม'),
  ('16_security','admin/security-center.php','ศูนย์ความปลอดภัย','เหตุการณ์และกฎความปลอดภัย'),
  ('17_analytics','admin/behavior-analytics.php','Behavior Analytics','ข้อมูลพฤติกรรมการใช้งาน'),
  ('18_users','admin/users.php','ผู้ใช้งานทั้งหมด','รายการผู้ใช้และบทบาท'),
 ],
}

records = []
selected_section = os.environ.get('CAPTURE_SECTION')
if selected_section:
 sections = {k:v for k,v in sections.items() if k == selected_section}
try:
 with sync_playwright() as p:
  browser = p.chromium.launch(executable_path=CHROME, headless=True)
  for section, pages in sections.items():
   context = browser.new_context(viewport={'width': 1440, 'height': 900}, device_scale_factor=1, locale='th-TH')
   role = {'02_customer':'customer','03_seller':'seller','04_admin':'admin'}.get(section)
   if role:
    context.add_cookies([{'name':'PHPSESSID','value':data[role]['session_id'],'url':BASE}])
   page = context.new_page()
   folder = OUT / section
   folder.mkdir(parents=True, exist_ok=True)
   for stem, path, title, desc in pages:
    url = BASE + path
    status = 'captured'
    try:
     response = page.goto(url, wait_until='domcontentloaded', timeout=20000)
     page.wait_for_timeout(500)
     promo_close=page.locator('.floating-promo-close')
     if section == '01_public' and stem == '01_home' and promo_close.count() and promo_close.is_visible():
      page.screenshot(path=str(folder / '01_home_promo.png'), full_page=False, animations='disabled')
     if promo_close.count() and promo_close.is_visible():
      promo_close.click()
     final = page.url
     body = page.locator('body').inner_text(timeout=5000)[:500]
     if 'Fatal error' in body or 'Parse error' in body: status = 'error'
     elif urlparse(final).path != urlparse(url).path: status = 'redirect'
     elif response and response.status >= 400: status = f'HTTP {response.status}'
     if status == 'captured':
      page.screenshot(path=str(folder / f'{stem}.png'), full_page=True, animations='disabled')
    except Exception as exc:
     final = page.url
     status = 'capture_failed'
    records.append({'section':section,'file':f'{stem}.png' if status=='captured' else None,'path':path,'title':title,'description':desc,'status':status,'final_url':final})
    print(section,stem,status,flush=True)
   context.close()
  browser.close()
finally:
 for role in ('customer','seller','admin'):
  sid_val=data.get(role,{}).get('session_id')
  if sid_val:
   (ROOT / '.runtime-sessions' / ('sess_' + sid_val)).unlink(missing_ok=True)

OUT.mkdir(parents=True, exist_ok=True)
if selected_section and (OUT/'manifest.json').exists():
 previous=json.loads((OUT/'manifest.json').read_text(encoding='utf-8'))
 records=[r for r in previous if r['section'] != selected_section] + records
(OUT/'manifest.json').write_text(json.dumps(records,ensure_ascii=False,indent=2),encoding='utf-8')
print('TOTAL',len(records),'CAPTURED',sum(r['status']=='captured' for r in records))
