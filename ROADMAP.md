# Roadmap: AI-Powered Project Manager

> **Reference:** See [VISION.md](./VISION.md) for full project vision, architecture, and data model.
> **Last updated:** 2026-02-17 (Steps 1-11a complete, 130 tests)

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
13. **Atomic invokable Actions + Services:** Actions are single-responsibility invokable classes (`__invoke`). Services orchestrate multiple actions in a transaction.
14. **Open Source:** Self-hosted, community-driven.
15. **Stack:** Laravel 12 + Inertia.js + React 19 + Tailwind v4 + Laravel AI SDK.
16. **Wayfinder:** Frontend route helpers auto-generated from Laravel routes. No Ziggy, no manual route files. Import from `@/routes`.
17. **Essentials:** `nunomaduro/essentials` with `Unguard` (no `$fillable`), `ShouldBeStrict` (no lazy loading), `ImmutableDates`, `PreventStrayRequests` in tests.
18. **ULID for URLs:** Projects and Tasks use `ulid` column for route model binding. `getRouteKeyName()` returns `'ulid'`, `booted()` auto-generates on creation.
19. **SDK Request Array Access:** Tools access parameters via `ArrayAccess` (`$request['key']`), not `get()` or `input()`. SDK `Request` implements `ArrayAccess`. Optional fields use null coalescing: `$request['key'] ?? null`.
20. **Agent Tools via Constructor DI:** Tools receive `Project` in constructor, `ArchitectAgent` instantiates them in `tools()` method with `$this->project()`.
21. **Multi-agent chat:** `agent_ids` array, not single `agent_id`. User message stored once, each agent responds independently. Controller manages conversation lifecycle manually (not SDK middleware) to avoid duplicate user messages.
22. **Agent resolution:** `ArchitectAgent` for architect type, `GenericAgent` for all others. Resolved via `match` in controller. New agent classes added as `AgentType` cases grow.
23. **Contextual sidebar:** Single sidebar that drill-downs: Projects list → Project nav (Conversations, Tasks, Decisions, etc.) → sub-views. No dual-sidebar. Navigation determined by URL via `useSidebarNavigation` hook.

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

### 1.4 Project CRUD ✅
- [x] `CreateProjectService` + atomic actions (`CreateProject`, `AddProjectMember`, `SeedProjectAgents`)
- [x] Instruction `.md` files in `resources/instructions/`
- [x] List projects (`GET /projects` with membership scoping)
- [x] Project detail page (`GET /projects/{ulid}` with `ProjectPolicy` authorization)
- [x] `StoreProjectRequest` form validation
- [x] ULID route model binding (`getRouteKeyName`)
- [x] Placeholder Inertia pages (`projects/index`, `projects/show`)

### 1.5 First Agent: Architect ✅
- [x] `ArchitectAgent` class implementing `Agent, Conversational, HasTools` (8 tests)
- [x] `GenericAgent` fallback class for agent types without dedicated class
- [x] `ReadsConversationHistory` concern — read history without triggering SDK middleware
- [x] Instructions loaded from `ProjectAgent` model + project context appended
- [x] Conversation persistence via `RemembersConversations` + custom store
- [x] Agent faking and assertion support via SDK `fake()`/`assertPrompted()`

### 1.6 First Artifact: Decisions (Partial) ✅
- [x] `CreateDecision` tool — creates decision record with title, choice, reasoning, alternatives, context (10 tests)
- [x] `ListDecisions` tool — lists project decisions with optional status filter (10 tests)
- [ ] `UpdateDecision` tool
- [ ] Decisions list view in project UI

### 1.7 Chat System
- [x] `ChatController` with multi-agent support (`agent_ids` array) (12 tests)
- [x] `StoreChatMessageRequest` validation (message, agent_ids, conversation_id)
- [x] Raw store methods (`storeRawUserMessage`, `storeRawAssistantMessage`) — 1 user message, N agent responses
- [x] Broadcast channel auth (`project.{projectId}.chat`)
- [ ] Contextual sidebar (projects list → project nav drill-down)
- [ ] Chat UI component (send message, display timeline)
- [ ] Agent toggle (multi-select which agents respond)
- [ ] Streaming via Reverb (`broadcastNow()` — currently using `prompt()`)
- [ ] Conversation list in sidebar
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

### Data Layer (Steps 1-7) ✅

| Step | What | Tests | Status |
|------|------|-------|--------|
| 1 | `projects` + `project_members` | 12 | ✅ |
| 2 | `project_agents` + `AgentType` enum | 10 | ✅ |
| 3 | Modified SDK migration (agent_conversations + messages) | — | ✅ |
| 4 | `ProjectConversationStore` | 6 | ✅ |
| 5 | `decisions` + `business_rules` + enums | 17 | ✅ |
| 6 | `tasks` + `implementation_notes` + enums | 19 | ✅ |
| 7 | `CreateProjectService` + atomic actions + instruction `.md` files | 9 | ✅ |

### HTTP + Agent Layer (Steps 8-11a) ✅

| Step | What | Tests | Status |
|------|------|-------|--------|
| 8 | Project routes + controller + policy + Inertia pages | 15 | ✅ |
| 9 | `ArchitectAgent` class + `Promptable`/`RemembersConversations` | 8 | ✅ |
| 10 | `CreateDecision` + `ListDecisions` tools | 10 | ✅ |
| 11a | `ChatController` multi-agent + `GenericAgent` + raw store methods | 12 | ✅ |

**Total: 130 tests, all passing.**

### Frontend (Steps 11b+) — In Progress

| Step | What | Status |
|------|------|--------|
| 11b-i | Contextual sidebar + layout restructure | ⏳ Next |
| 11b-ii | Chat UI components (messages, input, agent toggles) | Pending |
| 11b-iii | Reverb streaming integration | Pending |

### Test Organization

- `tests/Unit/` — Models, Actions, Services, Stores (no HTTP)
- `tests/Feature/` — HTTP tests (controllers, routes, middleware)

---

## Technical Notes for Future Chats

When starting a new chat about this project, provide these files for context:
- `VISION.md` — Full project vision, architecture, data model, and code examples
- `ROADMAP.md` — This file. Phased execution plan with current status.

The project uses **Laravel AI SDK** (`laravel/ai`) which provides: Agents (classes with instructions/tools/structured output), RemembersConversations (auto-persistence via ConversationStore interface), Anonymous Agents (dynamic creation), Provider Tools (WebSearch, WebFetch, FileSearch), multi-provider support, streaming, and comprehensive testing (fake/assertions).

Key architectural patterns:
- Agents as dedicated PHP classes with `Promptable` trait
- Tools as dedicated PHP classes implementing `Tool` interface — access params via `$request['key']` (ArrayAccess)
- Instructions stored in DB, defaults from `resources/instructions/*.md`
- Per-project agents with editable instructions (single source: database)
- `ProjectConversationStore` extends SDK's `DatabaseConversationStore`, bound in `boot()`
- Multi-agent chat: controller stores user message once, loops agents, stores each response independently
- `ReadsConversationHistory` concern: reads conversation history without triggering SDK `RememberConversation` middleware
- `GenericAgent` fallback for agent types without a dedicated class
- Invisible Moderator with confidence-based routing (Phase 2)
- Artifacts as structured data in separate tables (not conversation history)
- String columns in DB + PHP Enums for validation
- ULID columns for public-facing URLs (`getRouteKeyName()`)
- Project membership via `project_members` pivot with roles
- Atomic invokable Actions + Services for orchestration
- `nunomaduro/essentials` with `Unguard` enabled (no `$fillable` needed)
- **Wayfinder** (`@laravel/vite-plugin-wayfinder`) generates typed frontend route helpers from Laravel routes — no Ziggy, no manual route files
- `ShouldBeStrict` enabled (no lazy loading, no silently discarding attributes)
- Contextual sidebar: single sidebar that drill-downs based on URL (projects → project nav → sub-views)
