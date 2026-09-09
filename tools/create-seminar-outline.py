from docx import Document
from docx.shared import Cm, Pt
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from pathlib import Path

OUT=Path(r'D:\โฟลเดอร์ใหม่ (6)\แบบเสนอเค้าโครงวิชาสัมมนา_KitchenMart.docx')
d=Document(); sec=d.sections[0];sec.page_height=Cm(29.7);sec.page_width=Cm(21);sec.top_margin=Cm(2.54);sec.bottom_margin=Cm(2.54);sec.left_margin=Cm(3.5);sec.right_margin=Cm(2.5)
style=d.styles['Normal'];style.font.name='TH Sarabun New';style.font.size=Pt(16);style._element.rPr.rFonts.set(qn('w:eastAsia'),'TH Sarabun New')
style.paragraph_format.line_spacing=1.0;style.paragraph_format.space_after=Pt(0)
def p(text='',bold=False,center=False,indent=1.25,size=16,space=0):
    x=d.add_paragraph();x.alignment=WD_ALIGN_PARAGRAPH.CENTER if center else WD_ALIGN_PARAGRAPH.JUSTIFY;x.paragraph_format.first_line_indent=Cm(0 if center else indent);x.paragraph_format.space_after=Pt(space);r=x.add_run(text);r.bold=bold;r.font.name='TH Sarabun New';r._element.rPr.rFonts.set(qn('w:eastAsia'),'TH Sarabun New');r.font.size=Pt(size);return x
def h(text):p(text,bold=True,indent=0,space=3)
def item(text,n=None,level=0):p((f'{n}. ' if n is not None else '– ')+text,indent=0);d.paragraphs[-1].paragraph_format.left_indent=Cm(1.25+level*.6)
def page():d.add_page_break()
def shade(cell,fill):tcPr=cell._tc.get_or_add_tcPr();shd=OxmlElement('w:shd');shd.set(qn('w:fill'),fill);tcPr.append(shd)
def table(rows,widths=None,header=True):
    t=d.add_table(rows=1,cols=len(rows[0]));t.alignment=WD_TABLE_ALIGNMENT.CENTER;t.style='Table Grid'
    for j,v in enumerate(rows[0]):t.rows[0].cells[j].text=str(v);shade(t.rows[0].cells[j],'E7E6E6')
    for row in rows[1:]:
        c=t.add_row().cells
        for j,v in enumerate(row):c[j].text=str(v)
    for row in t.rows:
        for c in row.cells:
            c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
            for pa in c.paragraphs:
                pa.paragraph_format.first_line_indent=Cm(0);pa.paragraph_format.space_after=Pt(0)
                for r in pa.runs:r.font.name='TH Sarabun New';r._element.rPr.rFonts.set(qn('w:eastAsia'),'TH Sarabun New');r.font.size=Pt(14)
    return t

p('แบบเสนอเค้าโครงวิชาสัมมนาด้านเทคโนโลยีคอมพิวเตอร์และดิจิทัล',True,True,0,18)
p('ปีการศึกษา 1/2569',False,True,0,16,18)
h('1. ชื่องาน')
p('การพัฒนาเว็บไซต์พาณิชย์อิเล็กทรอนิกส์ที่มีระบบตรวจจับพฤติกรรมผิดปกติเพื่อเพิ่มความปลอดภัยของผู้ใช้งาน')
p('(Development of an E-commerce Website with Anomalous Behavior Detection to Enhance User Security)',False,True,0)
h('2. ผู้เสนอโครงงาน')
p('ชื่อ-สกุล: [กรอกชื่อ-สกุลผู้จัดทำ]    รหัสประจำตัว: [กรอกรหัสนักศึกษา]',indent=0)
p('ปัจจุบันเว็บไซต์พาณิชย์อิเล็กทรอนิกส์เป็นช่องทางสำคัญในการซื้อขายสินค้าและจัดเก็บข้อมูลส่วนบุคคล ข้อมูลการเข้าสู่ระบบ ที่อยู่จัดส่ง และประวัติคำสั่งซื้อจึงเป็นเป้าหมายของผู้ไม่หวังดี รูปแบบภัยคุกคามที่พบได้บ่อย ได้แก่ การเดารหัสผ่านซ้ำ การส่งคำขอจำนวนมาก การเข้าถึงหน้าผู้ดูแลระบบโดยไม่ได้รับอนุญาต และการสร้างคำสั่งซื้อถี่ผิดปกติ การป้องกันด้วยชื่อผู้ใช้และรหัสผ่านเพียงอย่างเดียวไม่สามารถอธิบายความต่อเนื่องของพฤติกรรมเหล่านี้ได้')
p('โครงงานนี้พัฒนา KitchenMart ซึ่งเป็นเว็บไซต์ E-commerce ที่บันทึกกิจกรรมสำคัญของผู้ใช้ แล้วแปลง Log เป็นคุณลักษณะเชิงเวลา ได้แก่ จำนวนการเข้าสู่ระบบต่อนาที จำนวน Request ต่อนาที จำนวนคำสั่งซื้อต่อชั่วโมง และจำนวน Login ที่ล้มเหลวต่อ 10 นาที จากนั้นเปรียบเทียบกับ Threshold ที่กำหนดจากการวิเคราะห์พฤติกรรมปกติ เมื่อเกินเกณฑ์ ระบบจะจัดประเภทเป็น Suspicious แจ้งเตือนผู้ดูแลระบบหรือผู้ใช้ และระงับ IP หรือบัญชีเป็นการชั่วคราวตามระดับความเสี่ยง')
p('เพื่อประเมินระบบอย่างเป็นรูปธรรม จะจำลองผู้ใช้ 100 คน เป็นเวลา 30 วัน รวม 3,000 กรณี แบ่งเป็นกลุ่ม Normal และ Suspicious พร้อมคำนวณ Confusion Matrix, Accuracy, Precision, Detection Rate/Recall, F1-score, False Positive Rate และ Detection Time ตลอดจนประเมิน Response Time, CPU Usage, Memory Usage และ Throughput ผลที่ได้ทำให้โครงงานครอบคลุมทั้ง Software Engineering, Cybersecurity และ Data Analysis')
h('3. วัตถุประสงค์ของโครงงาน')
item('เพื่อพัฒนาเว็บไซต์ E-commerce ที่รองรับการซื้อขายสินค้าและจัดการผู้ใช้ ร้านค้า คำสั่งซื้อ การชำระเงิน และระบบหลังบ้าน',1)
item('เพื่อพัฒนาระบบบันทึกและวิเคราะห์พฤติกรรมผู้ใช้แบบอิงช่วงเวลา และนำ Threshold ไปใช้ตรวจจับเหตุการณ์ผิดปกติจริง',2)
item('เพื่อประเมินประสิทธิภาพการตรวจจับด้วย Confusion Matrix และตัวชี้วัดมาตรฐาน รวมทั้งประเมินผลกระทบต่อสมรรถนะของระบบ',3)

page();h('4. ขอบเขตของโครงงาน');h('4.1 ขอบเขตเนื้อหา')
item('ระบบสมาชิก: สมัครสมาชิก ยืนยันอีเมล เข้าสู่ระบบ เปลี่ยนอีเมล เปลี่ยนรหัสผ่าน ที่อยู่จัดส่ง และการยืนยันตัวตนสองขั้นตอน',1)
item('ระบบพาณิชย์อิเล็กทรอนิกส์: สินค้า หมวดสินค้า สต็อก ตะกร้า คูปอง คำสั่งซื้อ การชำระเงิน การจัดส่ง การคืนสินค้าและคืนเงิน',2)
item('ระบบผู้ขาย: สมัครเป็นผู้ขาย ข้อมูลรับเงิน จัดการสินค้า ตกแต่งหน้าร้าน และติดตามยอดขาย/ยอดถอน',3)
item('ระบบผู้ดูแล: จัดการผู้ใช้ สินค้า คำสั่งซื้อ โปรโมชัน ผู้ขาย การแจ้งเตือน และศูนย์ความปลอดภัย',4)
item('ระบบความปลอดภัย: CSRF, Prepared Statement, Password Hashing, Rate Limit, Security Event, Email Alert และ Temporary Block',5)
item('ระบบวิเคราะห์พฤติกรรม: User Activity → Log → Feature → Threshold → Normal/Suspicious → Alert/Temporary Block',6)
h('4.2 ข้อมูลที่จัดเก็บ')
table([['เขตข้อมูล','รายละเอียด'],['user_id','รหัสผู้ใช้'],['timestamp','วันและเวลาที่เกิดกิจกรรม'],['ip_address','IP ที่ปกปิดบางส่วนและค่า Hash'],['action / url / status','กิจกรรม เส้นทาง และ HTTP status'],['response_time','เวลาตอบสนองหน่วยมิลลิวินาที'],['login_success','ผลการเข้าสู่ระบบ'],['request_count','จำนวนคำขอในช่วงเวลา'],['order_amount','มูลค่าคำสั่งซื้อ']])
h('4.3 ประชากร/กลุ่มตัวอย่าง')
p('ประชากร คือ ผู้ใช้งานเว็บไซต์ E-commerce และกิจกรรมที่เกิดขึ้นในระบบ กลุ่มตัวอย่างเป็นผู้ใช้จำลอง 100 คน ติดตามเป็นเวลา 30 วัน รวม 3,000 user-day cases แบ่งเป็น Normal User 80 คน (2,400 กรณี) และ Suspicious User 20 คน (600 กรณี) ข้อมูลทดลองมีป้ายกำกับและแยกออกจากข้อมูล Runtime เพื่อป้องกัน Data Leakage')
h('4.4 พฤติกรรมที่ใช้ตรวจจับ')
table([['Behavior / Feature','Mean เริ่มต้น','S.D. เริ่มต้น','Threshold ที่ใช้จริง'],['Login/min','0.4','0.3','> 2'],['Request/min','15','8','> 50'],['Order/hour','1.2','1.0','> 5'],['Failed Login/10 min','0.5','1.2','> 5']])
p('ค่า Mean และ S.D. จะคำนวณใหม่จากข้อมูล Normal ของการทดลอง ส่วน Threshold เป็นค่า Policy ที่กำหนดไว้ล่วงหน้าและใช้เหมือนกันทั้งการทดลองและ Runtime เพื่อไม่ปรับเกณฑ์ตามผลลัพธ์ภายหลัง')

page();h('4.5 สถิติที่ใช้ในการวิเคราะห์ข้อมูล')
p('1) ค่าเฉลี่ย (Mean) ใช้สูตร  X̄ = ΣXi / n',indent=0)
p('2) ส่วนเบี่ยงเบนมาตรฐานของกลุ่มตัวอย่าง (Sample Standard Deviation) ใช้สูตร  S.D. = √[Σ(Xi − X̄)² / (n − 1)]',indent=0)
p('3) Confusion Matrix ประกอบด้วย True Positive (TP), True Negative (TN), False Positive (FP) และ False Negative (FN)',indent=0)
table([['ตัวชี้วัด','สูตร','ความหมาย'],['Accuracy','(TP+TN)/(TP+TN+FP+FN)','ความถูกต้องโดยรวม'],['Precision','TP/(TP+FP)','เมื่อระบบแจ้ง Suspicious แล้วถูกต้องเท่าใด'],['Detection Rate / Recall','TP/(TP+FN)','ตรวจจับเหตุการณ์ผิดปกติได้เท่าใด'],['F1-score','2×Precision×Recall/(Precision+Recall)','สมดุล Precision และ Recall'],['False Positive Rate','FP/(FP+TN)','ผู้ใช้ปกติถูกแจ้งผิดเท่าใด']])
p('4) Detection Time วัดเวลาตั้งแต่กิจกรรมผิดปกติรายการแรกจนระบบตรวจพบว่าเกิน Threshold',indent=0)
p('5) System Performance วัดค่าเฉลี่ยและ P95 ของ Response Time, CPU Usage, Peak Memory และจำนวนกรณีที่วิเคราะห์ได้ต่อวินาที',indent=0)
h('4.6 รูปแบบการทดลอง')
item('Normal User: Login ปกติ ดูสินค้า เพิ่มสินค้าในตะกร้า และซื้อสินค้าเฉลี่ย 1–2 ครั้ง โดยมีความแปรปรวนตามธรรมชาติ',1)
item('Suspicious User: Login ผิดหลายครั้ง, ส่ง Request 500 ครั้ง/นาที, พยายามเข้า /admin และสร้างคำสั่งซื้อ 6–20 ครั้งในช่วงเวลาสั้น',2)
item('เพิ่ม Normal burst บางกรณีเพื่อทดสอบ False Positive และเพิ่ม Slow attack ที่ต่ำกว่า Threshold บางกรณีเพื่อทดสอบ False Negative',3)
item('การทดลองทำกับข้อมูลจำลองในเครื่องและไม่ยิงโจมตีระบบสาธารณะหรือบุคคลภายนอก',4)

page();h('5. เทคโนโลยีที่ใช้');h('5.1 เทคนิคหรือเทคโนโลยีที่ใช้')
item('Threshold-based Anomaly Detection: สร้างคุณลักษณะตาม Time Window และเปรียบเทียบค่าที่สังเกตได้กับเกณฑ์',1)
item('Behavioral Logging: บันทึกกิจกรรม Login, Request, Order และ Unauthorized Access พร้อมเวลาและผลลัพธ์',2)
item('Defense in Depth: ใช้การแจ้งเตือน การจำกัดอัตรา การระงับตัวตน/IP ชั่วคราว และบันทึก Audit ร่วมกัน',3)
item('Privacy-aware Logging: แสดง IP แบบปกปิดและเก็บ Hash แทนการเปิดเผยข้อมูลเต็มในหน้าวิเคราะห์',4)
h('5.2 เครื่องมือที่ใช้ในการวิจัย')
item('แบบบันทึกผลการทดลอง 3,000 กรณีและ Confusion Matrix',1)
item('รายงาน CSV ภาษาไทย/อังกฤษสำหรับเปิดด้วย Microsoft Excel',2)
item('เครื่องมือวัดเวลา CPU หน่วยความจำ และ Throughput ภายในโปรแกรมทดลอง',3)
h('5.3 เครื่องมือที่ใช้ในการพัฒนา')
table([['ประเภท','เครื่องมือ'],['ภาษา','PHP 8, JavaScript, HTML5, CSS3, SQL'],['ฐานข้อมูล','MySQL/MariaDB ผ่าน PDO Prepared Statements'],['เว็บเซิร์ฟเวอร์','PHP Development Server / Apache (XAMPP)'],['เครือข่าย','Cloudflare Tunnel สำหรับสาธิตระบบ'],['เครื่องมือ','Visual Studio Code, Git, GitHub, Microsoft Excel'],['ระบบปฏิบัติการ','Windows 11']])
h('6. วิธีการดำเนินงาน')
p('ประยุกต์กระบวนการพัฒนาแบบ ADDIE ร่วมกับวงจร Software Development Life Cycle โดยมีขั้นตอนดังนี้')
h('1. Analysis (การวิเคราะห์)')
item('ศึกษาปัญหาและจำแนกภัย เช่น Credential Cracking, Credential Stuffing, Request Burst, Unauthorized Access และ Abnormal Checkout',1)
item('กำหนด Log schema, กลุ่มตัวอย่าง, Feature, Threshold และตัวชี้วัดก่อนเริ่มทดลอง',2)
h('2. Design (การออกแบบ)')
item('ออกแบบสถาปัตยกรรม User Activity → Log → Feature Calculation → Threshold Evaluation → Classification → Response',1)
item('ออกแบบฐานข้อมูล activity_logs, baselines, detections และ experiments โดยแยก runtime/simulation',2)

page();h('3. Development (การพัฒนา)')
item('พัฒนาระบบ E-commerce และระบบหลังบ้านที่เกี่ยวข้อง',1)
item('พัฒนาตัวบันทึก Login, Request และ Order พร้อมการคำนวณ Feature ตาม Time Window',2)
item('พัฒนา Alert, Email Notification, Temporary IP/User Block และหน้าวิเคราะห์สำหรับ Admin',3)
item('พัฒนาเครื่องมือสร้างข้อมูล 100 ผู้ใช้/30 วัน วิเคราะห์ Baseline และ Export CSV',4)
h('4. Implementation (การนำไปใช้/ทดลอง)')
item('สร้างข้อมูล Normal 2,400 กรณี และ Suspicious 600 กรณี รวม 3,000 กรณี',1)
item('คำนวณ Mean และ Sample S.D. จาก Normal เท่านั้น และตรวจทุกกรณีด้วย Threshold เดียวกัน',2)
item('บันทึกผลทำนาย เวลาตรวจจับ และทรัพยากรระบบ โดยไม่ใช้ป้าย Actual ช่วยการตัดสิน',3)
h('5. Evaluation (การประเมินผล)')
item('สร้าง Confusion Matrix และคำนวณ Accuracy, Precision, Recall, F1-score และ FPR',1)
item('วิเคราะห์ Detection Time, Response Time, CPU Usage, Peak Memory และ Throughput',2)
item('วิเคราะห์สาเหตุ FP/FN และเสนอแนวทางปรับ Threshold หรือพัฒนาสู่ Machine Learning ในอนาคต',3)
h('ผลการทดลองเบื้องต้นจากระบบที่พัฒนาแล้ว')
table([['รายการ','ผล'],['จำนวนกรณี','3,000'],['TP / TN / FP / FN','540 / 2,375 / 25 / 60'],['Accuracy','97.17%'],['Precision','95.58%'],['Detection Rate / Recall','90.00%'],['F1-score','92.70%'],['False Positive Rate','1.04%'],['Average Detection Time','40 วินาที'],['Average Response Time','50.76 ms'],['Throughput','2,171 cases/sec'],['CPU Usage / Peak Memory','16.96% / 6 MB']])

page();h('7. แผนการดำเนินงาน');p('ตารางที่ 1.1 ระยะเวลาการดำเนินงาน',indent=0)
months=['กิจกรรม','ก.ย.','ต.ค.','พ.ย.','ธ.ค.','ม.ค.','ก.พ.','มี.ค.','เม.ย.']
rows=[months,['1. กำหนดและเสนอหัวข้อ','●','●','','','','','',''],['2. ศึกษาและรวบรวมข้อมูล','','●','●','','','','',''],['3. วิเคราะห์และออกแบบระบบ','','','●','●','','','',''],['4. พัฒนาเว็บไซต์และฐานข้อมูล','','','','●','●','●','',''],['5. พัฒนาระบบตรวจจับ','','','','','●','●','',''],['6. สร้างข้อมูลและทดลอง','','','','','','●','●',''],['7. วิเคราะห์ผลและจัดทำรายงาน','','','','','','','●','●']]
table(rows)
h('8. ประโยชน์ที่คาดว่าจะได้รับ')
item('ได้เว็บไซต์ E-commerce ที่ใช้งานได้ครบถ้วนและมีมาตรการรักษาความปลอดภัยหลายชั้น',1)
item('ได้ระบบตรวจจับพฤติกรรมผิดปกติที่อธิบายเหตุผลได้จาก Feature และ Threshold',2)
item('ผู้ดูแลได้รับการแจ้งเตือนและสามารถตรวจสอบหรือยกเลิกการระงับชั่วคราวได้',3)
item('ได้ชุดข้อมูลทดลองและผลประเมินเชิงสถิติที่สามารถทำซ้ำและ Export ไปวิเคราะห์ต่อได้',4)
item('เป็นแนวทางต่อยอดสู่ Adaptive Threshold, Machine Learning หรือระบบตรวจจับแบบ Streaming',5)
h('9. งานวิจัยที่เกี่ยวข้อง')
table([['ชื่องานวิจัย/แหล่งอ้างอิง','รายละเอียดผลสังเขป','ความสอดคล้องกับโครงงาน'],['Effective fraud detection in e-commerce: Leveraging machine learning and big data analytics (2024)','เสนอการใช้ข้อมูลธุรกรรม การวิเคราะห์รูปแบบและ Anomaly Detection เพื่อลดความเสี่ยงการฉ้อโกง E-commerce','สนับสนุนการใช้ข้อมูลกิจกรรมและธุรกรรมเพื่อจำแนกพฤติกรรมผิดปกติ'],['Real-time anomaly detection using deep learning in e-commerce platform (2025)','ประเมินโมเดลหลายชนิดด้วย Accuracy, Precision, Recall และ F1-score และเน้น Low-latency deployment','สอดคล้องกับการตรวจจับแบบใกล้เวลาจริงและตัวชี้วัดที่ใช้'],['OWASP Automated Threats to Web Applications','จำแนก Credential Cracking, Credential Stuffing, Denial of Inventory, Scraping และ Vulnerability Scanning พร้อมแนวทางควบคุม','ใช้เป็นฐานกำหนดสถานการณ์ Suspicious, Rate Limit, Alert และ Temporary Block']])

page();h('10. บรรณานุกรม (รูปแบบ APA 7)')
p('Al-Hashedi, K. G., & Magalingam, P. (2024). Effective fraud detection in e-commerce: Leveraging machine learning and big data analytics. Measurement: Sensors, 33, 101138. https://doi.org/10.1016/j.measen.2024.101138',indent=0)
p('Guo, Y., Li, Z., Zhang, H., & Liu, Y. (2023). Supervised and hybrid learning-based zero-day attack detection. Computer Communications, 198, 175–185.',indent=0)
p('National Institute of Standards and Technology. (2001). Intrusion detection systems (NIST Special Publication 800-31). U.S. Department of Commerce. https://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-31.pdf',indent=0)
p('Open Worldwide Application Security Project. (n.d.). Automated threats to web applications. https://owasp.org/www-project-automated-threats-to-web-applications/',indent=0)
p('Open Worldwide Application Security Project. (n.d.). Bot management and anti-automation cheat sheet. https://cheatsheetseries.owasp.org/cheatsheets/Bot_Management_and_Anti-Automation_Cheat_Sheet.html',indent=0)
p('Open Worldwide Application Security Project. (n.d.). Credential stuffing prevention cheat sheet. https://cheatsheetseries.owasp.org/cheatsheets/Credential_Stuffing_Prevention_Cheat_Sheet.html',indent=0)
p('Open Worldwide Application Security Project. (n.d.). OAT-007 credential cracking. https://owasp.org/www-project-automated-threats-to-web-applications/assets/oats/EN/OAT-007_Credential_Cracking',indent=0)
p('Real-time anomaly detection using deep learning in e-commerce platform. (2025). Procedia Computer Science, 269, 309–320. https://doi.org/10.1016/j.procs.2025.08.283',indent=0)
p('หมายเหตุ: โปรดกรอกชื่อ-สกุลและรหัสนักศึกษาที่หน้าแรกก่อนส่งอาจารย์',bold=True,indent=0)
d.save(OUT);print(OUT)
