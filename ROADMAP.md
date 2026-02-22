# Roadmap: AI-Powered Project Manager

> **Reference:** See [VISION.md](./VISION.md) for full project vision, architecture, and data model.
> **Last updated:** 2026-02-22 (Steps 1-17 complete, 426 tests)

---

## Key Decisions Made

These decisions were made during ideation and refined during implementation:

1. **Chat-first approach:** The primary interface is a conversation, not a dashboard. Project management happens as a consequence of chatting with AI agents.
2. **Meeting Room model:** `Project > Conversation > Agent`. The user opens a conversation and the Moderator routes to the right agent. Multiple agents can respond in the same conversation.
3. **Subtle Moderator:** Uses a cheap model (via `#[UseCheapestModel]`) for classification. Routes directly when confident (>= 0.8) — per-agent thinking bubbles show who's responding. Low confidence (< 0.8) broadcasts `AgentSelectionRequired` — user picks agents via poll UI in ChatInput. Supports multi-agent routing. User never sees or manages the Moderator directly.
4. **Agents are per-project:** Created automatically when a project is created. Pre-defined agents: Architect, Analyst, PM, Technical. Instructions are editable per project with a "reset to default" button.
5. **Instructions in `.md` → database:** Default instructions live in `resources/instructions/*.md`. On project creation, content is copied to `project_agents.instructions` in the database. Runtime always reads from the database. "Reset to default" re-copies from `.md`.
6. **Artifacts as knowledge bridge:** Agents don't share conversation histories. They read/write structured artifacts (Decisions, Business Rules, Tasks, Implementation Notes). Conversations are ephemeral; knowledge lives in artifacts.
7. **Separate artifact tables:** Each artifact type has its own table (not polymorphic).
8. **Multi-provider:** User provides their own API keys. Supports Anthropic, OpenAI, Gemini, etc. via Laravel AI SDK.
9. **Project membership with roles:** `project_members` pivot table with roles (owner, admin, member, viewer). No `user_id` on projects table.
10. **Custom ConversationStore:** SDK's `RemembersConversations` trait resolves `ConversationStore` via container. We extend `DatabaseConversationStore` adding `project_id` and `project_agent_id`. Bound in `AppServiceProvider::boot()` to override SDK's default.
11. **String columns + PHP Enums:** All enum-like values stored as strings in DB. Validation via PHP Enums (`AgentType`, `DecisionStatus`, `BusinessRuleStatus`, `TaskPriority`). No DB enums. Exception: `TaskStatus` is now a model/table (Decision 54).
12. **Desktop-first:** No mobile. Complex PM tool with AI chat, agents, kanban, artifacts not suitable for mobile.
13. **Atomic invokable Actions + Services:** Actions are single-responsibility invokable classes (`__invoke`). Services orchestrate multiple actions in a transaction.
14. **Open Source:** Self-hosted, community-driven.
15. **Stack:** Laravel 12 + Inertia.js + React 19 + Tailwind v4 + Laravel AI SDK.
16. **Wayfinder:** Frontend route helpers auto-generated from Laravel routes. No Ziggy, no manual route files. Import from `@/routes`.
17. **Essentials:** `nunomaduro/essentials` with `Unguard` (no `$fillable`), `ShouldBeStrict` (no lazy loading), `ImmutableDates`, `PreventStrayRequests` in tests.
18. **ULID for URLs:** Projects and Tasks use `ulid` column for route model binding. `getRouteKeyName()` returns `'ulid'`, `booted()` auto-generates on creation. Conversations use ULID as primary key directly.
19. **SDK Request Array Access:** Tools access parameters via `ArrayAccess` (`$request['key']`), not `get()` or `input()`. SDK `Request` implements `ArrayAccess`. Optional fields use null coalescing: `$request['key'] ?? null`.
20. **Agent Tools via Constructor DI:** Tools receive `Project` in constructor, `ArchitectAgent` instantiates them in `tools()` method with `$this->project()`.
21. **Multi-agent chat:** `agent_ids` array, not single `agent_id`. User message stored once, each agent responds independently. Controller manages conversation lifecycle manually (not SDK middleware) to avoid duplicate user messages.
22. **Agent resolution (unified):** All agents use `GenericAgent`. No dedicated agent classes. Tools and model resolved from `project_agents` database columns. `SeedProjectAgents` assigns tools per agent type.
23. **Contextual sidebar:** Three-level drill-down: `ProjectsPanel` (project list) → `ProjectNavPanel` (Conversations, Tasks, etc.) → `ConversationsNavPanel` (conversation list + New Conversation). Sidebar panel determined by page via `sidebar` prop in AppLayout.
24. **flex-col-reverse for chat scroll:** Chat message container uses `flex-col-reverse` for automatic scroll-to-bottom without useEffect or refs. Browser natively starts scroll at the end.
25. **SidebarProvider h-svh:** Changed from `min-h-svh` (allows infinite growth) to `h-svh` (fixed viewport height) to enable internal scroll in nested flex containers.
26. **Cursor pagination for conversations:** Sidebar conversation list uses `cursorPaginate(20)` for stable pagination with new conversations appearing. Frontend type `CursorPaginated<T>`.
27. **Agent column nullable:** `agent_conversation_messages.agent` is nullable — user messages don't have an agent class, only assistant messages do.
28. **ULID string casting:** `Str::ulid()` returns Symfony ULID object, must be cast to `(string)` when storing in DB via Eloquent.
29. **Conversation title deferred:** Title auto-generation via AI deferred to Reverb streaming step. For now, uses first ~100 chars of user message.
30. **Async agent responses via Reverb:** ChatController dispatches `ProcessAgentMessage` Jobs (1 per agent, parallel via Redis). Jobs call AI, save response, broadcast `AgentMessageReceived`. Frontend Echo listeners append messages in real-time.
31. **ChatController invokable:** Single `__invoke` method with private helpers: `resolveConversation()` (create or find), `dispatchAgentJobs()` (loop agents + dispatch). Clean separation of concerns.
32. **Docker Reverb networking:** Backend uses `REVERB_HOST=reverb` + `REVERB_PORT=9001` (Docker internal). Frontend uses `VITE_REVERB_HOST=localhost` + `VITE_REVERB_PORT=9012` (host-forwarded). Three contexts: container→container, browser→host, config→env.
33. **Per-agent processing state replaces derived loading:** Original `waitingForResponse` (derived from last message role) replaced by `processingAgents` state driven by `AgentsProcessing` event. Each agent gets named thinking bubble, removed individually as responses arrive.
34. **Message dedup by ID:** Echo listener checks `prev.some(m => m.id === e.message.id)` before appending. Prevents duplicates when Inertia page reload and WebSocket both deliver the same message.
35. **UpdateDecision tool:** Partial updates — only provided fields are changed. Scoped to project (cannot update other project's decisions). Returns "not found" for cross-project attempts.
36. **Decisions post-it grid:** Decisions displayed as colored post-it cards in a grid. Color by status (green=active, amber=superseded, red=deprecated). Click opens Dialog with full details. Alternating rotation for visual interest.
37. **ModeratorAgent with `#[UseCheapestModel]`:** Invisible router. Analyzes message, returns JSON with `agents[]` (type + confidence) and `reasoning`. Multi-agent capable — can route to multiple agents simultaneously.
38. **Moderator confidence routing:** >= 0.8 routes directly + dispatches agent jobs. < 0.8 broadcasts `AgentSelectionRequired` with candidates — user selects agents via poll UI in ChatInput, POST to `SelectAgentsController` dispatches jobs.
39. **AgentsProcessing event:** Broadcasts which agents are "thinking" before jobs execute. Frontend shows per-agent thinking bubbles with agent name + bouncing dots. Replaces generic `waitingForResponse`.
40. **Per-agent thinking bubbles:** Each processing agent gets its own bubble. As each responds via `AgentMessageReceived`, its bubble is removed. Parallel agents visible simultaneously.
41. **Async ProcessChatMessage job:** HTTP request only saves user message + dispatches `ProcessChatMessage` job → redirect IMMEDIATE. Job handles Moderator call (if no `agent_ids`) → resolves agents → dispatches `ProcessAgentMessage` per agent. Eliminates all AI latency from HTTP cycle.
42. **isRouting state:** Generic thinking bubble (no agent name) appears immediately on form submit. When `AgentsProcessing` event arrives, `isRouting = false` and per-agent bubbles replace it. Eliminates perceived lag.
43. **Turn-based message grouping:** Messages grouped into "turns" (user message + all assistant responses). Single response = inline display. Multi-agent response = Base UI Tabs with pill-style agent name triggers.
44. **GenericAgent unification:** Removed `ArchitectAgent`. ALL agent types use `GenericAgent::make(projectAgent: $projectAgent)`. Behavior driven entirely by database config (instructions, tools, model). One class, infinite agents.
45. **Dynamic tools from database:** `project_agents.tools` JSON array (e.g. `["CreateDecision", "ListDecisions"]`). `GenericAgent::tools()` resolves class names dynamically: `"App\\Ai\\Tools\\{$name}"`. Tools assigned per agent type in `SeedProjectAgents`.
46. **Per-agent model override:** `project_agents.model` (string, nullable). Passed to `$agent->prompt(model: ...)` at runtime. Null = SDK default from `config/ai.php`. Enables cheap models for simple agents, smart models for complex.
47. **No `conversation_message_id` on artifacts:** Dropped from all artifact tables. `project_id` already links artifacts to projects. Traceability via conversation history shows tool calls. Simpler schema, no orphan risk.
48. **Artifact tools pattern:** All tools follow same structure: constructor receives `Project`, `handle()` creates/lists/updates, returns confirmation string. Create/Update use `array_filter` for partial updates. List supports status/category/priority filters.
49. **Wayfinder route imports:** Frontend uses auto-generated TypeScript route functions from `@/routes/projects/tasks`, `@/routes/projects/business-rules`, etc. No `route()` helper, no hardcoded URLs.
50. **Agent selection poll in ChatInput:** Low confidence → `AgentSelectionRequired` event → `InputChatPoll` replaces textarea with reasoning + candidate badges (checkboxes) + "Ask selected" button. POST `/p/{project}/c/{conversation}/select-agents` dispatches jobs. Poll reasoning adapts to conversation language naturally via AI.
51. **AI falsy value sanitization:** AI models often send `0`, `""`, or `false` for optional fields. All tool `handle()` methods use `($request['field'] ?? null) ?: null` pattern — `??` handles missing keys, `?:` handles falsy values. `array_filter` callbacks use `$value !== null && $value !== ''`.
52. **JsonSchema SDK limitation:** Laravel AI SDK's `JsonSchema` does not support `$schema->object()->properties()` for nested objects. Complex nested types (e.g. `code_snippets` array of objects) use `$schema->string()` with JSON description + `json_decode` + validation in `handle()`.
53. **Horizon worker code reload:** After code changes, workers keep old code in memory. Must run `php artisan horizon:terminate` to reload. `queue:flush` clears failed jobs, `horizon:clear` clears pending jobs.
54. **Custom task statuses replace enum:** `TaskStatus` PHP enum replaced by `task_statuses` database table with `project_id` FK. Each project has customizable statuses. Default 3: To Do, In Progress, Done. Columns: `name`, `slug`, `color`, `position`, `is_default`, `is_closed`. AI tools use slug-based status with dynamic schema describing available options. FK on tasks uses `restrictOnDelete` (must move tasks before deleting a status). `SeedProjectStatuses` action runs during project creation.
55. **AI priority validation with tryFrom:** AI models may send invalid priority values (e.g. `"critical"`). Tools validate with `TaskPriority::tryFrom()` — invalid values are silently discarded instead of crashing with `ValueError`.
56. **Kanban-only task view:** Task list view removed. Tasks displayed exclusively as kanban board with drag-and-drop via `@dnd-kit`. `pointerWithin` collision detection for accurate column targeting. `PointerSensor` with `distance: 8` distinguishes click (navigate) from drag.
57. **Unified chat page:** Single Inertia page handles both empty state (centered input) and active conversation (messages + input at bottom). Nullable `$conversation` parameter. CSS transitions animate between states.
58. **JSON chat responses:** `SendMessageController` returns `{ conversation_id }` JSON instead of redirect. Frontend uses axios POST with optimistic UI. Enables SPA-like experience without full page reloads.
59. **Hidden messages via meta:** Auto-generated prompts (e.g. task start) stored with `meta->hidden = true`. Backend filters with `whereNull('meta->hidden')`. AI retains context, user doesn't see system prompts.
60. **Invokable controller pattern:** Domain-driven folder structure (`Project/Conversation/`, `Project/Task/`). Each controller is single-action invokable (`__invoke`). Replaces multi-method controllers.
61. **initialProcessingAgents prop:** Backend detects conversation with no assistant response yet, passes processing agents to frontend. Solves race condition where Echo event fires before frontend connects.
62. **CSS animation for chat transition:** Flex spacers + `transition-[flex]` animate input from center to bottom. Title fades via `max-h-0 opacity-0`. No JavaScript animation library needed.
63. **TaskDetails in floating Dialog:** When task has conversation, details shown in Dialog triggered by floating button (bottom-right). Keeps chat as primary UI, details accessible on demand.
64. **Settings with tab links:** Settings page uses `<Link>` styled as tabs instead of `<Tabs>` component. Each tab is a separate route (`/s` General, `/s/a` Agents). Shared `SettingsLayout` component renders tab navigation.
65. **Agent CRUD with Sheet:** Agent create/edit uses Sheet (slide-in panel) instead of Dialog or separate page. Keeps list visible while editing. Uses Inertia `<Form>` with wayfinder routes.
66. **Scoped route binding for agents:** Routes use `->scopeBindings()` to let Laravel scope `{agent}` to `{project}` via the `agents()` relationship. Eliminates manual `abort_unless($agent->project_id === $project->id, 404)` checks.
67. **Reset agent from .md:** Default agents can reset name + instructions from `resources/instructions/{type}.md`. Name extracted from first line (`# Name`). Custom agents cannot be reset (403).
68. **`is_in_progress` flag on task_statuses:** Same pattern as `is_default` and `is_closed`. `StartTaskConversation` moves task to in-progress status automatically. No hardcoded slugs.
69. **`StartTaskConversation` as Service:** Renamed from Action to Service (`App\Services`). Orchestrates: create conversation, update task status, create hidden message, dispatch agent job. Follows project convention (Services orchestrate, Actions are single-responsibility).
70. **Kanban column scroll:** Column uses `h-full` + droppable area uses `overflow-y-auto`. Prevents infinite column growth when many tasks exist.

---

## Phase 1 — Foundation ✅

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
- [x] Migration + Model: `tasks` table — `TaskPriority` enum, `task_status_id` FK to `task_statuses`, subtasks via `parent_task_id`
- [x] Migration + Model: `task_statuses` table — per-project customizable statuses, `default()`/`closed()`/`ordered()` scopes
- [x] Migration + Model: `implementation_notes` table — `code_snippets` JSON

### 1.4 Project CRUD ✅
- [x] `CreateProjectService` + atomic actions (`CreateProject`, `AddProjectMember`, `SeedProjectStatuses`, `SeedProjectAgents`)
- [x] Instruction `.md` files in `resources/instructions/`
- [x] List projects (`GET /projects` with membership scoping)
- [x] Project detail page (`GET /projects/{ulid}` with `ProjectPolicy` authorization)
- [x] `StoreProjectRequest` form validation
- [x] ULID route model binding (`getRouteKeyName`)

### 1.5–1.7 (unchanged, collapsed for brevity)
- [x] First Agent: Architect (8 tests)
- [x] First Artifact: Decisions (30 tests)
- [x] Chat System (12 tests)

### Milestone: ✅ User can create a project, chat with agents, see responses with markdown. Conversations persist and are listed in sidebar.

---

## Phase 1.5 — Real-time Chat ✅
## Phase 2 — Full Agent System ✅

(Unchanged from previous version — see git history for details)

---

## Phase 3 — Task System & Artifact Views (Partial ✅)

> Goal: Tasks are first-class citizens with their own views and dedicated technical chat.

### 3.1 Artifact List Views ✅
- [x] Business Rules list view — Accordion by category, status badges
- [x] Decisions list view — post-it grid with Dialog detail, color by status
- [x] `BusinessRuleController` + `DecisionController` with `ProjectPolicy` authorization

### 3.2 Custom Task Statuses ✅
- [x] `task_statuses` table — per-project, customizable (name, slug, color, position, is_default, is_closed)
- [x] `TaskStatus` model with scopes: `default()`, `closed()`, `ordered()`
- [x] `SeedProjectStatuses` action — creates 3 defaults: To Do, In Progress, Done
- [x] Integrated into `CreateProjectService` (runs before agent seeding)
- [x] `TaskStatus` enum removed — replaced by database model
- [x] `Task` model updated — `status()` belongsTo relationship, `withStatus()`, `closed()`, `open()` scopes
- [x] AI tools updated — CreateTask, UpdateTask, ListTasks use slug-based status with dynamic schema
- [x] Priority validation — `TaskPriority::tryFrom()` guards against invalid AI values

### 3.3 Task Kanban Board ✅
- [x] `TaskController` — index (loads statuses + tasks with status), update (PATCH for drag-and-drop)
- [x] Kanban board — `@dnd-kit` with `DndContext`, `SortableContext`, `DragOverlay`
- [x] `KanbanColumn` — droppable with `useDroppable`, color header from status.color
- [x] `KanbanCard` — sortable, click navigates to show, drag moves between columns
- [x] `pointerWithin` collision detection for accurate column targeting
- [x] Optimistic UI — state updates instantly, PATCH persists via `router.patch` + `back()`
- [x] Empty state with CTA to start conversation with PM agent

### 3.4 Task Detail & Technical Chat ✅
- [x] Task show page — two states: TaskDetails (no conversation) vs Chat (with conversation)
- [x] `StartTaskConversation` action — creates hidden user message with task context + dispatches Technical agent
- [x] `Task/SendMessageController` — JSON response (replaces redirect-based ChatController)
- [x] `ChatProvider` reused across Conversation and Task pages
- [x] `defaultSelectedAgentIds` — Technical agent pre-selected on task chat
- [x] `initialProcessingAgents` — typing indicator on task start (before Echo connects)
- [x] Hidden messages — `meta->hidden` on auto-generated prompt, filtered in backend query
- [x] Orphan assistant turns — `groupIntoTurns` handles responses without visible user message
- [x] Standalone processing indicator — shows agent typing when no turns exist yet
- [x] Floating TaskDetails button — Dialog overlay with task info, subtasks, implementation notes
- [x] Action plan prompt — Technical agent responds with step-by-step plan, decisions, risks, subtasks
- [x] Language matching — agent responds in same language as task title/description

### 3.5 Unified Chat System ✅
- [x] Invokable controllers — `Conversation/IndexController` (unified index+show), `Conversation/SendMessageController` (JSON)
- [x] Nullable conversation — single page handles empty state and active conversation
- [x] Optimistic UI — user message added immediately, removed on error
- [x] Dynamic Echo — connects/disconnects based on conversationId
- [x] URL update — `history.replaceState` after first message creates conversation
- [x] CSS animation — input descends from center to bottom, title fades out, spacers collapse
- [x] Scrollable sidebar — conversations list with overflow, "New Conversation" button at bottom
- [x] `jsonb` meta column — migration updated from `text` to `jsonb` for PostgreSQL JSON queries

### 3.6 Task Filtering (TODO)
- [ ] Filter by status, priority, phase
- [ ] Search tasks

### Milestone (Phase 3): ✅ Kanban board with drag-and-drop. Task detail with dedicated Technical chat. Unified chat system with CSS animations, optimistic UI, and real-time updates. Auto-start creates action plan. Artifacts viewed in dedicated pages.

---

## Phase 4 — Agent Management & Polish (Partial ✅)

> Goal: Agent configuration UI, prompt tuning, and UX refinements.

### 4.1 Settings & Agent Management ✅
- [x] Settings page with tab link navigation (General / Agents)
- [x] `Settings/IndexController` — General placeholder
- [x] `Settings/Agents/IndexController` — lists agents + available tools
- [x] `Settings/Agents/StoreController` — creates custom agents (`AgentType::Custom`)
- [x] `Settings/Agents/UpdateController` — edits name, instructions, model, tools
- [x] `Settings/Agents/DestroyController` — soft deletes agents (scoped binding)
- [x] `Settings/Agents/ResetController` — resets default agent name + instructions from `.md`
- [x] Agent list page with clickable cards
- [x] Sheet lateral for create/edit form (Inertia `<Form>` pattern)
- [x] Tools toggle via Checkbox + hidden inputs
- [x] AlertDialog for delete confirmation
- [x] Settings enabled in sidebar navigation

### 4.2 Polish (TODO)
- [ ] Prompt tuning (agents too verbose, create artifacts without asking)
- [ ] React Compiler crash investigation (ConversationShow `useMemoCache` error)
- [ ] Token streaming (SSE or WebSocket, resolves Pusher payload too large)
- [ ] Tool call optimization (batch inserts, reduce AI round-trips)
- [ ] Chat messages top-to-bottom (replace `flex-col-reverse` — aligns with streaming)
- [x] Start Task → mark as "In Progress" automatically (`is_in_progress` flag on `task_statuses`)
- [ ] Task filtering (status, priority, phase, search)
- [ ] Agent card design improvements
- [ ] AI SDK Elements (Vercel) for chat UI polish

---

## Phase 5 — Custom Agents & Extensibility

> Goal: User-created agents and advanced features.

- [ ] Custom agent creation (name + AI-assisted instructions)
- [ ] Artifact cross-references
- [ ] Conversation search
- [ ] Project dashboard / overview
- [ ] Context window management strategy

---

## Implementation Steps Summary

### Steps 1-14.1 (unchanged)

| Steps | What | Tests |
|-------|------|-------|
| 1-7 | Data Layer | 86 |
| 8-11a | HTTP + Agent Layer | 45 |
| 11b-i/ii | Frontend | — |
| 11b-iii | Real-time | 16 |
| 12 | Architect Closure + Moderator | 13 |
| 13 | Async + Artifact Tools + Dynamic Agents | 51 |
| 14 | Agent Selection Poll | 7 |
| 14.1 | Bugfixes | 2 |

### Custom Task Statuses + Kanban Board (Step 15) ✅

| What | Tests |
|------|-------|
| `task_statuses` migration + `TaskStatus` model + scopes | 12 |
| `SeedProjectStatuses` action | 5 |
| Task model refactor (enum → relationship) + scopes (withStatus, closed, open) | updated |
| AI tools update (slug-based + dynamic schema + priority tryFrom) | updated |
| `TaskController` update endpoint (PATCH) | 7 |
| Kanban frontend (`@dnd-kit` DndContext + KanbanColumn + KanbanCard) | — |
| `TaskStatus` enum removed | — |

### Unified Chat + Task Chat (Step 16) ✅

| What | Tests |
|------|-------|
| `Conversation/IndexController` unified (index+show, nullable conversation) | updated |
| `Conversation/SendMessageController` (JSON response) | updated |
| `Task/SendMessageController` (JSON response, replaces ChatController) | updated |
| `StartTaskConversation` — hidden user message + action plan prompt | 1 |
| `ChatProvider` — `defaultSelectedAgentIds`, `initialProcessingAgents` | — |
| `ChatMessages` — orphan turns, standalone processing indicator | — |
| Unified conversation page — CSS animation, optimistic UI, dynamic Echo | — |
| Task show page — TaskEmpty vs TaskChat, floating Dialog | — |
| `meta` column migration `text` → `jsonb` | — |

### Settings & Agent Management (Step 17) ✅

| What | Tests |
|------|-------|
| `Settings/IndexController` — General page | 3 |
| `Settings/Agents/IndexController` — list agents + availableTools | 3 |
| `Settings/Agents/StoreController` — create custom agent | 2 |
| `Settings/Agents/UpdateController` — edit agent (scoped binding) | 3 |
| `Settings/Agents/DestroyController` — soft delete agent | 2 |
| `Settings/Agents/ResetController` — reset name + instructions from .md | 3 |
| Settings layout with tab links (General / Agents) | — |
| Agent list page with cards + Sheet form | — |

### Known Issues (deferred)
- **Pusher payload too large:** Long AI responses exceed Pusher's 10KB limit. Fix: token streaming (Phase 4).
- **React Compiler crash:** `useMemoCache` null error in ConversationShow. Fix: investigate in Phase 4.
- **Tool call round-trips:** PM creating 16 tasks = 16 AI round-trips (~120s). Fix: batch optimization (Phase 4).
- **Chat scroll direction:** `flex-col-reverse` starts messages at bottom. Fix: top-to-bottom with streaming (Phase 4).

**Total: 427 tests, all passing.**

---

## Technical Notes for Future Chats

When starting a new chat about this project, provide these files for context:
- `VISION.md` — Full project vision, architecture, data model, and code examples
- `ROADMAP.md` — This file. Phased execution plan with current status.

Key architectural patterns:
- All agents use `GenericAgent` — tools and model resolved from database
- Tools as dedicated PHP classes implementing `Tool` interface — access params via `$request['key']` (ArrayAccess)
- Dynamic tool resolution: `project_agents.tools` JSON → `"App\\Ai\\Tools\\{$name}"` instantiation
- Per-agent model override: `project_agents.model` → `$agent->prompt(model: ...)` at runtime
- Instructions stored in DB, defaults from `resources/instructions/*.md`
- `ProjectConversationStore` extends SDK's `DatabaseConversationStore`, bound in `boot()`
- Multi-agent chat: controller dispatches `ProcessChatMessage` job → Moderator → `ProcessAgentMessage` per agent
- ModeratorAgent: invisible router with confidence-based multi-agent routing
- Artifacts as structured data in separate tables (not conversation history)
- String columns in DB + PHP Enums for validation (exception: TaskStatus is a model/table)
- ULID for public URLs + `(string)` cast on `Str::ulid()`
- Contextual sidebar: 3-level drill-down via `sidebar` prop
- Chat uses `flex-col-reverse` for auto scroll-to-bottom
- Reverb broadcasting: `AgentMessageReceived` + `ConversationTitleUpdated` + `AgentsProcessing` events
- AI input sanitization: `($request['field'] ?? null) ?: null`, priority validated with `TaskPriority::tryFrom()`
- Task statuses: `task_statuses` table per project, AI tools use slug, `restrictOnDelete` FK, `SeedProjectStatuses` in `CreateProjectService`
- Kanban: `@dnd-kit` with `pointerWithin` collision, `PointerSensor` distance:8, optimistic state + PATCH persist
