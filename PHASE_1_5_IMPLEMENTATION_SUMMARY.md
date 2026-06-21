# สรุปงานที่ทำไป Phase 1–5

เอกสารนี้สรุปจาก `implementation_plan.md` และโค้ดจริงในโปรเจกต์ `Laravel-AI-Agent-Platform` เพื่ออธิบายว่าแต่ละ Phase ทำอะไรไปแล้ว เกี่ยวข้องกับไฟล์ไหน และฟังก์ชัน/เมธอดสำคัญทำหน้าที่อะไร

สถานะโดยรวม:

- Phase 1: ทำครบแกนหลักของ Foundation, Auth, Chat, AI provider และ SSE streaming
- Phase 2: ทำครบระบบ Tool Engine ระดับแอป แต่ยังไม่ใช่ Docker isolation เต็มรูปแบบ
- Phase 3: ทำครบ channel webhook หลัก LINE, Telegram, Slack, Discord แบบ PHP webhook flow
- Phase 4: ทำครบ memory, personalization, heartbeat/reminder แต่ยังใช้ JSON embedding + cosine search แทน pgvector/Qdrant
- Phase 5: ทำครบ Skill Marketplace, custom webhook skills, built-in integrations และ premium hook เบื้องต้น

---

## Phase 1 — Foundation & Auth

เป้าหมายของ Phase นี้คือสร้างโครง Laravel + Vue, ระบบล็อกอิน, chat พื้นฐาน, AI provider และ conversation history

### สิ่งที่ทำไปแล้ว

- ตั้งค่า Laravel 12 API + Sanctum token auth
- ทำ Vue 3 SPA พร้อม router guard, Pinia store, Tailwind/Vite
- ทำระบบ register/login/logout/user profile
- ทำ chat endpoint ทั้งแบบ streaming และ non-streaming
- บันทึก conversations/messages ลงฐานข้อมูล
- รองรับ multi-model ผ่าน Anthropic, OpenAI และ Ollama
- มี API key CRUD สำหรับผู้ใช้

### Backend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `routes/api.php` | รวม API routes ทั้งหมด เช่น auth, chat, conversations, models |
| `app/Http/Controllers/Api/AuthController.php` | จัดการ register/login/logout/current user |
| `app/Http/Controllers/Api/ChatController.php` | รับข้อความจากผู้ใช้ สร้าง/เลือก conversation เรียก AI และส่งผลกลับ |
| `app/Http/Controllers/Api/ConversationController.php` | CRUD conversations และดึง messages |
| `app/Http/Controllers/Api/ApiKeyController.php` | จัดการ API keys ของผู้ใช้ |
| `app/Services/AI/AIManager.php` | registry/selector ของ AI providers |
| `app/Services/AI/AnthropicProvider.php` | คุยกับ Anthropic Messages API และ stream event |
| `app/Services/AI/OpenAIProvider.php` | คุยกับ OpenAI Chat Completion/Embedding API |
| `app/Services/AI/OllamaProvider.php` | คุยกับ Ollama local model |
| `app/Services/AI/AgentOrchestrator.php` | รวม history, system prompt, memory, tool definitions และเรียก provider |
| `app/Models/User.php` | User model พร้อม Sanctum token และ relationships |
| `app/Models/Conversation.php` | Conversation model, title, model, last activity |
| `app/Models/Message.php` | Message model สำหรับ user/assistant/system messages |
| `app/Models/ApiKey.php` | API key model |
| `database/migrations/2024_01_01_000001_create_conversations_table.php` | ตาราง conversations |
| `database/migrations/2024_01_01_000002_create_messages_table.php` | ตาราง messages |
| `database/migrations/2024_01_01_000003_create_api_keys_table.php` | ตาราง api_keys |

### Frontend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `resources/js/router/index.js` | กำหนด routes และ auth guard |
| `resources/js/stores/auth.js` | เก็บ auth token/user และเรียก login/register/logout |
| `resources/js/stores/chat.js` | state ของ conversations, current conversation, messages, streaming |
| `resources/js/views/Login.vue` | หน้า login |
| `resources/js/views/Register.vue` | หน้า register |
| `resources/js/views/Chat.vue` | หน้า chat หลัก |
| `resources/js/components/ChatInput.vue` | input ส่งข้อความ |
| `resources/js/components/MessageBubble.vue` | แสดง message แต่ละอัน |
| `resources/js/components/StreamingMessage.vue` | แสดงข้อความที่กำลัง stream |
| `resources/js/api/client.js` | Axios client พร้อม Bearer token interceptor |

### ฟังก์ชันสำคัญ

#### `AuthController::register(Request $request)`

รับ `name`, `email`, `password`, validate แล้วสร้าง user ใหม่ จากนั้นสร้าง Sanctum token และคืน `user + token` ให้ frontend

#### `AuthController::login(Request $request)`

ตรวจ email/password ถ้าถูกต้องจะลบ token เดิมชื่อ `auth-token` แล้วสร้าง token ใหม่ เพื่อให้ session ปัจจุบันชัดเจน

#### `AuthController::logout(Request $request)`

ลบ current access token ของผู้ใช้ที่ login อยู่

#### `AuthController::user(Request $request)`

คืนข้อมูล user ปัจจุบันให้ frontend ใช้ restore session

#### `ChatController::send(Request $request)`

เป็น entry point หลักของ chat:

- validate message/model/conversation_id/stream
- ตรวจ prompt injection ผ่าน `PromptInjectionGuard`
- สร้าง conversation ใหม่หรือใช้ conversation เดิม
- บันทึก user message
- สร้าง `ToolContext`
- ถ้า `stream=true` ไปที่ `streamResponse()`
- ถ้า `stream=false` ไปที่ `syncResponse()`

#### `ChatController::stream(Request $request, Conversation $conversation)`

ใช้สำหรับ SSE stream จาก conversation ที่มีอยู่แล้ว พร้อมตรวจว่า conversation เป็นของ user คนนี้จริง

#### `ChatController::syncResponse(...)`

เรียก `AgentOrchestrator::chat()` แบบรอผลทั้งหมด แล้วบันทึก assistant message, dispatch memory extraction job และบันทึก usage

#### `ChatController::streamResponse(...)`

เปิด SSE response แล้วส่ง event เช่น `text`, `tool_start`, `tool_result`, `done`, `error` กลับ frontend ระหว่างที่ AI ตอบ

#### `ConversationController::index()`

ดึงรายการ conversations ของ user เรียงจากล่าสุด

#### `ConversationController::store()`

สร้าง conversation ใหม่ พร้อม title/model เริ่มต้น

#### `ConversationController::show()`

ดึง conversation พร้อม messages โดยตรวจ owner ก่อน

#### `ConversationController::messages()`

ดึง messages ของ conversation แบบ paginate

#### `AIManager::resolveProvider(string $model)`

เลือก provider ตาม model alias เช่น `claude-sonnet` ไป Anthropic, `gpt-4o` ไป OpenAI, ไม่รู้จักจะ fallback ไป Ollama

#### `AgentOrchestrator::chat(...)`

ทำ chat แบบ synchronous และรองรับ tool-use loop:

- สร้าง history จาก messages
- แนบ tool definitions
- เรียก provider
- ถ้า AI ขอใช้ tool จะสร้าง `ToolCall`
- เรียก `ToolExecutor`
- ส่ง tool result กลับเข้า history
- วนจน AI ตอบปกติหรือครบ max iterations

#### `AgentOrchestrator::stream(...)`

เหมือน `chat()` แต่ yield event สำหรับ SSE เช่น text chunk, tool start/result และ done

#### `AgentOrchestrator::buildHistory(...)`

ประกอบ system prompt, persona, memory recall, messages ล่าสุด และชื่อ tools ที่เปิดใช้งาน

---

## Phase 2 — Skill / Tool Engine

เป้าหมาย Phase นี้คือให้ AI เรียกเครื่องมือได้ เช่น search, browser, file, shell, calculator และ track tool/task status

### สิ่งที่ทำไปแล้ว

- มี base class สำหรับ tools
- มี registry สำหรับ register/sync tools ลง DB
- มี executor สำหรับรัน native tools และ dynamic webhook skills
- มี tool definitions สำหรับ AI function calling
- มี built-in tools หลายตัว
- มี `tool_calls`, `task_logs` และ jobs/events สำหรับ task status
- shell/file tools ใช้ sandbox path ต่อ user และมี guard กัน path traversal

### Backend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `app/Services/Tools/BaseTool.php` | abstract contract ของ tool ทุกตัว |
| `app/Services/Tools/ToolRegistry.php` | register/get/enabled/sync tool definitions |
| `app/Services/Tools/ToolExecutor.php` | execute tool และบันทึกผลลง `ToolCall` |
| `app/Services/Tools/ToolContext.php` | context ของ tool เช่น user, conversation, sandbox path |
| `app/Services/Tools/BuiltIn/WebSearchTool.php` | web search ผ่าน Brave/Google |
| `app/Services/Tools/BuiltIn/BrowserTool.php` | fetch web page และ optional Playwright screenshot |
| `app/Services/Tools/BuiltIn/FileSystemTool.php` | อ่าน/เขียน/list/delete ภายใน sandbox |
| `app/Services/Tools/BuiltIn/ShellCommandTool.php` | รัน shell command ภายใน sandbox พร้อม security guard |
| `app/Services/Tools/BuiltIn/CalculatorTool.php` | คำนวณ arithmetic expression |
| `app/Services/Tools/BuiltIn/DateTimeTool.php` | ดูวันเวลา/timezone |
| `app/Models/Skill.php` | records ของ tools/skills ใน DB |
| `app/Models/ToolCall.php` | เก็บ tool call status/result/duration |
| `app/Models/TaskLog.php` | เก็บ async task log |
| `app/Jobs/ProcessToolCallJob.php` | job สำหรับ execute tool แบบ queue |
| `app/Jobs/ExecuteAgentTaskJob.php` | job สำหรับ execute agent task |
| `app/Events/ToolCallStatusUpdated.php` | event สำหรับ broadcast status tool call |
| `app/Events/TaskStatusUpdated.php` | event สำหรับ broadcast task status |
| `app/Http/Controllers/Api/TaskController.php` | API ดู/cancel tasks และ tool calls |
| `app/Console/Commands/SyncSkillsCommand.php` | artisan command `skills:sync` |

### ฟังก์ชันสำคัญ

#### `BaseTool::name()`

คืนชื่อ tool ที่ AI จะใช้ เช่น `web_search`, `shell_command`

#### `BaseTool::parametersSchema()`

คืน JSON Schema ของ arguments ที่ AI ต้องส่งเข้ามา

#### `BaseTool::execute(array $arguments, ?ToolContext $context)`

รัน logic จริงของ tool แล้วคืน `success/result/error`

#### `ToolRegistry::register(BaseTool $tool)`

ลงทะเบียน native PHP tool เข้า memory registry

#### `ToolRegistry::enabled()`

คืนเฉพาะ tools ที่ไม่ได้ถูก disable ในตาราง `skills`

#### `ToolRegistry::getToolDefinitions()`

รวม definitions ของ native tools และ dynamic DB skills เพื่อส่งให้ AI ใช้ function calling

#### `ToolRegistry::syncToDatabase()`

sync tools ที่ register ไว้ลงตาราง `skills` เช่น display name, schema, category, timeout

#### `ToolExecutor::execute(...)`

ทำหน้าที่กลางในการ execute:

- mark tool call เป็น running
- ถ้าเป็น native tool ให้เรียก class tool
- ถ้าไม่ใช่ native แต่เป็น DB webhook skill ให้เรียก `HttpWebhookSkillExecutor`
- mark completed/failed พร้อม duration

#### `ToolContext::getSandboxPath()`

คืน path sandbox ของ user เช่น `storage/app/sandbox/user_{id}` และสร้างโฟลเดอร์ถ้ายังไม่มี

#### `ShellCommandTool::validateCommand(string $command)`

ตรวจ command ว่าไม่อยู่ใน dangerous patterns และ base command อยู่ใน allow-list

#### `ShellCommandTool::containsPathTraversal(string $path)`

ตรวจ `working_directory` ว่ามี `..` หรือไม่ เพื่อกันออกนอก sandbox

#### `ShellCommandTool::isPathInside(string $path, string $base)`

ตรวจ resolved path ว่ายังอยู่ภายใน sandbox จริง ทั้ง Windows/Linux path style

#### `BrowserTool::execute(...)`

รับ URL และ action (`fetch` หรือ `screenshot`) ตรวจ URL แล้วเรียก `fetchPage()` หรือ `takeScreenshot()`

#### `BrowserTool::isBlockedUrl(string $url)`

กันการ fetch ไปยัง localhost/private/reserved IP เพื่อลด SSRF risk

---

## Phase 3 — Channel Integrations

เป้าหมาย Phase นี้คือให้ผู้ใช้คุยกับ AI ผ่าน LINE, Telegram, Slack, Discord ได้ และ routing กลับ channel ถูกต้อง

### สิ่งที่ทำไปแล้ว

- เพิ่ม channel connection CRUD UI/API
- รองรับ webhook endpoint สำหรับ LINE, Telegram, Slack, Discord
- มี service กลางสำหรับรับข้อความจาก channel แล้วส่งเข้า AI
- มี outbound router/client สำหรับตอบกลับแต่ละ channel
- มี channel thread mapping เพื่อผูก external thread กับ conversation
- มี rate limit webhook และ inbound message ต่อ source

### Backend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `app/Http/Controllers/Api/ChannelConnectionController.php` | CRUD channel integrations และ register Telegram webhook |
| `app/Http/Controllers/Api/Webhooks/LineWebhookController.php` | รับ LINE webhook, verify signature, ตอบกลับ LINE |
| `app/Http/Controllers/Api/Webhooks/TelegramWebhookController.php` | รับ Telegram webhook และตรวจ secret token |
| `app/Http/Controllers/Api/Webhooks/SlackWebhookController.php` | รับ Slack events/url verification และ verify signature |
| `app/Http/Controllers/Api/Webhooks/DiscordWebhookController.php` | รับ Discord interactions และ verify signature |
| `app/Services/Channels/ChannelChatService.php` | แปลงข้อความ channel เป็น conversation + AI reply |
| `app/Services/Channels/ChannelOutboundRouter.php` | ส่งข้อความออกไปแต่ละ provider |
| `app/Services/Channels/LineMessagingClient.php` | client สำหรับ LINE Messaging API |
| `app/Services/Channels/TelegramBotClient.php` | client สำหรับ Telegram Bot API |
| `app/Services/Channels/SlackWebClient.php` | client สำหรับ Slack Web API |
| `app/Services/Channels/DiscordInteractionClient.php` | client สำหรับ Discord interaction response/follow-up |
| `app/Services/Channels/SlackRequestValidator.php` | ตรวจ Slack signature/timestamp |
| `app/Services/Channels/DiscordInteractionVerifier.php` | ตรวจ Discord request signature |
| `app/Models/ChannelConnection.php` | เก็บ provider, credentials, webhook key |
| `app/Models/ChannelThread.php` | map external thread/source ID กับ conversation |
| `database/migrations/2026_04_04_000001_create_channel_connections_table.php` | ตาราง channel_connections |
| `database/migrations/2026_04_04_000002_create_channel_threads_table.php` | ตาราง channel_threads |

### Frontend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `resources/js/views/Channels.vue` | UI เพิ่ม/แก้/ลบ/เปิดปิด channel connections |
| `resources/js/stores/channels.js` | state และ API calls ของ channel connections |
| `resources/js/components/Sidebar.vue` | เพิ่มเมนู Channels |

### ฟังก์ชันสำคัญ

#### `ChannelConnectionController::index()`

คืนรายการ channel connections ของ user ปัจจุบัน

#### `ChannelConnectionController::store()`

สร้าง connection สำหรับ `line`, `telegram`, `slack`, `discord` พร้อม validate credentials ที่จำเป็นของแต่ละ provider

#### `ChannelConnectionController::update()`

แก้ label, enabled flag, และ credentials เฉพาะ provider นั้น ๆ

#### `ChannelConnectionController::registerTelegramWebhook()`

เรียก Telegram `setWebhook` พร้อม `secret_token` เพื่อผูก bot token กับ webhook URL ของระบบ

#### `LineWebhookController::__invoke(...)`

รับ LINE webhook:

- หา connection จาก `webhook_key`
- verify `X-Line-Signature`
- รับเฉพาะ text message
- rate limit ต่อ source id
- ส่งข้อความเข้า `ChannelChatService::replyToText()`
- ใช้ outbound router ตอบกลับด้วย reply token

#### `ChannelChatService::replyToText(...)`

service กลางของ channel chat:

- ตรวจ prompt injection
- resolve conversation จาก external thread id
- บันทึก user message
- เรียก `AgentOrchestrator::chat()`
- บันทึก assistant message
- dispatch memory extraction
- record usage ตาม provider
- คืนข้อความสำหรับตอบกลับ channel

#### `ChannelChatService::resolveConversation(...)`

หา `ChannelThread` เดิม ถ้าไม่มีจะสร้าง conversation ใหม่และบันทึก mapping external thread id

#### `ChannelOutboundRouter`

เป็นจุดรวมการส่งข้อความกลับไป provider ต่าง ๆ เช่น LINE reply, Telegram send message, Slack response, Discord follow-up

---

## Phase 4 — Memory & Personalization

เป้าหมาย Phase นี้คือให้ AI จำ preference/บริบทระยะยาวของผู้ใช้ มี persona และ proactive reminders

### สิ่งที่ทำไปแล้ว

- มีตาราง user memories พร้อม embedding JSON, importance, dedupe hash
- มี service สร้าง embedding จาก OpenAI หรือ Ollama
- มี auto memory extraction job หลัง assistant ตอบ
- มี memory recall เข้า system prompt
- มี persona/settings UI
- มี heartbeat/reminder jobs และ API สำหรับ acknowledge reminder
- มี context window management ผ่าน `context_max_messages`

### Backend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `app/Models/UserMemory.php` | เก็บ long-term memory ของ user |
| `app/Models/AgentReminder.php` | เก็บ proactive reminders |
| `app/Support/AgentSettings.php` | value object สำหรับ user personalization settings |
| `app/Services/Memory/EmbeddingService.php` | สร้าง embedding จาก backend ที่ตั้งค่าไว้ |
| `app/Services/Memory/MemoryExtractionService.php` | วิเคราะห์ conversation แล้วสร้าง memories |
| `app/Services/Memory/MemoryPromptBuilder.php` | recall memories ที่เกี่ยวข้องแล้วสร้าง prompt block |
| `app/Services/Memory/VectorCosine.php` | คำนวณ cosine similarity |
| `app/Http/Controllers/Api/MemoryController.php` | API ดู/สร้าง/ลบ memories |
| `app/Http/Controllers/Api/AgentSettingsController.php` | API ดู/แก้ personalization settings |
| `app/Http/Controllers/Api/AgentReminderController.php` | API ดู/ack reminders |
| `app/Jobs/ExtractMemoriesFromConversationJob.php` | queue job สำหรับ extract memory |
| `app/Jobs/RunAgentHeartbeatsJob.php` | job รวมสำหรับหา user ที่ต้อง heartbeat |
| `app/Jobs/UserHeartbeatJob.php` | job สร้าง reminder สำหรับ user |
| `database/migrations/2026_04_06_000001_add_agent_settings_to_users_table.php` | เพิ่ม settings ใน users |
| `database/migrations/2026_04_06_000002_create_user_memories_table.php` | ตาราง memories |
| `database/migrations/2026_04_06_000003_create_agent_reminders_table.php` | ตาราง reminders |

### Frontend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `resources/js/views/Personalization.vue` | UI persona/settings/memory/reminders |
| `resources/js/stores/personalization.js` | state และ API calls ของ personalization |
| `resources/js/components/OnboardingModal.vue` | onboarding เบื้องต้นสำหรับตั้งค่า user |

### ฟังก์ชันสำคัญ

#### `AgentSettings::forUser(User $user)`

โหลด settings จาก `user.agent_settings` แล้วเติม default values เช่น memory enabled, top K, min score, context max messages

#### `AgentSettings::merge(User $user, array $input)`

รวม settings เดิมกับค่าที่ user ส่งมา แล้ว sanitize/normalize ก่อนบันทึก

#### `MemoryController::index()`

คืน memories ของ user แบบ paginate

#### `MemoryController::store(Request $request, EmbeddingService $embedding)`

สร้าง manual memory:

- validate content/importance
- dedupe ด้วย hash
- สร้าง embedding
- บันทึก memory พร้อม source = `manual`

#### `MemoryController::destroy(...)`

ลบ memory โดยตรวจ owner ก่อน

#### `MemoryExtractionService::extractAndStore(Conversation $conversation)`

ทำ auto memory extraction:

- โหลด messages ล่าสุด
- ส่ง transcript ให้ AI extraction model
- parse JSON `{ "memories": [...] }`
- dedupe exact hash และ semantic similarity
- สร้าง embedding
- บันทึก source = `auto`

#### `MemoryPromptBuilder::recallBlock(User $user, string $latestUserMessage)`

สร้าง memory block สำหรับ system prompt:

- embed user message ล่าสุด
- ดึง memories ล่าสุด 400 รายการ
- คำนวณ cosine similarity คูณ importance
- filter ด้วย `memory_min_score`
- เอา top K มาเป็น bullet list ใน prompt

#### `VectorCosine::similarity(array $a, array $b)`

คำนวณ similarity ระหว่าง embedding vectors

#### `AgentReminderController::index()`

คืน reminders ล่าสุดของ user โดยเรียง unread ก่อน

#### `AgentReminderController::ack(...)`

mark reminder ว่าอ่านแล้วด้วย `read_at`

---

## Phase 5 — Skill Marketplace

เป้าหมาย Phase นี้คือให้เพิ่มความสามารถ AI ผ่าน marketplace, built-in skills, custom webhook skills และ version rollback

### สิ่งที่ทำไปแล้ว

- มี marketplace catalog ผ่าน `skill_packages`
- มี install/uninstall tracking ผ่าน `user_skill_installs`
- มี manifest v1 สำหรับ skill packages
- custom skill รองรับ JSON manifest และ YAML manifest
- built-in packages/tools: Weather, Stocks, Google Calendar, Gmail, Notion
- webhook skills มี timeout, rate limit, permission scope และ host allow-list
- custom skill update เก็บ revision snapshot และ rollback ได้
- มี premium flag และ config gate สำหรับ subscription ในอนาคต

### Backend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `app/Models/SkillPackage.php` | catalog package ใน marketplace |
| `app/Models/UserSkillInstall.php` | บันทึกว่า user ติดตั้ง package ไหน |
| `app/Models/SkillRevision.php` | เก็บ snapshot สำหรับ rollback |
| `app/Services/Marketplace/SkillManifestValidator.php` | validate manifest v1 |
| `app/Services/Marketplace/SkillManifestSchema.php` | นิยาม shape ของ manifest สำหรับ API/docs |
| `app/Services/Marketplace/YamlManifestParser.php` | parse YAML manifest |
| `app/Services/Marketplace/MarketplaceService.php` | install/uninstall package และ materialize skill |
| `app/Services/Marketplace/HttpWebhookSkillExecutor.php` | execute custom/marketplace HTTP webhook skill |
| `app/Http/Controllers/Api/MarketplaceController.php` | API browse/show/install/uninstall/my-installs/schema |
| `app/Http/Controllers/Api/CustomSkillController.php` | API create/update/delete custom webhook skills |
| `app/Http/Controllers/Api/SkillController.php` | API list/toggle/rollback skills |
| `app/Services/Tools/BuiltIn/WeatherTool.php` | weather skill ผ่าน Open-Meteo |
| `app/Services/Tools/BuiltIn/StockQuoteTool.php` | stock quote skill ผ่าน Yahoo chart API |
| `app/Services/Tools/BuiltIn/GoogleCalendarTool.php` | Google Calendar integration |
| `app/Services/Tools/BuiltIn/GmailQueryTool.php` | Gmail search integration |
| `app/Services/Tools/BuiltIn/NotionTool.php` | Notion search integration |
| `database/migrations/2026_04_09_000001_skill_marketplace_tables.php` | marketplace tables + skills columns |
| `database/seeders/SkillPackageSeeder.php` | seed marketplace packages |
| `config/skills.php` | webhook allow-list, timeout, premium install gate |

### Frontend Files

| ไฟล์ | หน้าที่ |
|---|---|
| `resources/js/views/Marketplace.vue` | หน้า marketplace browse/search/install + no-code webhook form |
| `resources/js/stores/marketplace.js` | state/API calls ของ marketplace |
| `resources/js/views/Skills.vue` | จัดการ skills เปิด/ปิด ดู parameters และ rollback custom webhook |
| `resources/js/stores/skills.js` | state/API calls ของ skills |
| `resources/js/components/Sidebar.vue` | เมนู Marketplace และ Skills |

### ฟังก์ชันสำคัญ

#### `MarketplaceController::manifestSchema()`

คืน manifest schema description ให้ frontend หรือ developer ใช้ดูรูปแบบ package manifest

#### `MarketplaceController::packages(Request $request)`

ค้นหา/list packages โดยรองรับ query `q` และ `category` เรียง featured ก่อน

#### `MarketplaceController::show(SkillPackage $package)`

คืนรายละเอียด package รวมถึง manifest

#### `MarketplaceController::install(...)`

เรียก `MarketplaceService::install()` เพื่อติดตั้ง package และ enable skill

#### `MarketplaceController::uninstall(...)`

เรียก `MarketplaceService::uninstall()` เพื่อลบ install record และ disable/delete skill ตามชนิด package

#### `MarketplaceController::myInstalls(Request $request)`

คืนรายการ packages ที่ user ติดตั้งไว้

#### `MarketplaceService::install(User $user, SkillPackage $package)`

ติดตั้ง package:

- ตรวจ premium gate
- validate manifest
- materialize skill เป็น native skill หรือ HTTP webhook skill
- enable skill
- บันทึก `user_skill_installs`

#### `MarketplaceService::uninstall(User $user, SkillPackage $package)`

ถอน package:

- ลบ install record ของ user
- ถ้าไม่มี user อื่นใช้ package นี้แล้ว
  - native skill จะถูก disable
  - webhook marketplace skill จะถูก delete

#### `MarketplaceService::materializeSkill(...)`

แปลง manifest เป็น `Skill`:

- ถ้า `execution.type = native` จะหา skill ที่มี tool class อยู่แล้ว
- ถ้า `execution.type = http_webhook` จะสร้าง/อัปเดต skill ที่ config handler เป็น `http_webhook`

#### `SkillManifestValidator::validate(array $manifest)`

ตรวจ manifest:

- `schema_version` ต้องเป็น 1
- ต้องมี name/display_name/description/category/parameters_schema/execution
- name ต้องเป็น snake_case
- parameters_schema ต้องเป็น JSON schema object
- execution ต้องเป็น `native` หรือ `http_webhook`
- webhook host ต้องอยู่ใน allow-list ถ้าตั้งค่าไว้

#### `YamlManifestParser::parse(string $yaml)`

parse YAML string เป็น array และ throw validation error ถ้า YAML ไม่ถูกต้อง

#### `HttpWebhookSkillExecutor::execute(Skill $skill, array $arguments, ?ToolContext $context)`

รัน webhook skill:

- อ่าน URL/method/header/timeout จาก skill config
- rate limit ต่อ user+skill
- ตรวจ permission `network`
- ส่ง HTTP request พร้อม arguments
- truncate response ถ้ายาวเกิน
- คืน success/error ให้ tool pipeline

#### `CustomSkillController::store(...)`

สร้าง custom webhook skill จาก `manifest` หรือ `manifest_yaml`:

- validate manifest
- บังคับให้เป็น `http_webhook`
- กันชื่อ skill ซ้ำ
- สร้าง skill source = `custom`

#### `CustomSkillController::update(...)`

แก้ custom webhook skill:

- อนุญาตเฉพาะ `source = custom`
- validate manifest
- ห้าม rename `manifest.name`
- สร้าง `SkillRevision` snapshot ก่อนแก้
- update config/schema/version

#### `CustomSkillController::destroy(Skill $skill)`

ลบเฉพาะ custom skills

#### `CustomSkillController::resolveManifestInput(Request $request)`

รับ input ได้สองแบบ:

- `manifest` เป็น JSON object
- `manifest_yaml` เป็น YAML string

ถ้าส่งมาทั้งสองแบบจะคืน error

#### `SkillController::index()`

คืนรายการ skills ทั้งหมดพร้อม source, package, schema, rate limit, webhook flag

#### `SkillController::toggle(Request $request, Skill $skill)`

เปิด/ปิด skill ด้วย `is_enabled`

#### `SkillController::rollback(Skill $skill)`

rollback custom/marketplace webhook skill จาก revision ล่าสุด แล้วลบ revision นั้น

#### `WeatherTool::execute(...)`

รับ location หรือ lat/lon, geocode ด้วย Open-Meteo แล้วดึง current weather/forecast

#### `StockQuoteTool::execute(...)`

รับ ticker symbol แล้วเรียก Yahoo chart endpoint เพื่อคืนราคาล่าสุด

#### `GoogleCalendarTool::execute(...)`

ใช้ `GOOGLE_CALENDAR_ACCESS_TOKEN` เพื่อดึง upcoming events จาก Google Calendar ถ้า token ยังไม่ตั้ง จะตอบเป็น setup instruction

#### `GmailQueryTool::execute(...)`

ใช้ `GMAIL_ACCESS_TOKEN` เพื่อ search Gmail และคืน subject/from/snippet ถ้า token ยังไม่ตั้ง จะตอบเป็น setup instruction

#### `NotionTool::execute(...)`

ใช้ `NOTION_INTEGRATION_TOKEN` เพื่อ search Notion workspace ถ้า token ยังไม่ตั้ง จะตอบเป็น setup instruction

---

## ภาพรวมความสัมพันธ์ของระบบ

Flow หลักของ web chat:

1. Frontend `Chat.vue` ส่งข้อความผ่าน `resources/js/stores/chat.js`
2. `POST /api/chat` เข้า `ChatController::send()`
3. `ChatController` บันทึก user message
4. `AgentOrchestrator` สร้าง prompt/history/tools/memory
5. `AIManager` เลือก provider เช่น Anthropic/OpenAI/Ollama
6. ถ้า AI ขอใช้ tool จะสร้าง `ToolCall` และส่งให้ `ToolExecutor`
7. `ToolExecutor` รัน native tool หรือ webhook skill
8. Assistant response ถูกบันทึกใน `messages`
9. Dispatch memory extraction และ record usage

Flow หลักของ channel chat:

1. Provider เช่น LINE/Telegram/Slack/Discord ยิง webhook เข้า `/api/webhooks/...`
2. Webhook controller verify signature/secret
3. ส่ง text เข้า `ChannelChatService::replyToText()`
4. `ChannelChatService` map external thread กับ conversation
5. เรียก `AgentOrchestrator::chat()`
6. ส่งข้อความกลับ provider ผ่าน outbound router/client

Flow หลักของ marketplace:

1. Frontend `/marketplace` โหลด package catalog จาก `MarketplaceController::packages()`
2. User กด install package
3. `MarketplaceService::install()` validate manifest และ materialize skill
4. Skill ถูกเปิดใช้งานใน `skills`
5. `ToolRegistry::getToolDefinitions()` expose skill ให้ AI ใช้งาน
6. ถ้า skill เป็น webhook จะถูกรันผ่าน `HttpWebhookSkillExecutor`

---

## สิ่งที่ยังควรทำต่อ

- เพิ่ม Docker/container isolation สำหรับ shell/file/browser tools ก่อน production
- เพิ่ม pgvector หรือ Qdrant แทน JSON embedding search เมื่อข้อมูล memory เยอะขึ้น
- เพิ่ม end-to-end tests สำหรับ Telegram/Discord outbound replies
- เพิ่ม per-user skill enablement เพราะตอนนี้ `skills.is_enabled` ยังเป็น global-level
- เพิ่ม subscription/billing provider ถ้าจะเปิด premium skills จริง
- เพิ่ม tests สำหรับ custom skill lifecycle: create, update, rollback, delete, webhook execution
- เพิ่ม tests สำหรับ SSE streaming edge cases

---

## Verification ล่าสุด

รันแล้วผ่าน:

```text
php artisan test
Tests: 10 passed (14 assertions)
```

Frontend build ก่อนหน้านี้ผ่าน:

```text
npm run build
```

