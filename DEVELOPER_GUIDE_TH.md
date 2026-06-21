# คู่มือสำหรับนักพัฒนา — AI Agent Platform

คู่มือนี้สำหรับนักพัฒนาที่ต้องการนำโปรเจกต์ **Laravel-AI-Agent-Platform** ไปติดตั้ง พัฒนา ทดสอบ และต่อยอดใช้งานจริง  
โปรเจกต์นี้เป็น Laravel 12 + Vue 3 สำหรับ AI chat, tool execution, channel integrations, memory/personalization, skill marketplace และ analytics

เอกสารที่เกี่ยวข้อง:

- `README.md` — ภาพรวมโปรเจกต์แบบสั้น
- `SETUP.md` — setup guide ภาษาอังกฤษ
- `RUN_LOCALHOST_TH.md` — คู่มือรัน localhost ภาษาไทยแบบละเอียด
- `CONFIGURATION_TH.md` — อธิบาย `.env`/config ภาษาไทย
- `implementation_plan.md` — roadmap Phase 1–6
- `PHASE_1_5_IMPLEMENTATION_SUMMARY.md` — สรุป implementation Phase 1–5 และไฟล์/ฟังก์ชันที่เกี่ยวข้อง

---

## 1. ภาพรวมระบบ

ระบบประกอบด้วยส่วนหลัก:

- **Backend API:** Laravel 12, Sanctum, Queue, Scheduler, Events
- **Frontend SPA:** Vue 3, Vite, Pinia, Vue Router, Tailwind CSS
- **AI Core:** Anthropic, OpenAI, Ollama ผ่าน provider layer
- **Tools / Skills:** web search, browser, filesystem, shell, weather, stock, Google Calendar, Gmail, Notion, custom webhook skills
- **Channels:** LINE, Telegram, Slack, Discord ผ่าน webhook
- **Memory:** user memories, persona, auto extraction, heartbeat reminders
- **Marketplace:** skill packages, install/uninstall, custom skill builder
- **Ops:** Sentry, Telescope, usage analytics, Docker production files, Azure Pipeline template

Flow หลักของ chat:

1. Frontend ส่งข้อความไป `POST /api/chat`
2. `ChatController` validate และบันทึก user message
3. `AgentOrchestrator` สร้าง history, system prompt, tools, memory
4. `AIManager` เลือก provider เช่น Anthropic/OpenAI/Ollama
5. ถ้า AI ขอใช้ tool จะเรียก `ToolExecutor`
6. บันทึก assistant message, tool calls, usage และ dispatch memory extraction

---

## 2. Requirements

ต้องมีบนเครื่องก่อนเริ่ม:

| เครื่องมือ | เวอร์ชัน / หมายเหตุ |
|---|---|
| PHP | 8.2 ขึ้นไป |
| Composer | 2.x |
| Node.js | 20+ แนะนำ 22+ |
| npm | มากับ Node.js |
| Database | MySQL 8 แนะนำ, หรือ SQLite สำหรับลองเร็ว |
| Git | ใช้ clone/pull/push |
| Redis | optional แต่แนะนำสำหรับ production queue/cache |
| Ollama | optional ถ้าต้องการ local model |
| Docker | optional สำหรับ production-like deployment |

PHP extensions ที่ควรมี:

- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql` หรือ `pdo_sqlite`
- `tokenizer`
- `xml`
- `curl`
- `intl`
- `zip`
- `fileinfo`

ตรวจเวอร์ชัน:

```bash
php -v
composer -V
node -v
npm -v
```

---

## 3. Clone และติดตั้ง Dependencies

```bash
git clone <repo-url> Laravel-AI-Agent-Platform
cd Laravel-AI-Agent-Platform
composer install
npm install
```

สำหรับ production-like install:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

---

## 4. สร้างและตั้งค่า `.env`

Linux/macOS/Git Bash:

```bash
cp .env.example .env
php artisan key:generate
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

ค่าขั้นต่ำที่ต้องตรวจ:

```env
APP_NAME="AI Agent Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_agent_platform
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

AI_DEFAULT_MODEL=claude-sonnet
ANTHROPIC_API_KEY=
OPENAI_API_KEY=
OLLAMA_HOST=http://localhost:11434
```

ข้อสำคัญ:

- ห้าม commit `.env`
- `APP_KEY` ต้องไม่เปลี่ยนใน production เพราะใช้กับ encrypted casts / stored secrets
- ถ้าใช้ Vite dev server ให้เปิด `npm run dev` แยกอีก terminal
- ถ้าแก้ตัวแปร `VITE_*` ต้อง restart `npm run dev` หรือ build ใหม่

---

## 5. ตั้งค่าฐานข้อมูล

### 5.1 MySQL (แนะนำสำหรับพัฒนาจริง)

สร้าง database:

```sql
CREATE DATABASE ai_agent_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'app'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL ON ai_agent_platform.* TO 'app'@'localhost';
FLUSH PRIVILEGES;
```

ตั้งค่า `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_agent_platform
DB_USERNAME=app
DB_PASSWORD=your_password
```

### 5.2 SQLite (สำหรับลองเร็ว)

ตั้งค่า `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

สร้างไฟล์ database:

```bash
touch database/database.sqlite
```

Windows PowerShell:

```powershell
New-Item -Path database\database.sqlite -ItemType File -Force
```

### 5.3 Run migrations และ seeders

```bash
php artisan migrate
php artisan db:seed
```

Seeder สำคัญ:

- `DatabaseSeeder` — สร้าง test user และเรียก `SkillPackageSeeder`
- `SkillPackageSeeder` — seed marketplace packages เช่น Weather, Stocks, Google Calendar, Gmail, Notion

ถ้าต้อง sync built-in tools ลงตาราง `skills`:

```bash
php artisan skills:sync
```

---

## 6. รันโปรเจกต์บนเครื่อง

เปิดหลาย terminal:

Terminal 1 — Laravel server:

```bash
php artisan serve
```

Terminal 2 — Vite dev server:

```bash
npm run dev
```

Terminal 3 — Queue worker:

```bash
php artisan queue:work
```

Terminal 4 — Scheduler (ถ้าต้องการ heartbeat/reminder):

```bash
php artisan schedule:work
```

เปิด browser:

```text
http://127.0.0.1:8000
```

สมัครสมาชิก → login → เริ่ม chat

### คำสั่งเดียวสำหรับ dev

ถ้าติดตั้ง dev dependencies แล้ว:

```bash
composer run dev
```

คำสั่งนี้รัน:

- `php artisan serve`
- `php artisan queue:listen`
- `php artisan pail`
- `npm run dev`

หมายเหตุ: `composer run dev` ไม่ได้รัน scheduler ถ้าต้องใช้ heartbeat ให้เปิด `php artisan schedule:work` แยก

---

## 7. คำสั่งที่ใช้บ่อย

| คำสั่ง | ใช้ทำอะไร |
|---|---|
| `php artisan serve` | เปิด Laravel dev server |
| `npm run dev` | เปิด Vite HMR |
| `npm run build` | build frontend production assets |
| `php artisan migrate` | รัน migrations |
| `php artisan migrate:fresh --seed` | reset DB แล้ว seed ใหม่ |
| `php artisan db:seed` | รัน seeders |
| `php artisan queue:work` | รัน queue worker |
| `php artisan schedule:work` | รัน scheduler แบบ dev |
| `php artisan skills:sync` | sync built-in tools ลงตาราง skills |
| `php artisan test` | รัน PHPUnit tests |
| `composer run test` | clear config แล้วรัน tests |
| `php artisan config:clear` | clear config cache |
| `php artisan route:list` | ดู routes ทั้งหมด |
| `php artisan pail` | ดู logs แบบ realtime |

---

## 8. การตั้งค่า AI Providers

ไฟล์ที่เกี่ยวข้อง:

- `config/services.php`
- `app/Services/AI/AIManager.php`
- `app/Services/AI/AnthropicProvider.php`
- `app/Services/AI/OpenAIProvider.php`
- `app/Services/AI/OllamaProvider.php`

### Anthropic

```env
ANTHROPIC_API_KEY=sk-ant-...
AI_DEFAULT_MODEL=claude-sonnet
```

Aliases หลัก:

- `claude-sonnet`
- `claude-haiku`
- `claude-opus`

### OpenAI

```env
OPENAI_API_KEY=sk-...
AI_DEFAULT_MODEL=gpt-4o-mini
```

Aliases หลัก:

- `gpt-4o`
- `gpt-4o-mini`
- `gpt-4-turbo`

### Ollama

ติดตั้ง Ollama และ pull model ก่อน เช่น:

```bash
ollama pull llama3.1
```

ตั้งค่า:

```env
OLLAMA_HOST=http://localhost:11434
AI_DEFAULT_MODEL=llama3.1
```

ดูโมเดลที่ระบบ expose:

```http
GET /api/models
```

ต้อง login และแนบ Bearer token ก่อนเรียก API นี้

---

## 9. Queue, Scheduler และ Background Jobs

ฟีเจอร์ที่พึ่ง queue:

- memory extraction หลัง AI ตอบ
- async tool call / task execution
- heartbeat/reminder jobs
- channel tasks บางส่วน

สำหรับ local:

```bash
php artisan queue:work
```

สำหรับ production:

- ใช้ Supervisor/Systemd หรือ container worker
- แนะนำ Redis queue:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

Scheduler:

```bash
php artisan schedule:work
```

หรือ cron บน server:

```cron
* * * * * cd /path/to/Laravel-AI-Agent-Platform && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Frontend Development

ไฟล์หลัก:

- `resources/js/app.js`
- `resources/js/router/index.js`
- `resources/js/api/client.js`
- `resources/js/stores/*.js`
- `resources/js/views/*.vue`
- `resources/js/components/*.vue`

รัน dev:

```bash
npm run dev
```

build:

```bash
npm run build
```

หลักการทำงาน:

- `router/index.js` กำหนด routes และ auth guard
- `api/client.js` เป็น Axios client และแนบ Bearer token จาก `localStorage`
- Pinia stores เก็บ state แยกตาม feature เช่น auth/chat/skills/marketplace/channels/personalization
- Views คือหน้าหลัก เช่น Chat, Skills, Marketplace, Channels, Personalization, Analytics

---

## 11. Auth และ API Token

ระบบใช้ Laravel Sanctum แบบ Bearer token

Routes หลัก:

```http
POST /api/register
POST /api/login
POST /api/logout
GET  /api/user
```

Flow:

1. Login/register ได้ token
2. Frontend เก็บใน `localStorage` key `auth_token`
3. `api/client.js` แนบ header:

```http
Authorization: Bearer <token>
```

ข้อควรระวัง:

- อย่า log token ใน console/server logs
- production ควรใช้ HTTPS เท่านั้น
- ถ้าเปลี่ยน auth strategy ในอนาคต ให้ปรับ `resources/js/stores/auth.js` และ `api/client.js`

---

## 12. Tools และ Skill Engine

ไฟล์สำคัญ:

- `app/Services/Tools/BaseTool.php`
- `app/Services/Tools/ToolRegistry.php`
- `app/Services/Tools/ToolExecutor.php`
- `app/Services/Tools/ToolContext.php`
- `app/Providers/AIServiceProvider.php`

เพิ่ม native tool ใหม่:

1. สร้าง class ใน `app/Services/Tools/BuiltIn`
2. extends `BaseTool`
3. implement:
   - `name()`
   - `displayName()`
   - `description()`
   - `category()`
   - `parametersSchema()`
   - `execute()`
4. register ใน `AIServiceProvider`
5. รัน:

```bash
php artisan skills:sync
```

ตัวอย่าง skeleton:

```php
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class MyTool extends BaseTool
{
    public function name(): string { return 'my_tool'; }
    public function displayName(): string { return 'My Tool'; }
    public function description(): string { return 'Short description for the AI.'; }
    public function category(): string { return 'utility'; }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'input' => ['type' => 'string', 'description' => 'Input value'],
            ],
            'required' => ['input'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        return ['success' => true, 'result' => 'OK'];
    }
}
```

Security notes:

- Shell/File tools ต้องอยู่ใน sandbox
- Shell tool มี allow-list และ dangerous pattern block
- Browser fetch block localhost/private IP เพื่อลด SSRF
- Production ควรเพิ่ม Docker/container isolation สำหรับ tools ที่เสี่ยง

---

## 13. Skill Marketplace และ Custom Webhook Skills

ไฟล์สำคัญ:

- `app/Services/Marketplace/MarketplaceService.php`
- `app/Services/Marketplace/SkillManifestValidator.php`
- `app/Services/Marketplace/HttpWebhookSkillExecutor.php`
- `app/Http/Controllers/Api/MarketplaceController.php`
- `app/Http/Controllers/Api/CustomSkillController.php`
- `resources/js/views/Marketplace.vue`

Routes:

```http
GET    /api/marketplace/manifest-schema
GET    /api/marketplace/packages
GET    /api/marketplace/packages/{package}
POST   /api/marketplace/packages/{package}/install
DELETE /api/marketplace/packages/{package}/install
GET    /api/marketplace/my-installs

POST   /api/skills/custom
PATCH  /api/skills/custom/{skill}
DELETE /api/skills/custom/{skill}
POST   /api/skills/{skill}/rollback
```

ตั้งค่า marketplace:

```env
SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS=api.example.com,hooks.example.com
SKILLS_ALLOW_PREMIUM_WITHOUT_SUBSCRIPTION=true
```

ตัวอย่าง custom skill manifest แบบ JSON:

```json
{
  "schema_version": 1,
  "name": "send_to_crm",
  "display_name": "Send to CRM",
  "description": "Send a short lead summary to the CRM webhook.",
  "category": "sales",
  "version": "1.0.0",
  "parameters_schema": {
    "type": "object",
    "properties": {
      "lead_name": { "type": "string" },
      "summary": { "type": "string" }
    },
    "required": ["lead_name", "summary"]
  },
  "execution": {
    "type": "http_webhook",
    "endpoint": "https://api.example.com/ai/lead",
    "method": "POST",
    "headers": {},
    "timeout_seconds": 30
  }
}
```

ส่งเข้า API:

```http
POST /api/skills/custom
Content-Type: application/json
Authorization: Bearer <token>

{
  "manifest": { ... }
}
```

หรือ YAML:

```http
POST /api/skills/custom

{
  "manifest_yaml": "schema_version: 1\nname: send_to_crm\n..."
}
```

---

## 14. Memory และ Personalization

ไฟล์สำคัญ:

- `app/Support/AgentSettings.php`
- `app/Services/Memory/EmbeddingService.php`
- `app/Services/Memory/MemoryExtractionService.php`
- `app/Services/Memory/MemoryPromptBuilder.php`
- `app/Http/Controllers/Api/MemoryController.php`
- `app/Http/Controllers/Api/AgentSettingsController.php`
- `resources/js/views/Personalization.vue`

ตั้งค่า:

```env
MEMORY_EMBEDDING_BACKEND=openai:text-embedding-3-small
MEMORY_EXTRACTION_MODEL=gpt-4o-mini
HEARTBEAT_MODEL=gpt-4o-mini
```

ถ้าใช้ OpenAI embedding ต้องมี:

```env
OPENAI_API_KEY=...
```

ถ้าใช้ Ollama embedding:

```env
MEMORY_EMBEDDING_BACKEND=ollama:nomic-embed-text
OLLAMA_HOST=http://localhost:11434
```

ฟีเจอร์ที่เกี่ยวข้อง:

- Memory auto extraction หลัง AI ตอบ
- Memory recall ใน system prompt
- Persona setting
- Context window max messages
- Heartbeat reminders

ต้องมี queue worker:

```bash
php artisan queue:work
```

---

## 15. Channel Integrations

ไฟล์สำคัญ:

- `app/Http/Controllers/Api/ChannelConnectionController.php`
- `app/Http/Controllers/Api/Webhooks/*WebhookController.php`
- `app/Services/Channels/ChannelChatService.php`
- `app/Services/Channels/ChannelOutboundRouter.php`
- `resources/js/views/Channels.vue`

Routes webhook:

```http
POST /api/webhooks/line/{webhook_key}
POST /api/webhooks/telegram/{webhook_key}
POST /api/webhooks/slack/{webhook_key}
POST /api/webhooks/discord/{webhook_key}
```

การตั้งค่า:

1. Login เข้า UI
2. ไปหน้า Channels
3. เพิ่ม provider ที่ต้องการ
4. ใส่ credentials เช่น LINE channel secret/token, Telegram bot token, Slack signing secret/bot token, Discord public key/application id
5. นำ webhook URL ที่ระบบแสดงไปตั้งใน provider

Telegram มี endpoint ช่วย register webhook:

```http
POST /api/channel-connections/{id}/telegram/webhook
```

ข้อควรระวัง:

- ต้องตั้ง `APP_URL` ให้เป็น public URL ที่ provider เรียกถึงได้
- Local development อาจใช้ tunnel เช่น ngrok/cloudflared
- Webhook secrets/tokens เป็นความลับ ห้าม commit

---

## 16. Realtime, Broadcasting, Soketi/Pusher

เปิด broadcasting:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

VITE_PUSHER_APP_KEY=app-key
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_CLUSTER=mt1
```

หลังแก้ `VITE_*`:

```bash
npm run dev
```

หรือ:

```bash
npm run build
```

---

## 17. Browser Tool และ Playwright Screenshot

Browser tool รองรับ:

- `fetch` — ดึง HTML แล้วแปลงเป็น text
- `screenshot` — ใช้ Playwright CLI ผ่าน `npx`

เปิด screenshot:

```env
BROWSER_PLAYWRIGHT_ENABLED=true
BROWSER_PLAYWRIGHT_TIMEOUT=45
BROWSER_PLAYWRIGHT_VIEWPORT=1280,720
BROWSER_NPX_BINARY=npx
```

ติดตั้ง browser:

```bash
npx playwright install chromium
```

Windows บางเครื่องอาจต้องใช้:

```env
BROWSER_NPX_BINARY=npx.cmd
```

หมายเหตุ:

- Docker production image ปกติอาจไม่มี Node/Playwright
- ถ้าต้องใช้ screenshot ใน production ให้ปรับ image เอง

---

## 18. Monitoring, Debugging และ Analytics

### Logs

```bash
php artisan pail
```

หรือดู:

```text
storage/logs/laravel.log
```

### Health check

```http
GET /up
```

### Telescope

เปิดใน local:

```env
TELESCOPE_ENABLED=true
```

จากนั้นเข้า:

```text
/telescope
```

### Sentry

```env
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Usage Analytics

ไฟล์สำคัญ:

- `app/Http/Controllers/Api/AnalyticsController.php`
- `app/Services/Analytics/UsageRecorder.php`
- `app/Services/Analytics/TokenCostEstimator.php`
- `resources/js/views/Analytics.vue`

Routes:

```http
GET /api/analytics/summary
GET /api/analytics/timeseries
```

---

## 19. Testing

รัน tests:

```bash
php artisan test
```

หรือ:

```bash
composer run test
```

รัน frontend build:

```bash
npm run build
```

ก่อน push แนะนำ:

```bash
php artisan test
npm run build
```

ผลล่าสุดที่เคยตรวจ:

```text
Tests: 10 passed (14 assertions)
```

---

## 20. Coding Conventions

แนวทาง backend:

- ใช้ Laravel conventions: Controller บาง, Service สำหรับ business logic
- Validate input ใน Controller ก่อนส่งต่อ service
- ตรวจ owner ก่อนเข้าถึง resource ของ user เช่น conversation/memory/channel
- อย่า hardcode secrets
- ใช้ config/env ผ่าน `config(...)`
- ถ้าสร้าง tool ใหม่ ต้องมี JSON schema ชัดเจน
- ถ้าสร้าง queue job ใหม่ ควรคิดเรื่อง retry/timeout/failure behavior

แนวทาง frontend:

- เก็บ API state ใน Pinia store
- View รับผิดชอบ UI/layout
- Components ใช้ซ้ำเมื่อมี pattern ชัดเจน
- เรียก API ผ่าน `resources/js/api/client.js`
- ถ้าเพิ่ม route ใหม่ ต้องเพิ่มใน `resources/js/router/index.js` และ sidebar ถ้าต้องการเมนู

---

## 21. Git Workflow ที่แนะนำ

ก่อนเริ่ม:

```bash
git pull
git status
```

ทำงาน:

```bash
git checkout -b feature/my-change
```

ก่อน commit:

```bash
php artisan test
npm run build
git status
git diff
```

commit:

```bash
git add .
git commit -m "Short clear message"
```

push:

```bash
git push -u origin feature/my-change
```

ถ้าทำงานบน `main` โดยตรง ต้องระวังเป็นพิเศษและ sync remote ก่อนเสมอ

---

## 22. Production Checklist

ก่อน deploy production:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` เป็น HTTPS domain จริง
- [ ] `APP_KEY` สร้างแล้วและ backup อย่างปลอดภัย
- [ ] Database credentials เป็นของ production
- [ ] เปิด queue worker ถาวร
- [ ] เปิด scheduler/cron
- [ ] ตั้ง `QUEUE_CONNECTION=redis` ถ้ามี workload สูง
- [ ] ตั้ง `CACHE_STORE=redis` ถ้าใช้ Redis
- [ ] ตั้ง `SESSION_DRIVER=redis` หรือ database ตาม infra
- [ ] ตั้ง AI API keys ผ่าน secrets manager หรือ env จริง
- [ ] จำกัด `SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS`
- [ ] ตั้ง channel webhook URLs เป็น public HTTPS
- [ ] ตั้ง Sentry DSN ถ้าต้องการ monitoring
- [ ] ตรวจ `GET /up`
- [ ] รัน migrations
- [ ] รัน `php artisan config:cache`, `route:cache`, `view:cache` ตามเหมาะสม
- [ ] รัน `npm run build`
- [ ] ห้ามเปิด Telescope ใน public production โดยไม่ป้องกัน

ตัวอย่าง optimize:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

ถ้าแก้ `.env` หรือ config:

```bash
php artisan config:clear
php artisan config:cache
```

---

## 23. Troubleshooting

### `Composer detected issues ... PHP version`

ตรวจว่า CLI ใช้ PHP 8.2+:

```bash
php -v
where php
```

บน Windows อาจมี XAMPP/Laragon หลายตัว ต้องจัด PATH ให้ PHP 8.2 มาก่อน

### `No application encryption key has been specified`

รัน:

```bash
php artisan key:generate
```

### Login แล้ว API 401

ตรวจ:

- token อยู่ใน `localStorage`
- `api/client.js` แนบ `Authorization: Bearer ...`
- `.env`/CORS/APP_URL ถูกต้อง
- รัน migration ตาราง Sanctum แล้ว

### Chat ไม่ตอบหรือ error provider

ตรวจ:

- `ANTHROPIC_API_KEY` หรือ `OPENAI_API_KEY`
- `AI_DEFAULT_MODEL`
- ถ้าใช้ Ollama ตรวจ `OLLAMA_HOST` และ `ollama list`
- ดู log ด้วย `php artisan pail`

### Memory ไม่ถูกสร้าง

ตรวจ:

- เปิด `memory_enabled`, `memory_auto_extract`
- มี queue worker รันอยู่
- embedding backend ตั้งถูกต้อง
- API key สำหรับ embedding มีอยู่

### Marketplace webhook skill เรียกไม่ได้

ตรวจ:

- `SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS`
- endpoint เป็น public URL ที่ server เรียกได้
- method/headers ถูกต้อง
- rate limit ไม่เต็ม
- response ไม่ใหญ่/ช้าเกิน timeout

### Channel webhook ไม่เข้า

ตรวจ:

- `APP_URL` เป็น public HTTPS
- provider ตั้ง webhook URL ตรงกับที่ UI แสดง
- secret/signature ถูกต้อง
- connection เปิด `is_enabled`
- ดู logs และ provider webhook delivery logs

### Frontend ไม่ update หลังแก้ `.env`

ถ้าเป็น `VITE_*`:

```bash
npm run dev
```

restart Vite หรือ build ใหม่:

```bash
npm run build
```

---

## 24. ไฟล์ที่ควรรู้จัก

| ไฟล์/โฟลเดอร์ | ใช้ทำอะไร |
|---|---|
| `routes/api.php` | API routes ทั้งหมด |
| `app/Http/Controllers/Api` | API controllers |
| `app/Services/AI` | AI provider และ orchestration |
| `app/Services/Tools` | Tool engine |
| `app/Services/Marketplace` | Skill marketplace |
| `app/Services/Memory` | Memory/embedding/recall |
| `app/Services/Channels` | Channel integrations |
| `app/Jobs` | Queue jobs |
| `app/Events` | Broadcast events |
| `app/Models` | Eloquent models |
| `database/migrations` | Database schema |
| `database/seeders` | Seed data |
| `resources/js/views` | Vue pages |
| `resources/js/components` | Vue components |
| `resources/js/stores` | Pinia stores |
| `resources/js/api/client.js` | Axios client |
| `config/services.php` | External services config |
| `config/skills.php` | Skill marketplace/security config |
| `.env.example` | ตัวอย่าง environment variables |

---

## 25. ขั้นตอนแนะนำหลัง clone ครั้งแรก (สรุปเร็ว)

```bash
composer install
cp .env.example .env
php artisan key:generate
# แก้ .env: DB, APP_URL, AI keys
php artisan migrate
php artisan db:seed
php artisan skills:sync
npm install
```

รันใช้งาน:

```bash
php artisan serve
npm run dev
php artisan queue:work
```

ทดสอบ:

```bash
php artisan test
npm run build
```

