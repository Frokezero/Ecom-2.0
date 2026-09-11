$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root = Split-Path -Parent $PSScriptRoot
$outDir = Join-Path $root 'docs'
$out = Join-Path $outDir 'ER_KitchenMart.docx'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null
if (Test-Path $out) { Remove-Item -LiteralPath $out -Force }

function Esc([string]$s) { [System.Security.SecurityElement]::Escape($s) }
function P([string]$text, [string]$style='Normal') {
  '<w:p><w:pPr><w:pStyle w:val="' + $style + '"/></w:pPr><w:r><w:t xml:space="preserve">' + (Esc $text) + '</w:t></w:r></w:p>'
}
function Table([string[][]]$rows) {
  $x = '<w:tbl><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4" w:color="D9D4CA"/><w:left w:val="single" w:sz="4" w:color="D9D4CA"/><w:bottom w:val="single" w:sz="4" w:color="D9D4CA"/><w:right w:val="single" w:sz="4" w:color="D9D4CA"/><w:insideH w:val="single" w:sz="4" w:color="D9D4CA"/><w:insideV w:val="single" w:sz="4" w:color="D9D4CA"/></w:tblBorders></w:tblPr>'
  for ($i=0; $i -lt $rows.Count; $i++) {
    $x += '<w:tr>'
    foreach ($cell in $rows[$i]) {
      $bold = if ($i -eq 0) { '<w:rPr><w:b/></w:rPr>' } else { '' }
      $x += '<w:tc><w:tcPr><w:tcW w:w="2400" w:type="dxa"/></w:tcPr><w:p><w:r>' + $bold + '<w:t xml:space="preserve">' + (Esc $cell) + '</w:t></w:r></w:p></w:tc>'
    }
    $x += '</w:tr>'
  }
  $x + '</w:tbl>'
}

$body = @()
$body += (P 'Entity Relationship (ER) System' 'Title')
$body += (P 'KitchenMart E-commerce Platform' 'Subtitle')
$body += (P 'เอกสารสรุปโครงสร้างข้อมูลและความสัมพันธ์ของระบบ | จัดทำวันที่ 10 กันยายน 2569')
$body += (P '1. ขอบเขตระบบ' 'Heading1')
$body += (P 'เอกสารนี้อธิบาย ER ของแพลตฟอร์มขายสินค้าเครื่องครัว ครอบคลุมหน้าร้าน ลูกค้า ผู้ขาย แอดมิน คำสั่งซื้อ การชำระเงิน โปรโมชั่น การจัดส่ง การคืนเงิน การสื่อสาร และระบบตรวจจับพฤติกรรมผิดปกติ (Security/Behavior Analytics)')
$body += (P 'สัญลักษณ์: PK = Primary Key, FK = Foreign Key, 1:N = หนึ่งต่อหลาย, N:N = หลายต่อหลาย')
$body += (P '2. Entity หลัก' 'Heading1')
$body += (Table @(
  @('Entity / ตาราง','หน้าที่','คีย์สำคัญ'),
  @('users','บัญชีลูกค้า ผู้ขาย และแอดมิน','PK id; username, email, role'),
  @('seller_profiles','ข้อมูลร้านค้าและสถานะการอนุมัติ','PK/FK user_id; primary_category_id FK'),
  @('categories','หมวดหมู่สินค้า','PK id; slug UNIQUE'),
  @('products','สินค้า ราคา สต็อก สื่อหลัก และสถานะอนุมัติ','PK id; category_id FK; seller_id FK'),
  @('product_images','รูปเพิ่มเติมของสินค้า เรียงลำดับได้','PK id; product_id FK'),
  @('product_variants','SKU/ตัวเลือกสินค้าและสต็อกแยกตัวเลือก','PK id; product_id FK; sku UNIQUE'),
  @('orders','หัวคำสั่งซื้อ ลูกค้า ยอดรวม การชำระเงิน และสถานะ','PK id; order_no UNIQUE; user_id FK; coupon_id FK'),
  @('order_items','รายการสินค้าในคำสั่งซื้อและราคาขณะซื้อ','PK id; order_id FK; product_id FK; variant_id FK'),
  @('order_fulfillments','การแพ็ก/จัดส่งแยกตามร้านผู้ขาย','PK id; order_id FK; seller_id FK'),
  @('user_addresses','สมุดที่อยู่จัดส่งหลายรายการต่อผู้ใช้','PK id; user_id FK; is_default'),
  @('payment_transactions','รายการชำระเงินและ webhook สถานะธุรกรรม','PK id; order_id FK'),
  @('coupons / user_coupons / coupon_usages','คูปอง สิทธิ์ที่ผู้ใช้เก็บ และประวัติการใช้','coupon_id/user_id/order_id FK'),
  @('promotional_banners','แบนเนอร์โปรโมชันผูกหมวดหมู่หรือคูปอง','PK id; category_id/coupon_id FK'),
  @('product_bundles / product_bundle_items','Bundle Deal และสินค้าที่อยู่ในชุด','bundle_id/product_id FK; composite PK'),
  @('product_reviews / wishlists','รีวิว คะแนน รายการโปรด','product_id/user_id FK'),
  @('store_followers / store_conversations / store_messages','ติดตามร้าน แชตลูกค้า-ผู้ขาย และข้อความ','seller_id/customer_id/conversation_id FK'),
  @('return_requests / return_request_items / refunds','คำขอคืนสินค้า รายการคืน และเงินคืน','order_id/user_id/order_item_id/payment FK'),
  @('seller_payout_requests / seller_ledger','ถอนเงินผู้ขายและบัญชีรายรับรายจ่าย','seller_id/order_id/payout_request_id FK'),
  @('notifications / email_delivery_logs','แจ้งเตือนในระบบและประวัติอีเมล','user_id FK'),
  @('audit_logs / order_status_history','ประวัติการเปลี่ยนแปลงเพื่อการตรวจสอบ','actor_user_id/order_id FK'),
  @('security_events / security_blocks / security_rules','เหตุการณ์เสี่ยง การบล็อกชั่วคราว และกฎ Threshold','user_id/created_by FK'),
  @('user_activity_logs / behavior_baselines / behavior_detections','เก็บกิจกรรม ค่าปกติ และผลตรวจจับผิดปกติ','user_id/activity_log_id FK'),
  @('request_rate_counters / login_attempts / auth_challenges','ควบคุมอัตรา Request และความปลอดภัย Login/2FA','bucket_key PK; user_id FK'),
  @('support_tickets / support_messages','ศูนย์ช่วยเหลือและข้อความสนับสนุน','user_id/order_id/ticket_id FK'),
  @('privacy_consents','บันทึกความยินยอมด้านข้อมูลส่วนบุคคล','user_id FK')
))
$body += (P '3. ความสัมพันธ์สำคัญ' 'Heading1')
$body += (Table @(
  @('ต้นทาง','ความสัมพันธ์','ปลายทาง / คำอธิบาย'),
  @('users','1:N','products: ผู้ขายสร้างสินค้า; seller_id อาจเป็น NULL สำหรับสินค้า MALL'),
  @('users','1:1','seller_profiles: ผู้ขายหนึ่งบัญชีมีโปรไฟล์ร้านหนึ่งชุด'),
  @('categories','1:N','products และ promotional_banners'),
  @('products','1:N','product_images, product_variants, product_reviews, inventory_movements'),
  @('users','1:N','orders, user_addresses, notifications, security_events, activity logs'),
  @('orders','1:N','order_items, order_fulfillments, payment_transactions, status history'),
  @('orders','N:1','users: ลูกค้าหนึ่งคนมีหลายคำสั่งซื้อ'),
  @('coupons','N:N','users ผ่าน user_coupons และ coupon_usages; orders เก็บคูปองที่ใช้'),
  @('product_bundles','N:N','products ผ่าน product_bundle_items'),
  @('return_requests','1:N','return_request_items; refunds อ้างอิงคำขอคืนได้'),
  @('store_conversations','1:N','store_messages; seller/customer เป็น users คนละบทบาท'),
  @('support_tickets','1:N','support_messages และอาจผูกกับ orders'),
  @('user_activity_logs','1:N','behavior_detections อ้างอิงกิจกรรมที่ตรวจพบ'),
  @('users','1:N','security_blocks, audit_logs, login_attempts และ auth_challenges')
))
$body += (P '4. ภาพรวมการทำงานของข้อมูล' 'Heading1')
$body += (P 'ผู้ใช้สมัคร/เข้าสู่ระบบ → เลือกหมวดหมู่และสินค้า → เพิ่มที่อยู่/ตะกร้า → ใช้คูปองที่เข้าเงื่อนไข → สร้าง orders และ order_items → บันทึก payment_transactions → ผู้ขายจัดการ order_fulfillments → อัปเดตสถานะและแจ้งเตือน → ปิดการขาย/รีวิว/คืนสินค้า')
$body += (P 'Security flow: ทุก Request/Login ถูกบันทึกเป็น activity/security event → คำนวณ feature เช่น login/min, request/min, failed login และ order/hour → เทียบ behavior_baselines/security_rules → บันทึก behavior_detections → แจ้งเตือนหรือสร้าง security_blocks ชั่วคราว พร้อม audit_logs')
$body += (P '5. กฎความถูกต้องของข้อมูล' 'Heading1')
$body += (P '• ใช้ InnoDB และ Foreign Key เพื่อรักษาความสอดคล้องของข้อมูล\n• ลบข้อมูลแบบ CASCADE เฉพาะข้อมูลลูกที่ไม่ต้องเก็บประวัติ เช่น รูปสินค้า/ข้อความ\n• ข้อมูลคำสั่งซื้อ การชำระเงิน บัญชีผู้ขาย และ Audit ใช้ RESTRICT หรือ SET NULL เพื่อป้องกันการสูญเสียหลักฐาน\n• คูปองต่อผู้ใช้มี UNIQUE (coupon_id,user_id) และการใช้คูปองต่อคำสั่งซื้อมี UNIQUE (order_id)\n• สินค้า Variant ใช้ SKU UNIQUE และรองรับสต็อกแยก SKU')
$body += (P '6. การนำไปใช้และทดสอบ' 'Heading1')
$body += (P 'เอกสารอ้างอิงจาก database/schema.sql และ database/migrations ในโปรเจกต์ ควรใช้ migration เป็นแหล่งเปลี่ยนแปลง schema หลัก และทดสอบเส้นทางสำคัญ: สมัคร/ล็อกอิน, สร้างสินค้า, ซื้อสินค้า+คูปอง, ชำระเงิน, ผู้ขายจัดส่ง, คืนสินค้า, และการตรวจจับพฤติกรรมผิดปกติ')

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' + ($body -join '') + '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="900" w:right="900" w:bottom="900" w:left="900"/></w:sectPr></w:body></w:document>'
$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Prompt" w:hAnsi="Prompt" w:eastAsia="Prompt"/><w:sz w:val="20"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="244A3A"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:rPr><w:sz w:val="26"/><w:color w:val="E86A33"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="Heading 1"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="244A3A"/></w:rPr></w:style></w:styles>'
$types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>'
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>'
$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'

$zip = [System.IO.Compression.ZipFile]::Open($out, [System.IO.Compression.ZipArchiveMode]::Create)
foreach ($item in @(
  @('[Content_Types].xml',$types), @('_rels/.rels',$rels), @('word/document.xml',$document), @('word/styles.xml',$styles), @('word/_rels/document.xml.rels',$docRels)
)) {
  $entry = $zip.CreateEntry($item[0]); $sw = New-Object IO.StreamWriter($entry.Open(), (New-Object Text.UTF8Encoding($false))); $sw.Write($item[1]); $sw.Dispose()
}
$zip.Dispose()
Write-Output $out
