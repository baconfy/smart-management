# Roadmap: AI-Powered Project Manager

> **Reference:** See [VISION.md](./VISION.md) for full project vision, architecture, and data model.
> **Last updated:** 2026-02-15

---

## Key Decisions Made

These decisions were made during the ideation phase and are the foundation of the project:

1. **Chat-first approach:** The primary interface is a conversation, not a dashboard. Project management happens as a consequence of chatting with AI agents.
2. **Meeting Room model:** `Project > Conversation > Agent`. The user opens a conversation and the Moderator routes to the right agent. Multiple agents can respond in the same conversation.
3. **Invisible Moderator:** Uses an affordable model (Haiku/GPT-4o-mini) for classification. Routes directly when confident (>= 0.8), asks the user to choose when uncertain. User never sees or manages the Moderator.
4. **Agents are per-project:** Created automatically when a project is created. Pre-defined agents: Architect, Analyst, PM, Technical. Instructions are editable per project with a "reset to default" button.
5. **Two-layer instructions:** `base_instructions` (system, not editable) + `custom_instructions` (user-editable, resettable). Prevents users from breaking core agent behavior.
6. **Artifacts as knowledge bridge:** Agents don't share conversation histories. They read/write structured artifacts (Decisions, Business Rules, Tasks, Implementation Notes). Conversations are ephemeral; knowledge lives in artifacts.
7. **Separate artifact tables:** Each artifact type has its own table (not polymorphic).
8. **Multi-provider:** User provides their own API keys. Supports Anthropic, OpenAI, Gemini, etc. via Laravel AI SDK.
9. **Open Source:** Self-hosted, community-driven.
10. **Stack:** Laravel 12 + Inertia.js + React + Laravel AI SDK.

---

## Phase 1 — Foundation

> Goal: A working project with a single agent (Architect) and one artifact type (Decisions). Prove the core loop works: user talks → agent responds → artifact is created.

### 1.1 Project Setup

- [X] Initialize the Laravel 12 project with Inertia.js + React
- [X] Install and configure Laravel AI SDK (`laravel/ai`)
- [X] Configure multi-provider support (API keys via `.env`)
- [X] Publish AI SDK migrations and run them

### 1.2 Data Model — Core

- [ ] Migration: `projects` table
- [ ] Migration: `project_agents` table (with `base_instructions`, `custom_instructions`, `is_system`, `is_default`)
- [ ] Migration: `conversations` table
- [ ] Migration: `conversation_messages` table (with `project_agent_id`)
- [ ] Eloquent models with relationships

### 1.3 Project CRUD

- [ ] Create projects (with automatic agent seeding: Moderator and Architect only for now)
- [ ] List projects
- [ ] Project detail page (basic layout with chat area)

### 1.4 First Agent: Architect

- [ ] `ArchitectAgent` class implementing `Agent, Conversational, HasTools`
- [ ] Base instructions (Blade template)
- [ ] Default custom instructions
- [ ] Conversation persistence via `RemembersConversations`

### 1.5 First Artifact: Decisions

- [ ] Migration: `decisions` table
- [ ] `CreateDecision` tool
- [ ] `ListDecisions` tool
- [ ] `UpdateDecision` tool
- [ ] Decisions list view in project UI

### 1.6 Chat UI

- [ ] Chat component (send message, display response)
- [ ] Streaming support (SSE via AI SDK `stream()`)
- [ ] Conversation list in project sidebar
- [ ] New conversation / continue conversation

### Milestone: User can create a project, chat with the Architect, and see architecture decisions being recorded.

---

## Phase 2 — Full Agent System

> Goal: All 4 agents working with the Moderator routing. All artifact types functional.

### 2.1 Remaining Agents

- [ ] `AnalystAgent` — reads Decisions, writes Business Rules
- [ ] `PMAgent` — reads Decisions + Business Rules, writes Tasks
- [ ] `TechnicalAgent` — reads all, writes Implementation Notes

### 2.2 Remaining Artifacts

- [ ] Migration: `business_rules` table
- [ ] Migration: `tasks` table (with `parent_task_id`, `sort_order`)
- [ ] Migration: `implementation_notes` table
- [ ] Tools for each: Create, List, Update

### 2.3 Moderator

- [ ] `ModeratorAgent` class with `#[UseCheapestModel]`
- [ ] Structured output: `target_agent`, `confidence`, `reasoning`, `alternatives`
- [ ] Routing logic: direct route (>= 0.8) vs. user selection fallback
- [ ] Frontend: agent selection widget when Moderator is uncertain
- [ ] Display which agent responded (subtle indicator in chat bubble)

### 2.4 Agent Management UI

- [ ] View project agents list
- [ ] Edit custom instructions per agent
- [ ] "Reset to default" button
- [ ] Agent settings (provider preference, temperature)

### Milestone: Full meeting room experience. User chats naturally, Moderator routes, all artifact types are generated.

---

## Phase 3 — Task System

> Goal: Tasks are first-class citizens with their own views and dedicated technical chat.

### 3.1 Task Views

- [ ] Task list view (filterable by status, priority, phase)
- [ ] Task board view (kanban-style by status)
- [ ] Task detail view (metadata, description, implementation notes)

### 3.2 Task Chat

- [ ] Dedicated technical chat per task
- [ ] Technical agent scoped to task context (loads task + related artifacts)
- [ ] Implementation Notes generated from task conversations

### 3.3 Task Management

- [ ] Manual task status update (from UI)
- [ ] Task ordering / drag-and-drop
- [ ] Subtasks (parent_task_id)

### Milestone: Complete task lifecycle — created by PM agent, discussed in technical chat, notes accumulated, status tracked.

---

## Phase 4 — Polish & Custom Agents

> Goal: Refined UX and extensibility via user-created agents.

### 4.1 Custom Agents

- [ ] "Create agent" form (name + description)
- [ ] AI-assisted instruction generation (internal agent writes optimized instructions from description)
- [ ] Custom agents stored in `project_agents` with `type = custom`
- [ ] Dynamic instantiation via Anonymous Agents
- [ ] Edit / delete custom agents

### 4.2 UX Polish

- [ ] Artifact cross-references (link a Decision to the Tasks it influenced)
- [ ] Conversation search
- [ ] Project dashboard / overview (artifact counts, progress summary)
- [ ] Dark mode

### 4.3 AI Polish

- [ ] Context window management strategy (artifact summarization for large projects)
- [ ] Conversation title auto-generation
- [ ] Agent response quality improvements based on real usage

### Milestone: Extensible system. Users can create specialized agents tailored to their workflow.

---

## Future Phases (Post-MVP)

These are not planned in detail yet. Ordered by perceived value:

- **Time Tracking:** Agent can log time from conversation ("worked 3h on HD Wallet")
- **Financial Management:** Budget tracking per project, invoice generation
- **Integrations:** GitHub (link commits to tasks), Slack (notifications)
- **Team Features:** Multiple users per project, role-based access
- **Templates:** Project templates with pre-configured agents and artifact structures
- **Analytics:** AI-generated project health reports, velocity tracking
- **Mobile App:** Responsive or native mobile experience

---

## Technical Notes for Future Chats

When starting a new chat about this project, provide these files for context:

- `VISION.md` — Full project vision, architecture, data model, and code examples
- `ROADMAP.md` — This file. Phased execution plan with current status.

The project uses **Laravel AI SDK** (`laravel/ai`) which provides: Agents (classes with instructions/tools/structured output), RemembersConversations (auto-persistence), Anonymous Agents (dynamic creation), Provider Tools (WebSearch, WebFetch, FileSearch), multi-provider support, streaming, and comprehensive testing (fake/assertions).

Key architectural patterns:

- Agents as dedicated PHP classes with `Promptable` trait
- Tools as dedicated PHP classes implementing `Tool` interface
- Instructions rendered via Blade templates (dynamic context injection)
- Per-project agents with two-layer instructions (`base` + `custom`)
- Invisible Moderator with confidence-based routing
- Artifacts as structured data in separate tables (not conversation history)
