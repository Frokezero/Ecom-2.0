import json
import html
from collections import defaultdict
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'presentation'/'website_capture'
main=json.loads((OUT/'manifest.json').read_text(encoding='utf-8'))
extra=json.loads((OUT/'05_interactions'/'manifest.json').read_text(encoding='utf-8'))
names={
 '01_public':'01 เว็บสำหรับผู้เยี่ยมชม',
 '02_customer':'02 บัญชีลูกค้า',
 '03_seller':'03 ระบบผู้ขาย',
 '04_admin':'04 ระบบแอดมิน',
 '05_interactions':'05 ตัวอย่างการใช้งานย่อย',
}
intro={
 '01_public':'เริ่มจากหน้าแรก ค้นหาและอ่านข้อมูลสินค้า ก่อนสมัครหรือเข้าสู่ระบบ ภาพในโฟลเดอร์นี้เปิดได้โดยไม่ใช้บัญชี',
 '02_customer':'ใช้บัญชีลูกค้าเพื่อจัดการข้อมูลส่วนตัว ตะกร้า การชำระเงิน คำสั่งซื้อ และบริการหลังการขาย ภาพตะกร้าและชำระเงินใช้รายการสินค้าในเซสชันสำหรับถ่ายภาพโดยไม่มีการสร้างออเดอร์ใหม่',
 '03_seller':'ใช้บัญชีผู้ขายที่มีร้านได้รับอนุมัติแล้ว เพื่อแสดงหน้าจัดการร้าน สินค้า สต็อก ออเดอร์ และการเงิน',
 '04_admin':'ใช้เซสชันภายในเครื่องสำหรับภาพระบบหลังบ้าน ครอบคลุมหน้าภาพรวม การขาย ร้านค้า การเงิน การช่วยเหลือ และความปลอดภัย',
 '05_interactions':'ภาพสถานะเพิ่มเติมหลังใช้ตัวกรองหรือเปิดส่วนโต้ตอบบนหน้าเว็บ ไม่มีการยืนยันคำสั่งซื้อหรือแก้ไขข้อมูลถาวร',
}
steps={
 '01_public/01_home':'ใช้แถบค้นหาด้านบนหรือเปิดเมนูหมวดสินค้า จากนั้นคลิกแบนเนอร์หรือการ์ดสินค้าเพื่อไปต่อ',
 '01_public/02_products':'เลือกหมวดทางแถบด้านข้าง ปรับช่วงราคา/สต็อก และเปลี่ยนการเรียง ก่อนคลิกสินค้าที่สนใจ',
 '01_public/03_product_detail':'ตรวจภาพ ชื่อ ราคา สต็อก และรายละเอียด เลือกจำนวน แล้วกดเพิ่มลงตะกร้า; เลื่อนลงเพื่ออ่านรีวิว',
 '01_public/04_shop':'ดูชื่อร้าน คะแนนและสินค้าของร้าน ใช้ปุ่มติดตามหรือแชทเมื่อเข้าสู่ระบบแล้ว',
 '01_public/05_cart_guest':'เมื่อยังไม่มีสินค้า ระบบแสดงสถานะตะกร้าว่าง กดเลือกซื้อสินค้าเพื่อกลับไปหน้ารายการ',
 '01_public/06_login':'กรอกชื่อผู้ใช้/อีเมลและรหัสผ่าน แล้วกดเข้าสู่ระบบ; ใช้ลิงก์ลืมรหัสผ่านหากจำไม่ได้',
 '01_public/07_register':'กรอกข้อมูลบัญชี ยอมรับเงื่อนไข แล้วส่งแบบฟอร์ม จากนั้นทำขั้นตอนยืนยันอีเมลตามที่เว็บแจ้ง',
 '01_public/08_seller_register':'เลือกสมัครเป็นผู้ขาย กรอกข้อมูลติดต่อและรหัสผ่าน จากนั้นยืนยันอีเมลและข้อมูลร้าน',
 '01_public/09_forgot_password':'กรอกอีเมลของบัญชี แล้วตรวจอีเมลเพื่อเปิดลิงก์ตั้งรหัสผ่านใหม่',
 '01_public/09_check_email':'อ่านคำแนะนำ เปิดกล่องอีเมลและกดลิงก์ยืนยัน; หากไม่พบให้ตรวจ Spam/Junk หรือขอส่งใหม่',
 '01_public/10_privacy':'อ่านรายละเอียดการเก็บ ใช้ และดูแลข้อมูลส่วนบุคคลก่อนสมัครสมาชิก',
 '02_customer/01_profile':'ตรวจข้อมูลบัญชี แก้ชื่อ เบอร์โทรหรือข้อมูลที่ระบบอนุญาต แล้วบันทึก',
 '02_customer/02_address_book':'เพิ่มที่อยู่ใหม่ ตั้งเป็นค่าเริ่มต้น หรือแก้ข้อมูลผู้รับเพื่อใช้ตอนชำระเงิน',
 '02_customer/03_wishlist':'เปิดสินค้าที่บันทึกไว้ แล้วกลับไปหน้าสินค้าเพื่อสั่งซื้อหรือลบออกจากรายการโปรด',
 '02_customer/04_cart':'ตรวจชื่อ ราคาและจำนวนสินค้า ปรับจำนวนหรือลบรายการ แล้วไปหน้าชำระเงิน',
 '02_customer/05_checkout':'เลือกที่อยู่ ตรวจยอดรวม เปิดเลือกคูปอง เลือก PromptPay หรือ COD แล้วจึงกดยืนยันคำสั่งซื้อ',
 '02_customer/06_coupons':'ดูคูปองที่มีและเงื่อนไข เช่น ยอดขั้นต่ำหรือวันหมดอายุ แล้วนำไปเลือกในหน้าชำระเงิน',
 '02_customer/07_orders':'ตรวจรายการสั่งซื้อทั้งหมด ใช้สถานะและหมายเลขออเดอร์เพื่อเลือกดูรายละเอียด',
 '02_customer/08_order_detail':'ตรวจสินค้าที่สั่ง ที่อยู่ วิธีจ่าย และลำดับสถานะ; ใช้ปุ่มบริการหลังการขายเมื่อเข้าเงื่อนไข',
 '02_customer/08_order_success':'ดูหมายเลขและยอดของออเดอร์ที่มีอยู่ จากนั้นไปดูรายละเอียดหรือติดตามคำสั่งซื้อ',
 '02_customer/09_receipt':'เปิดข้อมูลการซื้อของออเดอร์นั้น และใช้คำสั่งพิมพ์/บันทึกจากเบราว์เซอร์เมื่อจำเป็น',
 '02_customer/10_returns':'ดูคำขอคืนสินค้าที่ส่งไว้และสถานะการพิจารณา',
 '02_customer/11_return_request':'เลือกสินค้าที่ต้องการคืน ระบุเหตุผลและรายละเอียด แล้วตรวจข้อมูลก่อนส่งคำขอ',
 '02_customer/12_notifications':'อ่านรายการแจ้งเตือนล่าสุด แล้วเปิดลิงก์ที่เกี่ยวข้องกับออเดอร์ ร้านค้า หรือบัญชี',
 '02_customer/13_messages':'เลือกคู่สนทนาหรือร้านค้า อ่านประวัติ และส่งข้อความสอบถามสินค้า/คำสั่งซื้อ',
 '02_customer/14_support':'เปิดศูนย์ช่วยเหลือ ระบุหัวข้อและรายละเอียดปัญหา แล้วติดตามคำตอบในระบบ',
 '03_seller/01_dashboard':'เริ่มที่ตัวเลขสรุปและงานล่าสุด จากนั้นใช้เมนูด้านบนไปสินค้า คลัง ออเดอร์ หรือการเงิน',
 '03_seller/02_store':'ตรวจสถานะร้านและข้อมูลพื้นฐานก่อนตั้งค่าหน้าร้านหรือเพิ่มสินค้า',
 '03_seller/02_my_store':'แก้ชื่อร้าน คำอธิบาย รูปและแบนเนอร์ ตรวจข้อมูลก่อนบันทึกหน้าร้าน',
 '03_seller/03_marketplace':'ดูสินค้าของร้านและสถานะอนุมัติ เปิดแก้รายละเอียดหรือส่งสินค้าใหม่เข้าตรวจ',
 '03_seller/04_inventory':'ตรวจจำนวนคงเหลือ กรองสินค้าใกล้หมด และบันทึกการปรับสต็อก',
 '03_seller/05_orders':'เปิดออเดอร์ของร้าน ดำเนินการตามลำดับรับงาน แพ็ก ส่ง และระบุเลขพัสดุเมื่อจัดส่ง',
 '03_seller/06_wallet':'ตรวจยอดเงินและรายการเคลื่อนไหว เปิดคำขอถอนเงินเมื่อยอดพร้อมถอน',
 '03_seller/07_product_options':'เพิ่มหรือแก้ตัวเลือกสินค้า รูปในแกลเลอรี และวิดีโอ แล้วบันทึกสื่อหลัก',
 '04_admin/01_dashboard':'ดูยอดขาย จำนวนออเดอร์ งานรอดำเนินการ และใช้ทางลัดไปหน้าที่ต้องจัดการ',
 '04_admin/02_products':'ค้นหาสินค้า เพิ่มหรือแก้ข้อมูล ราคา รูป หมวด และสถานะการเผยแพร่',
 '04_admin/03_promotions':'สร้างหรือแก้คูปอง/โปรโมชัน ตั้งสิทธิ์และช่วงเวลา แล้วตรวจสถานะใช้งาน',
 '04_admin/04_mall_promotions':'จัดการแคมเปญระดับ MALL และการนำเสนอโปรโมชันบนเว็บไซต์',
 '04_admin/05_orders':'กรองออเดอร์ตามสถานะ เปิดรายละเอียด และติดตามขั้นตอนชำระ/จัดส่ง',
 '04_admin/06_order_detail':'ตรวจรายการสินค้า ผู้รับ การชำระ และประวัติสถานะ ก่อนดำเนินการต่อ',
 '04_admin/07_returns':'เปิดคำขอคืนสินค้า ตรวจเหตุผลและหลักฐาน แล้วอัปเดตผลการพิจารณา',
 '04_admin/08_support':'เปิดเคสลูกค้า อ่านข้อความและตอบกลับตามข้อมูลคำสั่งซื้อหรือบัญชี',
 '04_admin/09_sellers':'ตรวจข้อมูลคำขอผู้ขายและตัดสินใจอนุมัติหรือดำเนินการตามสถานะ',
 '04_admin/10_stores':'ดูร้านทั้งหมด ค้นหาหรือเปิดรายละเอียดร้านเพื่อดูสถานะและสินค้า',
 '04_admin/11_store_detail':'ตรวจข้อมูลร้าน สินค้า และข้อจำกัดที่ใช้กับร้านนั้น',
 '04_admin/12_seller_products':'ตรวจสินค้าที่ผู้ขายส่งมา และอนุมัติ/ดำเนินการตามผลตรวจ',
 '04_admin/13_product_reports':'อ่านเหตุผลการรายงานสินค้า ตรวจรายการที่เกี่ยวข้อง แล้วบันทึกการดำเนินการ',
 '04_admin/14_payouts':'ตรวจคำขอถอนเงินผู้ขาย ยอด และสถานะการจ่าย ก่อนบันทึกผล',
 '04_admin/15_email_logs':'ตรวจอีเมลที่ส่ง/ค้าง/ล้มเหลวเพื่อวิเคราะห์ปัญหาการแจ้งเตือน',
 '04_admin/16_security':'ดูเหตุการณ์ความปลอดภัย กฎ และข้อมูลที่ระบบใช้เฝ้าระวัง',
 '04_admin/17_analytics':'อ่านแนวโน้มการใช้งานและพฤติกรรมเพื่อปรับปรุงหน้าเว็บ',
 '04_admin/18_users':'ค้นหาบัญชีผู้ใช้ ดูบทบาทและข้อมูลที่จำเป็นต่อการดูแลระบบ',
}
grouped=defaultdict(list)
for r in main:grouped[r['section']].append(r)
for section in ('01_public','02_customer','03_seller','04_admin'):
 lines=[f'# {names[section]}','',intro[section],'',f'ภาพในโฟลเดอร์: **{len(grouped[section])} หน้า**','']
 for i,r in enumerate(grouped[section],1):
  stem=r['file'][:-4] if r['file'] else Path(r['path']).stem
  lines += [f'## {i:02d}. {r["title"]}','',f'![{r["title"]}]({r["file"]})' if r['file'] else f'ไม่มีภาพ: {r["status"]}','','**สิ่งที่แสดง:** '+r['description'],'','**วิธีใช้งาน:** '+steps.get(section+'/'+stem,'เปิดหน้านี้จากเมนูของบทบาทที่เกี่ยวข้อง แล้วตรวจข้อมูลก่อนดำเนินการต่อ'),'','**ตำแหน่งหน้า:** `'+r['path']+'`','']
 (OUT/section/'README.md').write_text('\n'.join(lines),encoding='utf-8')

interaction_lines=['# ตัวอย่างการใช้งานย่อย','','ภาพเพิ่มเติมหลังเลือกตัวกรองหรือเปิดส่วนโต้ตอบของหน้าเว็บ','']
for i,r in enumerate(extra,1):
 interaction_lines += [f'## {i:02d}. {r["title"]}','','!['+r['title']+']('+r['file']+')','',r['description'],'',f'**หน้า:** `{r["path"]}`','']
(OUT/'05_interactions'/'README.md').write_text('\n'.join(interaction_lines),encoding='utf-8')

root_lines=['# ชุดภาพหน้าจอ KitchenMate','','ถ่ายจากเว็บไซต์ที่รันบนเครื่องนี้เมื่อ 16 กันยายน 2026 ขนาดหน้าต่าง 1440 × 900 พิกเซล ภาพเป็นหน้าจอจริงของระบบและเก็บแบบเต็มหน้า','',f'**รวม {sum(r["status"]=="captured" for r in main)+len(extra)+1} ภาพ**: {len(main)} หน้าหลัก, {len(extra)} สถานะการใช้งาน และภาพโปรโมชันหน้าแรกอีก 1 ภาพ','','## เริ่มอ่านตามบทบาท','']
for section in ('01_public','02_customer','03_seller','04_admin','05_interactions'):
 root_lines += [f'- [{names[section]}]({section}/README.md)']
root_lines += ['','## เส้นทางสาธิตที่แนะนำ','','1. ผู้เยี่ยมชม: หน้าแรก → ค้นหา/กรอง → รายละเอียดสินค้า → สมัครหรือเข้าสู่ระบบ','2. ลูกค้า: ตะกร้า → ชำระเงิน → เลือกคูปอง/วิธีจ่าย → คำสั่งซื้อ → บริการหลังการขาย','3. ผู้ขาย: แดชบอร์ด → ร้านค้าและสินค้า → สต็อก → ออเดอร์ → กระเป๋าเงิน','4. แอดมิน: แดชบอร์ด → สินค้า/ผู้ขาย → ออเดอร์/คืนสินค้า → โปรโมชัน → ข้อมูลและความปลอดภัย','','## ขอบเขตของภาพ','','ภาพลูกค้า ผู้ขาย และแอดมินใช้เซสชันชั่วคราวสำหรับเก็บภาพโดยไม่เปลี่ยนรหัสผ่านหรือสร้างคำสั่งซื้อใหม่ ภาพชำระเงินมีสินค้าในตะกร้าชั่วคราว 1 รายการ ภาพหน้าสั่งซื้อสำเร็จเปิดจากออเดอร์ที่มีอยู่ ภาพนี้อาจแสดงข้อมูลบัญชีและออเดอร์ที่อยู่ในฐานข้อมูลของเครื่อง ควรตรวจข้อมูลก่อนนำไปเผยแพร่ภายนอก','','สถานะที่ต้องใช้ลิงก์ยืนยันหรือรหัสจริง เช่น ยืนยันอีเมล ตั้งรหัสผ่านใหม่หลังคลิกลิงก์ และการยืนยันสองขั้นตอน ไม่ได้สร้างธุรกรรมเพื่อถ่ายภาพ ชุดนี้ครอบคลุมหน้าที่เปิดดูได้และสถานะโต้ตอบหลัก','','## ไฟล์ประกอบ','',f'- `manifest.json` ระบุชื่อหน้า เส้นทาง URL และสถานะการเก็บภาพ {len(main)} หน้า',f'- `05_interactions/manifest.json` ระบุภาพสถานะเพิ่มเติม {len(extra)} ภาพ','- รูป `.png` ในแต่ละโฟลเดอร์เป็นภาพต้นฉบับเต็มหน้า','','## ภาพโปรโมชันหน้าแรก','','![ตัวอย่างโปรโมชันหน้าแรก](01_public/01_home_promo.png)','']
(OUT/'README.md').write_text('\n'.join(root_lines),encoding='utf-8')
cards=[]
for r in main:
 if not r['file']:continue
 key=r['section']+'/'+r['file'][:-4]
 path=key+'.png'
 cards.append(f'<article class="card" data-section="{r["section"]}" data-search="{html.escape(r["title"]+" "+r["description"]+" "+steps.get(key,""),quote=True)}"><a href="{html.escape(path)}" target="_blank"><img loading="lazy" src="{html.escape(path)}" alt="{html.escape(r["title"])}"></a><div class="copy"><small>{html.escape(names[r["section"]])}</small><h2>{html.escape(r["title"])}</h2><p>{html.escape(r["description"])}</p><p><strong>วิธีใช้:</strong> {html.escape(steps.get(key,""))}</p><a class="open" href="{html.escape(path)}" target="_blank">เปิดภาพเต็ม ↗</a></div></article>')
for r in extra:
 path='05_interactions/'+r['file']
 cards.append(f'<article class="card" data-section="05_interactions" data-search="{html.escape(r["title"]+" "+r["description"],quote=True)}"><a href="{html.escape(path)}" target="_blank"><img loading="lazy" src="{html.escape(path)}" alt="{html.escape(r["title"])}"></a><div class="copy"><small>{html.escape(names["05_interactions"])}</small><h2>{html.escape(r["title"])}</h2><p>{html.escape(r["description"])}</p><a class="open" href="{html.escape(path)}" target="_blank">เปิดภาพเต็ม ↗</a></div></article>')
options=''.join(f'<option value="{k}">{v}</option>' for k,v in names.items())
page=f'''<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ภาพหน้าจอ KitchenMate</title><style>
body{{margin:0;background:#f8f6ef;color:#173f32;font-family:"Leelawadee UI",Tahoma,sans-serif}}header{{background:#173f32;color:#fff;padding:32px max(24px,5vw)}}h1{{margin:0 0 8px;font-size:32px}}header p{{margin:0;color:#dce9e1}}nav{{display:flex;gap:12px;flex-wrap:wrap;padding:18px max(24px,5vw);position:sticky;top:0;background:#f8f6ef;z-index:1;border-bottom:1px solid #e1d9ce}}input,select{{font:inherit;padding:10px 12px;border:1px solid #cbd4cd;border-radius:8px;background:#fff}}input{{flex:1;min-width:220px}}main{{padding:24px max(24px,5vw);display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:22px}}.card{{background:white;border:1px solid #e0e5dc;border-radius:16px;overflow:hidden;box-shadow:0 6px 20px #173f3210}}.card img{{width:100%;height:220px;object-fit:cover;object-position:top;display:block}}.copy{{padding:18px}}small{{color:#bd6e2c;font-weight:700}}h2{{font-size:21px;margin:8px 0}}p{{line-height:1.55}}.open{{color:#173f32;font-weight:700}}.card[hidden]{{display:none}}footer{{padding:24px max(24px,5vw);color:#5e7168}}
</style><header><h1>ภาพหน้าจอ KitchenMate</h1><p>{len(main)} หน้าหลัก · {len(extra)} สถานะเพิ่มเติม · คลิกภาพเพื่อดูขนาดเต็ม</p></header><nav><input id="q" placeholder="ค้นหาชื่อหน้าหรือคำอธิบาย"><select id="section"><option value="">ทุกระบบ</option>{options}</select></nav><main id="grid">{''.join(cards)}</main><footer>คำอธิบายละเอียดรายบทบาทอยู่ใน README.md ของแต่ละโฟลเดอร์</footer><script>const q=document.querySelector('#q'),s=document.querySelector('#section');function filter(){{const t=q.value.trim().toLowerCase();document.querySelectorAll('.card').forEach(c=>c.hidden=!!((s.value&&c.dataset.section!==s.value)||(t&&!c.dataset.search.toLowerCase().includes(t))))}}q.addEventListener('input',filter);s.addEventListener('change',filter)</script></html>'''
(OUT/'index.html').write_text(page,encoding='utf-8')
print('Wrote guides for',len(main),'pages and',len(extra),'interactions')
