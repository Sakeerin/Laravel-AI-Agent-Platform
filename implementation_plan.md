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
