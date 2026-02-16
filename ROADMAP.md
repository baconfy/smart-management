# Roadmap: AI-Powered Project Manager

> **Reference:** See [VISION.md](./VISION.md) for full project vision, architecture, and data model.
> **Last updated:** 2026-02-17

---

## Key Decisions Made

These decisions were made during ideation and refined during implementation:

1. **Chat-first approach:** The primary interface is a conversation, not a dashboard. Project management happens as a consequence of chatting with AI agents.
2. **Meeting Room model:** `Project > Conversation > Agent`. The user opens a conversation and the Moderator routes to the right agent. Multiple agents can respond in the same conversation.
3. **Invisible Moderator:** Uses a cheap model (Haiku/GPT-4o-mini) for classification. Routes directly when confident (>= 0.8), asks the user to choose when uncertain. User never sees or manages the Moderator.
4. **Agents are per-project:** Created automatically when a project is created. Pre-defined agents: Architect, Analyst, PM, Technical. Instructions are editable per project with a "reset to default" button.
5. **Instructions in `.md` → database:** Default instructions live in `resources/instructions/*.md`. On project creation, content is copied to `project_agents.instructions` in the database. Runtime always reads from the database. "Reset to default" re-copies from `.md`.
6. **Artifacts as knowledge bridge:** Agents don't share conversation histories. They read/write structured artifacts (Decisions, Business Rules, Tasks, Implementation Notes). Conversations are ephemeral; knowledge lives in artifacts.
7. **Separate artifact tables:** Each artifact type has its own table (not polymorphic).
8. **Multi-provider:** User provides their own API keys. Supports Anthropic, OpenAI, Gemini, etc. via Laravel AI SDK.
9. **Project membership with roles:** `project_members` pivot table with roles (owner, admin, member, viewer). No `user_id` on projects table.
10. **Custom ConversationStore:** SDK's `RemembersConversations` trait resolves `ConversationStore` via container. We extend `DatabaseConversationStore` adding `project_id` and `project_agent_id`. Bound in `AppServiceProvider::boot()` to override SDK's default.
11. **String columns + PHP Enums:** All enum-like values stored as strings in DB. Validation via PHP Enums (`AgentType`, `DecisionStatus`, `BusinessRuleStatus`, `TaskStatus`, `TaskPriority`). No DB enums.
12. **Desktop-first:** No mobile. Complex PM tool with AI chat, agents, kanban, artifacts not suitable for mobile.
13. **Atomic Actions + Services:** Actions for single responsibilities, Services for orchestrating multiple actions.
14. **Open Source:** Self-hosted, community-driven.
15. **Stack:** Laravel 12 + Inertia.js + React 19 + Tailwind v4 + Laravel AI SDK.

---

## Phase 1 — Foundation

> Goal: A working project with a single agent (Architect) and one artifact type (Decisions). Prove the core loop works: user talks → agent responds → artifact is created.

### 1.1 Project Setup ✅
- [x] Initialize Laravel 12 project with Inertia.js + React (via docker-starter-kit)
- [x] Install and configure Laravel AI SDK (`laravel/ai`)
- [x] Docker environment with PostgreSQL, Redis, Horizon, Reverb
- [x] Auth via Fortify with 2FA
- [x] Pest 4 testing configured

### 1.2 Data Model — Core ✅
- [x] Migration + Model: `projects` table (12 tests)
- [x] Migration + Model: `project_members` table — pivot with roles (12 tests)
- [x] Migration + Model + Enum: `project_agents` table — `AgentType` enum, `visible()`/`defaults()` scopes (10 tests)
- [x] Modified SDK migration: added `project_id` to `agent_conversations`, `project_agent_id` to `agent_conversation_messages`
- [x] Custom `ProjectConversationStore` extending SDK's `DatabaseConversationStore` — `forProject()`, `withAgent()`, `reset()` (6 tests)
- [x] Bound in `AppServiceProvider::boot()` to override SDK default

### 1.3 Data Model — Artifacts ✅
- [x] Migration + Model + Enum: `decisions` table — `DecisionStatus` enum, `active()` scope (17 tests)
- [x] Migration + Model + Enum: `business_rules` table — `BusinessRuleStatus` enum, `active()` scope (17 tests)
- [x] Migration + Model + Enums: `tasks` table — `TaskStatus`/`TaskPriority` enums, `withStatus()` scope, subtasks via `parent_task_id` (19 tests)
- [x] Migration + Model: `implementation_notes` table — `code_snippets` JSON (19 tests)

### 1.4 Project CRUD
- [ ] `CreateProject` action (with agent seeding from `.md` files + owner member)
- [ ] Instruction `.md` files in `resources/instructions/`
- [ ] List projects
- [ ] Project detail page (basic layout with chat area)

### 1.5 First Agent: Architect
- [ ] `ArchitectAgent` class implementing `Agent, Conversational, HasTools`
- [ ] Instructions `.md` template
- [ ] Conversation persistence via `RemembersConversations` + custom store
- [ ] Streaming support

### 1.6 First Artifact: Decisions
- [ ] `CreateDecision` tool
- [ ] `ListDecisions` tool
- [ ] `UpdateDecision` tool
- [ ] Decisions list view in project UI

### 1.7 Chat UI
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

### 2.2 Remaining Artifact Tools
- [ ] Tools for Business Rules: Create, List, Update
- [ ] Tools for Tasks: Create, List, Update
- [ ] Tools for Implementation Notes: Create, List

### 2.3 Moderator
- [ ] `ModeratorAgent` class with `#[UseCheapestModel]`
- [ ] Structured output: `target_agent`, `confidence`, `reasoning`, `alternatives`
- [ ] Routing logic: direct route (>= 0.8) vs. user selection fallback
- [ ] Frontend: agent selection widget when Moderator is uncertain
- [ ] Display which agent responded (subtle indicator in chat bubble)

### 2.4 Agent Management UI
- [ ] View project agents list
- [ ] Edit instructions per agent
- [ ] "Reset to default" button (re-copies from `.md`)
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
- [ ] AI-assisted instruction generation
- [ ] Custom agents stored in `project_agents` with `type = custom`
- [ ] Dynamic instantiation via Anonymous Agents
- [ ] Edit / delete custom agents

### 4.2 UX Polish
- [ ] Artifact cross-references (link a Decision to the Tasks it influenced)
- [ ] Conversation search
- [ ] Project dashboard / overview (artifact counts, progress summary)
- [ ] Dark mode refinement

### 4.3 AI Polish
- [ ] Context window management strategy (artifact summarization for large projects)
- [ ] Conversation title auto-generation
- [ ] Agent response quality improvements based on real usage

### Milestone: Extensible system. Users can create specialized agents tailored to their workflow.

---

## Future Phases (Post-MVP)

Ordered by perceived value:

- **Time Tracking:** Agent can log time from conversation ("worked 3h on HD Wallet")
- **Financial Management:** Budget tracking per project, invoice generation
- **Integrations:** GitHub (link commits to tasks), Slack (notifications)
- **Team Features:** Multiple users per project with role-based access (infrastructure already supports this via project_members)
- **Templates:** Project templates with pre-configured agents and artifact structures
- **Analytics:** AI-generated project health reports, velocity tracking

---

## Implementation Steps

### Data Layer (Complete) ✅

| Step | What | Tests | Status |
|------|------|-------|--------|
| 1 | `projects` + `project_members` | 12 | ✅ |
| 2 | `project_agents` + `AgentType` enum | 10 | ✅ |
| 3 | Modified SDK migration (agent_conversations + messages) | — | ✅ |
| 4 | `ProjectConversationStore` | 6 | ✅ |
| 5 | `decisions` + `business_rules` + enums | 17 | ✅ |
| 6 | `tasks` + `implementation_notes` + enums | 19 | ✅ |

**Total: 64 tests, all passing.**

### Next: Project CRUD (Step 7+)

| Step | What | Status |
|------|------|--------|
| 7 | `CreateProject` action + agent seeding + instruction `.md` files | ⏳ Next |
| 8 | Project routes + controllers + Inertia pages | Pending |
| 9 | First Agent: `ArchitectAgent` class | Pending |
| 10 | First Tools: `CreateDecision`, `ListDecisions` | Pending |
| 11 | Chat UI + streaming | Pending |

---

## Technical Notes for Future Chats

When starting a new chat about this project, provide these files for context:
- `VISION.md` — Full project vision, architecture, data model, and code examples
- `ROADMAP.md` — This file. Phased execution plan with current status.

The project uses **Laravel AI SDK** (`laravel/ai`) which provides: Agents (classes with instructions/tools/structured output), RemembersConversations (auto-persistence via ConversationStore interface), Anonymous Agents (dynamic creation), Provider Tools (WebSearch, WebFetch, FileSearch), multi-provider support, streaming, and comprehensive testing (fake/assertions).

Key architectural patterns:
- Agents as dedicated PHP classes with `Promptable` trait
- Tools as dedicated PHP classes implementing `Tool` interface
- Instructions stored in DB, defaults from `resources/instructions/*.md`
- Per-project agents with editable instructions (single source: database)
- `ProjectConversationStore` extends SDK's `DatabaseConversationStore`, bound in `boot()`
- Invisible Moderator with confidence-based routing
- Artifacts as structured data in separate tables (not conversation history)
- String columns in DB + PHP Enums for validation
- Project membership via `project_members` pivot with roles
- Atomic Actions + Services for business logic
