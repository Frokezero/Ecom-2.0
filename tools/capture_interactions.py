import json
import subprocess
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'presentation'/'website_capture'/'05_interactions'
OUT.mkdir(parents=True,exist_ok=True)
BASE='http://127.0.0.1:8000/'
data=json.loads(subprocess.check_output([r'C:\xampp\php\php.exe',str(ROOT/'tools'/'create_capture_sessions.php')],cwd=ROOT,text=True))
CHROME=r'C:\Program Files\Google\Chrome\Application\chrome.exe'
records=[]
try:
 with sync_playwright() as p:
  browser=p.chromium.launch(executable_path=CHROME,headless=True)
  for role in ('public','customer','seller','admin'):
   context=browser.new_context(viewport={'width':1440,'height':900},device_scale_factor=1,locale='th-TH')
   if role!='public':context.add_cookies([{'name':'PHPSESSID','value':data[role]['session_id'],'url':BASE}])
   page=context.new_page()
   cases={
    'public':[
     ('01_search','products.php?q=กระทะ','ค้นหาสินค้า','พิมพ์คำค้นในช่องค้นหาหรือเปิดผลลัพธ์การค้นหาเพื่อดูสินค้าที่ตรงคำ'),
     ('02_category','products.php?category=1','กรองหมวดสินค้า','เลือกหมวดจากเมนูเพื่อจำกัดรายการสินค้าที่แสดง'),
     ('03_sort_price','products.php?sort=price_asc','เรียงราคาจากน้อยไปมาก','ใช้ตัวเลือกเรียงลำดับในหน้าสินค้าทั้งหมด'),
    ],
    'customer':[
     ('04_checkout_coupon','checkout.php','หน้าต่างเลือกคูปอง','เปิดตัวเลือกคูปองในหน้าชำระเงิน ตรวจเงื่อนไขก่อนใช้'),
     ('05_checkout_cod','checkout.php','ชำระเงินปลายทาง','เลือกตัวเลือก COD ในหน้าชำระเงินก่อนยืนยันออเดอร์'),
    ],
    'seller':[
     ('06_seller_low_stock','seller-inventory.php?stock=low','กรองสินค้าใกล้หมด','ใช้ตัวกรองสินค้าใกล้หมดในคลังสินค้า'),
    ],
    'admin':[
     ('07_admin_pending','admin/orders.php?status=pending','กรองออเดอร์รอดำเนินการ','เลือกสถานะรอดำเนินการในหน้าคำสั่งซื้อแอดมิน'),
    ],
   }[role]
   for stem,path,title,desc in cases:
    page.goto(BASE+path,wait_until='domcontentloaded',timeout=20000)
    page.wait_for_timeout(500)
    close=page.locator('.floating-promo-close')
    if close.count() and close.is_visible():close.click()
    if stem=='04_checkout_coupon':page.locator('[data-coupon-open]').click()
    if stem=='05_checkout_cod':page.locator('.payment-option[data-method="cod"]').click()
    page.screenshot(path=str(OUT/(stem+'.png')),full_page=True,animations='disabled')
    records.append({'file':stem+'.png','title':title,'description':desc,'role':role,'path':path})
    print(stem,flush=True)
   context.close()
  browser.close()
finally:
 for role in ('customer','seller','admin'):
  (ROOT/'.runtime-sessions'/('sess_'+data[role]['session_id'])).unlink(missing_ok=True)
(OUT/'manifest.json').write_text(json.dumps(records,ensure_ascii=False,indent=2),encoding='utf-8')
