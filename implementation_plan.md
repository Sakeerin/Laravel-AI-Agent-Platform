# AI Agent Platform — Development Plan
**Laravel + Vue.js · คล้าย OpenClaw · ระยะเวลา ~18 สัปดาห์ · 6 Phases**

---

## Phase 1 — Foundation & Auth `2–3 weeks`
> สร้างโครงสร้างหลัก · ระบบล็อกอิน · Database

### Tasks
- Laravel 12 project setup + MySQL schema
- Auth ด้วย Laravel Sanctum (API token)
- Vue 3 + Vite + Tailwind CSS frontend
- Chat UI พื้นฐาน (ส่ง-รับข้อความ)
- Anthropic API integration (claude-sonnet)
- Streaming response ด้วย SSE
- Conversation history & memory storage
- Multi-model support (OpenAI, local Ollama)

### Deliverables
| | |
|---|---|
| **Deliverable** | Web chat app พื้นฐาน ล็อกอินได้ คุยกับ AI ได้ |
| **Database** | users, conversations, messages, api_keys |
| **APIs** | POST /chat, GET /conversations, GET /messages |

### Implementation Status — Updated 2026-06-21
**Status: ✅ Implemented**

- ✅ Laravel 12 + Sanctum auth, register/login/logout/user API
- ✅ Vue 3 SPA with Vite, Tailwind, Pinia, router guards
- ✅ Web chat UI, conversation/message persistence, title generation
- ✅ Anthropic/OpenAI/Ollama provider layer and model selection
- ✅ SSE streaming chat endpoint and non-streaming fallback
- ✅ API key CRUD endpoint
- 🔧 Follow-up: add broader feature tests for auth + streaming edge cases

---

## Phase 2 — Skill / Tool Engine `3–4 weeks`
> ระบบ Tools · Function Calling · Task Execution

### Tasks
- Tool/Skill registry system (JSON schema-based)
- Anthropic Tool Use (function calling) integration
- Web Search tool (Brave/Google API)
- Browser automation (Playwright headless)
- File read/write tool (sandboxed directory)
- Shell command execution (Docker sandbox)
- Laravel Queue สำหรับ async task execution
- Task status tracking & real-time updates

### Deliverables
| | |
|---|---|
| **Deliverable** | AI สั่ง search, เปิดเว็บ, จัดการไฟล์ได้ |
| **Security** | Docker isolation, path traversal protection |
| **Database** | skills, tool_calls, task_logs |

### Implementation Status — Updated 2026-06-21
**Status: 🟡 Implemented with sandbox follow-ups**

- ✅ Tool registry, database sync, JSON-schema tool definitions
- ✅ Anthropic tool-use loop in sync and streaming modes
- ✅ Web search tool with Brave/Google fallback
- ✅ Browser fetch + optional Playwright screenshot support
- ✅ File system tool uses per-user sandbox path
- ✅ Shell command tool uses per-user sandbox, allow-list, dangerous-pattern blocks, and path traversal guard
- ✅ Queue/task tracking models and task status events exist
- ⚠️ Gap: full Docker isolation is not wired yet; current sandbox is application-level filesystem/process policy
- 🔧 Follow-up: run shell/file/browser tools in a container or job worker sandbox before production

---

## Phase 3 — Channel Integrations `3–4 weeks`
> เชื่อมต่อ LINE · Telegram · Slack · Discord

### Tasks
- LINE Messaging API webhook handler
- Telegram Bot API integration
- Slack Bolt / Events API
- Discord.js bot integration
- WebSocket Gateway (Laravel Echo + Soketi)
- Channel router (ส่งข้อความกลับถูก channel)
- Rate limiting ต่อ channel/user
- Channel config UI ใน dashboard

### Deliverables
| | |
|---|---|
| **Deliverable** | สั่ง AI ผ่าน LINE/Telegram ได้เลย |
| **Priority** | LINE + Telegram ก่อน (ตลาดไทย) |

### Implementation Status — Updated 2026-06-21
**Status: 🟡 Mostly implemented**

- ✅ LINE, Telegram, Slack, Discord webhook controllers
- ✅ Channel connection model, API, UI, webhook keys, encrypted channel config helpers
- ✅ Channel router and outbound clients for supported providers
- ✅ Per-channel webhook throttle and per-thread inbound rate limiting
- ✅ Channel thread mapping for routing messages back to the right source
- 🟡 WebSocket/Echo/Soketi support exists for app events, but channel message UX should be tested end-to-end with a running broadcaster
- ⚠️ Gap: Discord is implemented as PHP webhook/interaction flow, not a separate Discord.js bot process
- 🔧 Follow-up: add end-to-end webhook tests for Telegram/Discord outbound replies

---

## Phase 4 — Memory & Personalization `2–3 weeks`
> Long-term memory · User context · Persona

### Tasks
- Vector DB สำหรับ semantic memory (pgvector)
- Auto memory extraction จาก conversation
- Memory recall ใน system prompt
- User preference & persona settings
- Agent "heartbeat" — proactive reminders
- Scheduled task automation (cron-based)
- Memory management UI (view/delete memories)
- Context window management

### Deliverables
| | |
|---|---|
| **Deliverable** | AI จำได้ว่าเราชอบอะไร ทำอะไรค้างอยู่ |
| **Storage** | pgvector หรือ Qdrant สำหรับ embeddings |

### Implementation Status — Updated 2026-06-21
**Status: 🟡 Implemented with vector-storage limitation**

- ✅ User memory table, embedding storage, dedupe hash, cosine similarity helper
- ✅ Auto memory extraction job from conversation history
- ✅ Memory recall block injected into the system prompt
- ✅ Persona/context settings API and Personalization UI
- ✅ Agent reminders / heartbeat jobs and acknowledgment API
- ✅ Memory management UI supports viewing, creating, and deleting memories
- ✅ Context window max-message setting is enforced in prompt history
- ⚠️ Gap: storage is JSON embeddings + in-app cosine search, not pgvector/Qdrant yet
- 🔧 Follow-up: migrate semantic recall to pgvector/Qdrant when memory volume grows

---

## Phase 5 — Skill Marketplace `2–3 weeks`
> Plugin system · Community skills · Skill builder
> ⚠️ เริ่ม parallel ตั้งแต่ Phase 3 ได้เลย

### Tasks
- Skill manifest format (JSON/YAML schema)
- Skill install/uninstall/enable system
- Marketplace UI (browse/search/install)
- Built-in skills: Calendar, Email, Weather, Stocks
- Skill sandbox (rate limit, permission scope)
- Custom skill builder UI (no-code)
- Skill version management & rollback
- Google Calendar, Gmail, Notion integrations

### Deliverables
| | |
|---|---|
| **Deliverable** | ติด skill เพิ่ม AI ทำอะไรได้เยอะขึ้น |
| **Revenue** | Premium skills model ได้เลย |

### Implementation Status — Updated 2026-06-21
**Status: 🟢 Implemented (premium billing hook only)**

- ✅ Skill manifest v1 supports JSON and custom-skill YAML input
- ✅ Marketplace package model, install/uninstall tracking, manifest schema endpoint
- ✅ Marketplace UI for browse/search/install and a no-code webhook skill builder
- ✅ Built-in packages/tools: Weather, Stocks, Google Calendar, Gmail, Notion
- ✅ HTTP webhook skills run through rate limits, timeout, host allow-list, and permission scope
- ✅ Custom webhook skills support revision snapshots and rollback
- ✅ Dynamic DB skills are exposed to the agent alongside native PHP tools
- ✅ Premium package flag and configurable install gate are in place
- ⚠️ Gap: premium billing/subscription entitlement is not implemented yet
- 🔧 Follow-up: add per-user skill enablement and subscription provider integration before paid marketplace launch

---

## Phase 6 — Polish, Security & Deploy `2–3 weeks`
> Security hardening · CI/CD · Monitoring

### Tasks
- Prompt injection protection
- API key encryption (Laravel encryption)
- Docker Compose production setup
- Azure DevOps CI/CD pipeline
- Usage analytics dashboard
- Cost tracking per user (token usage)
- Load testing & performance optimization
- Onboarding wizard สำหรับ user ใหม่

### Deliverables
| | |
|---|---|
| **Deliverable** | Production-ready app พร้อม deploy |
| **Monitoring** | Sentry + Laravel Telescope + Uptime Robot |

---

## Tech Stack

### Backend
| เทคโนโลยี | บทบาท |
|---|---|
| Laravel 12 | API, Queue, Auth, WebSocket |
| MySQL 8 | Main DB + pgvector alt |
| Redis | Queue, Cache, Sessions |
| Laravel Horizon | Queue monitoring dashboard |
| Soketi | Self-hosted Pusher (WebSocket) |
| Docker | Sandbox + Production deploy |

### Frontend
| เทคโนโลยี | บทบาท |
|---|---|
| Vue 3 | Composition API + Pinia |
| Vite | Build tool, HMR |
| Tailwind CSS | Utility-first styling |
| Inertia.js | Laravel-Vue bridge (หรือ SPA) |
| Tiptap | Rich text / Markdown editor |
| ApexCharts | Analytics dashboard |

### AI & Integrations
| เทคโนโลยี | บทบาท |
|---|---|
| Anthropic API | claude-sonnet-4 primary |
| OpenAI API | GPT-4o fallback option |
| Ollama | Local model (privacy mode) |
| Playwright | Browser automation tool |
| LINE / Telegram | Messaging channels |
| Google APIs | Calendar, Gmail, Drive |

---

## Architecture

```
┌─────────────────────────────────────────┐
│               Channels                  │
│  Web Chat · LINE · Telegram · Slack · Discord │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│               Gateway                   │
│  WebSocket (Soketi + Echo) · REST API  │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│               AI Core                   │
│  Agent Orchestrator · Memory Manager   │
│  Tool Router · Model Selector           │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│               Skills                    │
│  Web Search · Browser · File System    │
│  Shell Exec · Custom Skills             │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│               Storage                   │
│  MySQL · Redis · pgvector · Files       │
└─────────────────────────────────────────┘
```

---

## Timeline (~18 สัปดาห์)

```
          ม.ค.      ก.พ.      มี.ค.     เม.ย.     พ.ค.
          |---------|---------|---------|---------|
Phase 1   [███░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]  2–3 wk
Phase 2       [█████████░░░░░░░░░░░░░░░░░░░░░░░░]  3–4 wk
Phase 3                [█████████░░░░░░░░░░░░░░░]  3–4 wk
Phase 4                          [██████░░░░░░░░]  2–3 wk
Phase 5 (parallel)         [████████████████░░░░]  Parallel
Phase 6                                  [██████]  2–3 wk
```

> **หมายเหตุ:** Phase 5 (Marketplace) เริ่มพัฒนา parallel ตั้งแต่ Phase 3 ได้เลย เพราะไม่ depend กัน ช่วยให้ ship เร็วขึ้น
