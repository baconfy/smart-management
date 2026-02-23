# Smart Management

### Talk to AI. Project management happens.

An open-source, AI-powered project manager where conversations replace forms, kanbans, and dashboards.
You describe your project. AI agents organize everything behind the scenes.

[Quick Start](#-quick-start) • [Features](#-features) • [How It Works](#%EF%B8%8F-how-it-works) • [Tech Stack](#-tech-stack) • [Contributing](#-contributing)

![License](https://img.shields.io/badge/license-Non--Commercial-red)
![Laravel 12](https://img.shields.io/badge/laravel-12-FF2D20?logo=laravel)
![React 19](https://img.shields.io/badge/react-19-61DAFB?logo=react)
![Tests](https://img.shields.io/badge/tests-427-brightgreen)
![Multi-provider AI](https://img.shields.io/badge/AI-multi--provider-blueviolet)

---

## The Problem

Project management tools treat management as **bureaucracy** — you feed the system so it can work. It's work to generate work.

Using an AI chat as a project manager works surprisingly well — it's conversational, natural, zero friction. Until the chat hits its limits and dies. The conversation is lost, and with it, all the context, decisions, and structure that had been built.

**Smart Management fixes this.** The conversational approach works. What was missing is **persistence**, **structure**, and **scoped context**.

---

## ✨ Features

### 💬 Chat-First Interface
The primary interface is a **conversation**, not a dashboard. Describe your project, discuss decisions, define rules — AI agents organize everything behind the scenes. Traditional views (kanban, decisions, business rules) exist as **read-only visualizations** of what the AI has already organized.

### 🤖 Multi-Agent System
A team of specialized AI agents, each with a distinct role:

| Agent | Purpose | Creates |
|-------|---------|---------|
| **Architect** | Stack choices, patterns, tradeoffs | Decisions (ADRs) |
| **Project Manager** | Tasks, roadmap, estimates, progress | Tasks & Roadmap |
| **Business Analyst** | Domain rules, requirements, acceptance criteria | Business Rules |
| **Technical** | Implementation help, code review, debugging | Implementation Notes |

You never pick which agent responds — an **invisible Moderator** (running on a cheap model) classifies your message and routes it to the right specialist. When it's unsure, it asks you.

### 📚 Knowledge That Outlives Conversations
Agents don't share chat history. They read and write **structured artifacts**: Decisions, Business Rules, Tasks, and Implementation Notes. Conversations can be deleted without losing project knowledge. Your artifacts _are_ your project's brain.

### 📋 Kanban Board
Drag-and-drop task board with **customizable statuses per project**. Tasks are created by the PM agent during conversations, but you can also view and reorganize them visually.

### ⚙️ Per-Project Agent Customization
Each project gets its own set of agents. Edit instructions to fine-tune behavior:
- Make the Architect emphasize security for a crypto project
- Make the PM more aggressive on deadlines for a tight sprint
- **Reset to default** anytime — you can't permanently break an agent

### 🔑 Bring Your Own API Keys
Multi-provider support via [Laravel AI SDK](https://github.com/laravel/ai). Use **Anthropic**, **OpenAI**, **Gemini**, **Groq**, **DeepSeek**, **Mistral**, **xAI**, **Ollama** (local), and more. You own your keys, your data, your infrastructure.

### 🧪 427+ Tests
Comprehensive test suite covering the entire backend: models, actions, services, controllers, AI tools, and real-time events.

---

## ⚙️ How It Works

```
📁 Project
├── 💬 Conversations (the meeting room)
│   ├── "Defining stack and approach"
│   ├── "Payment split rules"
│   └── "Replanning phase 2"
├── 📚 Artifacts (generated from conversations)
│   ├── Decisions: "Use AdonisJS", "Non-custodial"
│   ├── Business Rules: "Immediate split", "0.5% fee"
│   └── Tasks & Roadmap
└── 📋 Kanban Board
    └── Drag-and-drop task management
```

**The Meeting Room model:** You open a conversation and start talking. The Moderator classifies your message and routes it to the right agent (or multiple agents). Each agent reads relevant artifacts for context and writes new ones as output.

```
You:        "Should the payment split happen on-chain or off-chain?"

Moderator:  → Business + Architecture question → routes to Analyst + Architect

You see:    A tabbed response from both agents,
            each covering their perspective.
```

**Task Technical Chat:** Click a task, start a conversation, and a Technical agent joins with full context about the task, related decisions, and business rules — ready to help you implement.

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.5 |
| Frontend | React 19, Inertia.js v2, Tailwind CSS v4 |
| AI | Laravel AI SDK (multi-provider) |
| Real-time | Laravel Reverb (WebSockets) |
| Queue | Laravel Horizon + Redis |
| Database | PostgreSQL 18 |
| Storage | MinIO (S3-compatible) |
| Auth | Laravel Fortify (with 2FA) |
| Testing | Pest 4 |
| Infrastructure | Docker (serversideup/php) |

---

## 🚀 Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)
- At least one AI API key (Anthropic, OpenAI, Gemini, etc.)

### 1. Clone the repository

```bash
git clone https://github.com/your-username/smart-management.git
cd smart-management
```

### 2. Copy the environment file

```bash
cp .env.example .env
```

### 3. Configure your AI provider(s)

Open `.env` and add at least one API key:

```env
# Add one or more AI provider keys
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
GEMINI_API_KEY=...

# All supported providers:
# ANTHROPIC_API_KEY, OPENAI_API_KEY, GEMINI_API_KEY,
# GROQ_API_KEY, DEEPSEEK_API_KEY, MISTRAL_API_KEY,
# XAI_API_KEY, OLLAMA_API_KEY, OPENROUTER_API_KEY,
# COHERE_API_KEY, VOYAGEAI_API_KEY, JINA_API_KEY
```

### 4. Start the development environment

```bash
composer dev
```

This single command boots **everything**: PHP, PostgreSQL, Redis, Horizon, Reverb, MinIO, Mailpit, and Vite with hot-reload.

### 5. Install dependencies and setup

In another terminal:

```bash
# Install PHP dependencies
docker compose exec app composer install

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate

# Install frontend dependencies and build
docker compose exec app npm install
docker compose exec app npm run build
```

### 6. Open the app

Visit **http://localhost** (or the port configured in `FORWARD_APP_PORT`).

Register an account, create your first project, and start chatting!

---

## 📁 Project Structure

```
app/
├── Actions/            # Single-responsibility invokable classes
├── Ai/
│   ├── Agents/         # GenericAgent (unified for all types)
│   ├── Stores/         # ProjectConversationStore
│   └── Tools/          # 16 AI tools (CRUD for each artifact type)
├── Enums/              # AgentType, DecisionStatus, TaskPriority, etc.
├── Events/             # AgentMessageReceived, AgentsProcessing, etc.
├── Models/             # Eloquent models
└── Services/           # Orchestrators (CreateProjectService, etc.)

resources/
├── instructions/       # Default agent instructions (.md files)
└── js/
    └── pages/
        └── projects/
            ├── conversations/   # Chat interface
            ├── tasks/           # Kanban board + task detail
            ├── decisions/       # Post-it grid view
            ├── business-rules/  # Accordion view
            └── settings/        # Agent management
```

---

## 🐳 Docker Services

| Service | Description | Host Port |
|---------|-------------|-----------|
| **app** | Laravel + Nginx | `80` |
| **horizon** | Queue worker | — |
| **reverb** | WebSocket server | `9012` |
| **postgres** | PostgreSQL 18 | `5432` |
| **redis** | Cache + Queue + Sessions | `6379` |
| **minio** | S3-compatible storage | `9000` (API) / `8900` (Console) |
| **mailpit** | Email testing | `8025` |

---

## 🔧 Configuration

### AI Provider

The default AI provider and model are configured in `config/ai.php`. You can override the model **per agent** in the project settings UI.

**Cost tip:** The invisible Moderator uses the cheapest available model automatically (e.g., Claude Haiku, GPT-4o-mini) since it only classifies messages. Your budget goes to the agents that matter.

### WebSockets (Reverb)

The default configuration works out of the box with Docker. If deploying to production, update the Reverb variables in `.env`:

```env
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Running Tests

```bash
docker compose exec app php artisan test --compact
```

---

## 🗺 Roadmap

See [ROADMAP.md](./ROADMAP.md) for the full phased execution plan.

**Completed:**
- ✅ Phase 1 — Foundation (projects, agents, chat, decisions)
- ✅ Phase 1.5 — Real-time chat (WebSockets, parallel agents)
- ✅ Phase 2 — Full agent system (all 4 agents + all artifact types)
- ✅ Phase 3 — Task system (kanban, technical chat, unified chat)
- ✅ Phase 4.1 — Settings & agent management

**Coming next:**
- 🔄 Token streaming (SSE)
- 🔄 Custom agent creation with AI-assisted instructions
- 🔄 Project dashboard & overview
- 🔄 Context window management
- 🔄 Integrations (GitHub, Slack)

---

## 🤝 Contributing

Contributions are welcome! This is a community-driven project.

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes (we maintain 427+ tests — keep it that way)
4. Run the test suite (`php artisan test --compact`)
5. Run the code formatter (`vendor/bin/pint --dirty`)
6. Commit and push
7. Open a Pull Request

Please read [VISION.md](./VISION.md) to understand the architectural decisions before contributing.

---

## ☕ Support the Project

If Smart Management is useful to you, consider buying me a coffee! It helps keep the project alive and motivated.

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-FFDD00?logo=buymeacoffee&logoColor=black)](https://buymeacoffee.com/rawnato)

---

## 📄 License

**Smart Management** is open-source software released under a **non-commercial license**.

You are free to:
- ✅ Use it for personal projects
- ✅ Self-host for your own use
- ✅ Study, modify, and learn from the code
- ✅ Contribute to the project

You may **not**:
- ❌ Use it for commercial purposes
- ❌ Sell it or offer it as a paid service (SaaS)
- ❌ Redistribute modified versions for commercial gain

---

## ⭐ Star History

If Smart Management helped you manage a project (or just sparked an idea), consider giving it a ⭐ on GitHub. It helps others discover the project.

---

---

Built with ❤️ by the community.

_"The user talks to the AI, and project management happens as a consequence."_
