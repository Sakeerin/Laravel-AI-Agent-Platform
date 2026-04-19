# คู่มือการตั้งค่าโปรเจกต์ (AI Agent Platform)

เอกสารนี้สรุปตัวแปรสภาพแวดล้อม (`/.env`) และไฟล์ config หลักของโปรเจกต์เป็นภาษาไทย  
**วิธีรันบนเครื่องตัวเองทีละขั้น:** [RUN_LOCALHOST_TH.md](RUN_LOCALHOST_TH.md)  
สำหรับขั้นตอนติดตั้งแบบภาษาอังกฤษ (รวม Docker) ให้อ่าน [SETUP.md](SETUP.md) และ [README.md](README.md)

---

## 1. แอปพลิเคชันหลัก

| ตัวแปร | ความหมาย |
|--------|-----------|
| `APP_NAME` | ชื่อแอป (แสดงใน UI / mail) |
| `APP_ENV` | `local` / `staging` / `production` |
| `APP_KEY` | คีย์เข้ารหัส Laravel — สร้างด้วย `php artisan key:generate` **ห้ามลืมใน production** (ใช้ decrypt คีย์ API ของผู้ใช้) |
| `APP_DEBUG` | `true` เฉพาะตอนพัฒนา — production ตั้ง `false` |
| `APP_URL` | URL ฐานของแอป ต้องตรงกับที่เปิดในเบราว์เซอร์ (เช่น `http://127.0.0.1:8000`) |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | ภาษาแอปและ Faker |

---

## 2. ฐานข้อมูล

| ตัวแปร | ความหมาย |
|--------|-----------|
| `DB_CONNECTION` | `mysql` (แนะนำสำหรับ dev จริง) หรือ `sqlite` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | การเชื่อมต่อ MySQL |
| `DB_URL` | ทางเลือกแบบ URL เดียว (ถ้าใช้) |

SQLite: ตั้ง `DB_CONNECTION=sqlite` และสร้างไฟล์ `database/database.sqlite` แล้ว migrate

---

## 3. โมเดล AI

| ตัวแปร | ความหมาย |
|--------|-----------|
| `ANTHROPIC_API_KEY` | คีย์ Anthropic (Claude) |
| `OPENAI_API_KEY` | คีย์ OpenAI (GPT, embedding บางส่วน) |
| `OLLAMA_HOST` | URL ของ Ollama (ค่าเริ่มต้น `http://localhost:11434`) |
| `AI_DEFAULT_MODEL` | โมเดลเริ่มต้น เช่น `claude-sonnet` — ต้องสอดคล้องกับ alias ในระบบ (ดูรายการที่ `GET /api/models` เมื่อล็อกอินแล้ว) |

อย่างน้อยต้องมีคีย์ของผู้ให้บริการหนึ่งราย หรือใช้ Ollama ให้ครบทั้ง host และโมเดลที่รองรับ

---

## 4. Session, Cache, Queue, Redis

| ตัวแปร | ความหมาย |
|--------|-----------|
| `SESSION_DRIVER` | ค่าเริ่มต้น `database` — production อาจใช้ `redis` |
| `SESSION_LIFETIME` | นาที |
| `SESSION_DOMAIN` | ใช้เมื่อ SPA กับ API คนละโดเมน (ตั้งให้ถูกต้องกับ cookie) |
| `CACHE_STORE` | `database` หรือ `redis` |
| `QUEUE_CONNECTION` | `database` (ค่าเริ่มต้น) หรือ `redis` — ฟีเจอร์งานหนัก (memory extraction, heartbeat, task) ต้องมี worker |
| `REDIS_*` | ใช้เมื่อเปิด Redis (`REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, ฯลฯ) |

**งานที่ต้องรันแยก:** `php artisan queue:work` และ scheduler (`php artisan schedule:work` หรือ cron `schedule:run`) — รายละเอียดใน [SETUP.md](SETUP.md)

---

## 5. Sanctum และ SPA

| ตัวแปร | ความหมาย |
|--------|-----------|
| `SANCTUM_STATEFUL_DOMAINS` | โดเมนที่ Sanctum ถือว่าเป็น stateful (cookie) — ค่าเริ่มต้นใน `config/sanctum.php` รวม localhost และพอร์ตทั่วไป ถ้า front อยู่คนละโดเมนให้เพิ่มที่นี่ |

แอปนี้ใช้ **Bearer token** จาก Sanctum สำหรับ API — ตั้ง `APP_URL` และ CORS/โดเมนให้สอดคล้องกับที่ deploy จริง

---

## 6. Realtime (Laravel Echo / Soketi / Pusher)

| ตัวแปร | ความหมาย |
|--------|-----------|
| `BROADCAST_CONNECTION` | ตั้ง `pusher` เพื่อเปิด realtime |
| `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` | ข้อมูลแอปฝั่งเซิร์ฟเวอร์ |
| `PUSHER_HOST`, `PUSHER_PORT`, `PUSHER_SCHEME` | สำหรับ **Soketi** self-hosted (เช่น `127.0.0.1`, `6001`, `http`) |
| `VITE_PUSHER_*` | ค่าเดียวกันที่ฝั่งเบราว์เซอร์ — **แก้แล้วต้อง** `npm run build` หรือรีสตาร์ท `npm run dev` |

---

## 7. เครื่องมือค้นหาเว็บ (Web Search)

ตั้งได้ใน `.env` (ยังไม่มีใน `.env.example` ทุกตัว แต่ระบบอ่านจาก config):

| ตัวแปร | ความหมาย |
|--------|-----------|
| `BRAVE_API_KEY` | ใช้ Brave Search API (ลำดับความสำคัญสูงถ้ามี) |
| `GOOGLE_SEARCH_API_KEY` | Google Custom Search — ต้องคู่กับ `GOOGLE_SEARCH_CX` |
| `GOOGLE_SEARCH_CX` | Search Engine ID (cx) |

ถ้าไม่ตั้งทั้งคู่ ระบบจะ fallback เป็น DuckDuckGo (จำกัดความน่าเชื่อถือ/อัตรา)

---

## 8. หน่วยความจำ (Memory) และ Heartbeat

กำหนดใน `config/services.php` ภายใต้ `memory`:

| ตัวแปร | ความหมาย |
|--------|-----------|
| `MEMORY_EMBEDDING_BACKEND` | เช่น `openai:text-embedding-3-small` หรือ `ollama:...` |
| `MEMORY_EXTRACTION_MODEL` | โมเดลสำหรับสกัดความจำจากบทสนทนา (ค่าเริ่มต้น `gpt-4o-mini`) |
| `HEARTBEAT_MODEL` | โมเดลสำหรับงาน heartbeat ของ agent |

Embedding เก็บเป็นเวกเตอร์ใน MySQL (รูปแบบที่แอปคำนวณ cosine) — ดูรายละเอียดใน UI Personalization

---

## 9. Integration ภายนอก (Calendar / Gmail / Notion)

| ตัวแปร | ความหมาย |
|--------|-----------|
| `GOOGLE_CALENDAR_ACCESS_TOKEN` | โทเค็นเข้าถึง Google Calendar (สำหรับเครื่องมือที่เกี่ยวข้อง) |
| `GMAIL_ACCESS_TOKEN` | โทเค็น Gmail |
| `NOTION_INTEGRATION_TOKEN` | โทเค็น Notion integration |

ใน production ควรย้ายไป OAuth ต่อผู้ใช้หรือ vault — ค่า env เหล่านี้เหมาะกับการทดสอบ/ส่วนตัว

---

## 10. Skill Marketplace และ HTTP Webhook Skills

| ตัวแปร | ความหมาย |
|--------|-----------|
| `SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS` | รายชื่อ host ที่อนุญาต (คั่นด้วย comma) — **production ควรจำกัด** ปล่อยว่างใน non-production อาจอนุญาต host ใดก็ได้ |
| `SKILLS_HTTP_WEBHOOK_TIMEOUT` | timeout วินาที (ดู `config/skills.php`) |
| `SKILLS_ALLOW_PREMIUM_WITHOUT_SUBSCRIPTION` | `true`/`false` — ถ้า `false` แพ็ก premium จะติด billing ก่อนติดตั้ง |

---

## 11. ความปลอดภัย (Prompt injection)

| ตัวแปร | ความหมาย |
|--------|-----------|
| `SECURITY_PROMPT_INJECTION_ENABLED` | เปิด/ปิดการกรองข้อความผู้ใช้ |
| `SECURITY_PROMPT_INJECTION_MODE` | `block` / `sanitize` / `off` |
| `SECURITY_SYSTEM_HARDENING` | เพิ่มบรรทัด hardening ใน system prompt |

รายละเอียดเชิงลึกอยู่ที่ `config/security.php`

---

## 12. เครื่องมือเบราว์เซอร์ (Playwright — ภาพหน้าจอ)

| ตัวแปร | ความหมาย |
|--------|-----------|
| `BROWSER_PLAYWRIGHT_ENABLED` | `true` เพื่อให้ action `screenshot` ของเครื่องมือ browser เรียก Playwright ผ่าน `npx` |
| `BROWSER_PLAYWRIGHT_TIMEOUT` | วินาที |
| `BROWSER_PLAYWRIGHT_VIEWPORT` | เช่น `1280,720` |
| `BROWSER_NPX_BINARY` | ค่าเริ่มต้น `npx` — บน Windows บางเครื่องอาจต้องเป็น `npx.cmd` |

ต้องมี **Node.js** บนเครื่องที่รัน PHP และรัน `npx playwright install chromium`  
ภาพ Docker production มาตรฐานของโปรเจกต์ **ไม่มี Node** — ฟีเจอร์นี้ใช้บนเครื่อง dev หรือปรับ image เอง

---

## 13. การวิเคราะห์การใช้งาน (ต้นทุนโดยประมาณ)

ไฟล์ `config/pricing.php` กำหนด **USD ต่อ 1M tokens** ตาม prefix โมเดล — ใช้ในหน้า Analytics (ไม่ใช่บิลจริง)  
แก้ไฟล์นี้เมื่อต้องการปรับอัตราประมาณการ

---

## 14. Sentry และ Telescope

| ตัวแปร | ความหมาย |
|--------|-----------|
| `SENTRY_LARAVEL_DSN` | เปิดรายงาน error ไป Sentry เมื่อใส่ DSN |
| `SENTRY_TRACES_SAMPLE_RATE` | อัตรา sampling ของ trace |
| `TELESCOPE_ENABLED` | `true` เฉพาะ local — เปิด Telescope หลัง migrate ตาราง Telescope แล้วเข้า `/telescope` |

---

## 15. อีเมลและไฟล์ (ทั่วไป)

| ตัวแปร | ความหมาย |
|--------|-----------|
| `MAIL_*` | ตั้ง mailer จริงเมื่อต้องส่งอีเมล (ค่าเริ่มต้น `log`) |
| `FILESYSTEM_DISK` | ดิสก์หลักของ storage |
| `AWS_*` | ใช้เมื่อเก็บไฟล์บน S3 |

---

## 16. Docker และ Production

- ไฟล์: `docker-compose.prod.yml`, `Dockerfile`  
- ใน `.env` บน Docker มักตั้ง `DB_HOST=mysql` และเปิด `QUEUE_CONNECTION=redis` / `CACHE_STORE=redis` ตามแนวทางใน `.env.example`  
- พอร์ตแอปเริ่มต้น `8080` (ดู `APP_PORT` ใน compose)

---

## 17. Checklist ก่อนขึ้น Production

- [ ] `APP_ENV=production`, `APP_DEBUG=false`  
- [ ] `APP_KEY` คงที่และสำรองอย่างปลอดภัย  
- [ ] จำกัด `SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS`  
- [ ] ตรวจ `SANCTUM_STATEFUL_DOMAINS` และ HTTPS  
- [ ] รัน queue worker + scheduler  
- [ ] Health check: `GET /up` (เช่น Uptime Robot)

---

หากตัวแปรใน `.env` ไม่ตรงกับที่อธิบาย ให้ถือ **`/.env.example` และไฟล์ใน `config/`** เป็นข้อมูลอ้างอิงล่าสุด
