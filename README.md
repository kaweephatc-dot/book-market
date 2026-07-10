📚 Book Market — ตลาดกลางซื้อขายแลกเปลี่ยนหนังสือมือสอง

เว็บแอปพลิเคชันตลาดกลางสำหรับซื้อ ขาย และแลกเปลี่ยนหนังสือมือสอง พัฒนาแบบ Full-stack ด้วย Laravel — โปรเจคจบการศึกษา สาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏนครสวรรค์


✨ ฟีเจอร์หลัก


👤 ระบบสมาชิก — สมัคร / เข้าสู่ระบบ / จัดการโปรไฟล์
🏪 ระบบร้านค้าผู้ขาย — เปิดร้าน ลงขายหนังสือ จัดการสินค้าของตัวเอง
💬 ระบบแชท — พูดคุยระหว่างผู้ซื้อและผู้ขายภายในเว็บ
💳 ระบบจัดการการชำระเงิน — บันทึกและติดตามสถานะการชำระเงิน
🤖 วิเคราะห์สภาพหนังสือด้วย AI — ประเมินสภาพหนังสือจากรูปภาพ เพื่อเพิ่มความน่าเชื่อถือในการซื้อขาย
🛠️ Admin Panel — หน้าจัดการระบบสำหรับผู้ดูแล


🧰 เทคโนโลยีที่ใช้

ส่วนเทคโนโลยีBackendPHP, Laravel 12FrontendBlade, Tailwind CSS 4, JavaScript (Vite)ฐานข้อมูลMySQL DeploymentRailway

🚀 การติดตั้งเพื่อรันบนเครื่อง (Local Development)

bash
# 1. Clone โปรเจค
git clone https://github.com/kaweephatc-dot/book-market.git
cd book-market

# 2. ติดตั้ง dependencies
composer install
npm install

# 3. ตั้งค่า environment
cp .env.example .env
php artisan key:generate
# แก้ค่าเชื่อมต่อฐานข้อมูลในไฟล์ .env ให้ตรงกับเครื่องของคุณ

# 4. สร้างตารางฐานข้อมูล
php artisan migrate

# 5. รันโปรเจค
composer run dev

เปิดเบราว์เซอร์ที่ http://localhost:8000

👨‍💻 ผู้พัฒนา

กวีพัฒน์ ชูชิต (Kaweephat Chuchit)
นักศึกษาสาขาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยราชภัฏนครสวรรค์
kaweephat.c@nsru.ac.th
