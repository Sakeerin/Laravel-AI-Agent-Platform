# คู่มือรันโปรเจกต์บน Localhost (แบบละเอียด)

เอกสารนี้อธิบายทีละขั้นตอนวิธีเปิดโปรเจกต์ **AI Agent Platform** บนเครื่องตัวเองให้ใช้งานได้  
สำหรับความหมายของตัวแปรใน `.env` แต่ละตัว ดู [CONFIGURATION_TH.md](CONFIGURATION_TH.md)  
คู่มือภาษาอังกฤษแบบย่อ ดู [SETUP.md](SETUP.md)

---

## สิ่งที่ต้องมีบนเครื่อง (ก่อนเริ่ม)

| ซอฟต์แวร์ | เวอร์ชัน / หมายเหตุ |
|-----------|---------------------|
| **PHP** | 8.2 ขึ้นไป และเปิด extension: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `curl`, `intl`, `zip` และ **`pdo_mysql`** (ถ้าใช้ MySQL) หรือ **`pdo_sqlite`** (ถ้าใช้ SQLite) |
| **Composer** | 2.x — จัดการแพ็กเกจ PHP |
| **Node.js** | 20 ขึ้นไป (แนะนำ 22 เพื่อใกล้เคียง CI) — รัน Vite / หน้า Vue |
| **ฐานข้อมูล** | **MySQL 8** (แนะนำถ้าทำจริง) หรือ **SQLite** (เริ่มเร็ว ไม่ต้องลง MySQL) |
| **Git** | สำหรับ clone โปรเจกต์ |

**บน Windows:** ติดตั้ง PHP/Composer/Node แยก หรือใช้ Laragon / XAMPP (มี MySQL) แล้วเพิ่ม PHP + Composer ให้เรียกจาก PowerShell ได้

ตรวจว่าเรียกคำสั่งได้:

```bash
php -v
composer -V
node -v
npm -v
```

---

## ภาพรวม: หลังรันสำเร็จจะมีอะไรบ้าง

1. **เว็บแอป** — เปิดจากเบราว์เซอร์ (ปกติ `http://127.0.0.1:8000`)
2. **Vite** — รัน `npm run dev` เพื่อแก้ไฟล์ Vue แล้วเห็นผลทันที (hot reload)
3. **Queue worker** (แนะนำ) — งานหลังบ้าน เช่น สกัดความจำจากแชท, งาน tool บางอย่าง
4. **Scheduler** (ถ้าต้องการ heartbeat ตามเวลา) — รัน `schedule:work` แยกต่างหาก หรือใช้ cron บนเซิร์ฟเวอร์จริง

---

## เส้นทาง A — เริ่มเร็วด้วย SQLite (เหมาะกับลองครั้งแรก)

ไม่ต้องติดตั้ง MySQL แค่ใช้ไฟล์ `.sqlite` ในโฟลเดอร์โปรเจกต์

### ขั้นที่ 1: เข้าโฟลเดอร์โปรเจกต์

```bash
cd Laravel-AI-Agent-Platform
```

(เปลี่ยนเป็นพาธจริงของคุณ)

### ขั้นที่ 2: ติดตั้งแพ็กเกจ PHP

```bash
composer install
```

### ขั้นที่ 3: สร้างไฟล์ `.env` และคีย์แอป

**Linux / macOS / Git Bash:**

```bash
cp .env.example .env
php artisan key:generate
```

**Windows (PowerShell):**

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### ขั้นที่ 4: ตั้งค่า SQLite ใน `.env`

เปิดไฟล์ `.env` แล้วแก้ส่วนฐานข้อมูลให้เป็นแบบนี้ (สำคัญ: ถ้าคัดลอกจาก `.env.example` ที่มี `DB_DATABASE=ai_agent_platform` อยู่ **ต้องเปลี่ยน** ไม่เช่นนั้น Laravel จะหาไฟล์ผิด):

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

บรรทัด `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` ใช้กับ MySQL — ตอนใช้ SQLite คุณคอมเมนต์ทิ้งได้หรือปล่อยไว้ก็ได้ (มักไม่กระทบ) แต่ **`DB_DATABASE` ต้องชี้ไปที่ไฟล์ `.sqlite`**

สร้างไฟล์ฐานข้อมูลว่าง:

**Linux / macOS:**

```bash
touch database/database.sqlite
```

**Windows (PowerShell):**

```powershell
New-Item -Path database\database.sqlite -ItemType File -Force
```

### ขั้นที่ 5: ตั้งค่าแอปและ URL

ใน `.env`:

```env
APP_URL=http://127.0.0.1:8000
```

(ถ้าคุณใช้พอร์ตอื่นใน `php artisan serve --port=xxxx` ให้แก้ `APP_URL` ให้ตรง)

### ขั้นที่ 6: ใส่คีย์ AI อย่างน้อยหนึ่งช่องทาง

อย่างน้อยหนึ่งอย่าง:

- `ANTHROPIC_API_KEY=` หรือ
- `OPENAI_API_KEY=` หรือ
- รัน [Ollama](https://ollama.com/) บนเครื่อง แล้วตั้ง `OLLAMA_HOST=http://127.0.0.1:11434` และเลือกโมเดลใน UI ให้เป็นโมเดล Ollama (เช่น `llama3.1`)

ตั้งโมเดลเริ่มต้น (ถ้าต้องการ):

```env
AI_DEFAULT_MODEL=claude-sonnet
```

(ถ้าใช้ Ollama เป็นหลัก ให้เปลี่ยนเป็น alias ที่ระบบรองรับ เช่น `llama3.1`)

### ขั้นที่ 7: สร้างตารางในฐานข้อมูล

```bash
php artisan migrate
```

ถ้าต้องการข้อมูลตัวอย่างจาก seeder (ถ้ามีในโปรเจกต์):

```bash
php artisan db:seed
```

### ขั้นที่ 8: ติดตั้งแพ็กเกจ frontend

```bash
npm install
```

### ขั้นที่ 9: รันแอป (แยกหลายเทอร์มินัล)

**เทอร์มินัล 1 — Laravel**

```bash
php artisan serve
```

ค่าเริ่มต้นคือ `http://127.0.0.1:8000`

**เทอร์มินัล 2 — Vite**

```bash
npm run dev
```

**เทอร์มินัล 3 (แนะนำ) — Queue**

```bash
php artisan queue:work
```

**เทอร์มินัล 4 (ถ้าต้องการงานตามเวลา เช่น heartbeat)**  

สคริปต์ `composer run dev` **ไม่ได้** เปิด scheduler ให้ ต้องรันเอง:

```bash
php artisan schedule:work
```

### ขั้นที่ 10: เปิดเบราว์เซอร์

ไปที่ **http://127.0.0.1:8000** → สมัครสมาชิก → ล็อกอิน → ลองแชท

---

## เส้นทาง B — ใช้ MySQL (ใกล้เคียง production)

### 1) สร้างฐานข้อมูลใน MySQL

ตัวอย่าง (รันใน MySQL client):

```sql
CREATE DATABASE ai_agent_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'app'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL ON ai_agent_platform.* TO 'app'@'localhost';
FLUSH PRIVILEGES;
```

### 2) ตั้งค่าใน `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_agent_platform
DB_USERNAME=app
DB_PASSWORD=your_password
```

จากนั้นทำตาม **เส้นทาง A** ตั้งแต่ `composer install`, `.env`, `key:generate`, `migrate`, `npm install`, รัน `serve` + `npm run dev` (+ queue / schedule ตามต้องการ)

---

## วิธีรันแบบ “คำสั่งเดียว” (สำหรับพัฒนาประจำวัน)

หลังตั้งค่า `.env` และ migrate ครั้งแรกแล้ว คุณสามารถใช้:

```bash
composer run dev
```

คำสั่งนี้จะรันพร้อมกัน:

- `php artisan serve`
- `php artisan queue:listen` (worker)
- `php artisan pail` (ดู log)
- `npm run dev`

**หมายเหตุ:** ยัง **ไม่รวม** `schedule:work` — ถ้าต้องการ heartbeat ตามเวลา ให้เปิดเทอร์มินัลเพิ่มแล้วรัน `php artisan schedule:work`

---

## คำสั่งติดตั้งครั้งแรกแบบอัตโนมัติ (ทางเลือก)

```bash
composer run setup
```

จะทำโดยประมาณ: `composer install`, สร้าง `.env` ถ้ายังไม่มี, `key:generate`, `migrate --force`, `npm install`, `npm run build`

**ข้อควรระวัง:**

- ต้องตั้งค่า **DB ใน `.env` ให้ถูกต้องก่อน** ไม่งั้น migrate จะล้มเหลว
- หลัง `setup` คุณยังต้องใส่ **คีย์ AI** ใน `.env` เอง แชทถึงจะเรียกโมเดลได้
- `setup` จะ `npm run build` ไม่ได้เปิด `npm run dev` — ตอนพัฒนาปกติยังใช้ `npm run dev` ตามเดิม

---

## ตรวจสอบว่าระบบพร้อม

รันตามลำดับ:

```bash
php artisan about
```

เปิดเบราว์เซอร์ไป **http://127.0.0.1:8000/up** — ควรเห็นสถานะปกติ (health check)

รันเทส (ไม่บังคับ):

```bash
php artisan test
```

---

## ปัญหาที่พบบ่อย (Localhost)

| อาการ | สิ่งที่ลอง |
|--------|------------|
| หน้าเว็บ error 500 | ดู `storage/logs/laravel.log` — บ่อยที่สุดคือ ยังไม่มี `APP_KEY` (`php artisan key:generate`) หรือ DB ผิด |
| แชท error / timeout | ตรวจ `ANTHROPIC_API_KEY` / `OPENAI_API_KEY` / Ollama เปิดอยู่ และ `AI_DEFAULT_MODEL` ตรงกับโมเดลที่มี |
| หน้าเว็บไม่มีสไตล์ / JS พัง | ต้องรัน **`npm run dev`** ระหว่างพัฒนา หรือรัน **`npm run build`** แล้วรีเฟรช |
| Queue ไม่ทำงาน | เปิดเทอร์มินัลรัน `php artisan queue:work` หรือใช้ `composer run dev` |
| Windows: คำสั่ง `cp` ไม่ได้ | ใช้ `Copy-Item` ใน PowerShell หรือใช้ Git Bash |
| SQLite: migrate บ่นว่าไม่มีไฟล์ | สร้าง `database/database.sqlite` ตามขั้นตอนด้านบน |

---

## ขั้นตอนถัดไป (ไม่บังคับสำหรับ localhost)

- **Realtime (อัปเดต task แบบสด):** ตั้ง `BROADCAST_CONNECTION=pusher` และค่า `PUSHER_*` / `VITE_PUSHER_*` — ดู [SETUP.md](SETUP.md) ส่วน Soketi  
- **ช่องทาง LINE / Telegram:** ตั้งค่าในเมนู Channels ในแอป (webhook บน localhost มักต้องใช้เครื่องมืออย่าง ngrok)  
- **รันแบบ Docker:** ดู `docker-compose.prod.yml` และ [SETUP.md](SETUP.md) — เน้น production มากกว่า dev บนเครื่อง

---

สรุปสั้นๆ: **ติดตั้ง dependency → `.env` + DB → migrate → ใส่คีย์ AI → เปิด `php artisan serve` + `npm run dev` (+ `queue:work`)** แล้วเปิด **http://127.0.0.1:8000**
