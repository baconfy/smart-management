# Roadmap: AI-Powered Project Manager

> **Reference:** See [VISION.md](./VISION.md) for full project vision, architecture, and data model.
> **Last updated:** 2026-02-18 (Steps 1-11b-iii complete, 154 tests)

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
18. **ULID for URLs:** Projects and Tasks use `ulid` column for route model binding. `getRouteKeyName()` returns `'ulid'`, `booted()` auto-generates on creation. Conversations use ULID as primary key directly.
19. **SDK Request Array Access:** Tools access parameters via `ArrayAccess` (`$request['key']`), not `get()` or `input()`. SDK `Request` implements `ArrayAccess`. Optional fields use null coalescing: `$request['key'] ?? null`.
20. **Agent Tools via Constructor DI:** Tools receive `Project` in constructor, `ArchitectAgent` instantiates them in `tools()` method with `$this->project()`.
21. **Multi-agent chat:** `agent_ids` array, not single `agent_id`. User message stored once, each agent responds independently. Controller manages conversation lifecycle manually (not SDK middleware) to avoid duplicate user messages.
22. **Agent resolution:** `ArchitectAgent` for architect type, `GenericAgent` for all others. Resolved via `match` in controller. New agent classes added as `AgentType` cases grow.
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
33. **Derived loading state:** `waitingForResponse` derived from `messages[last].role === 'user'` instead of manual state. No useState/useEffect needed — auto-resolves when assistant message arrives via Echo or Inertia.
34. **Message dedup by ID:** Echo listener checks `prev.some(m => m.id === e.message.id)` before appending. Prevents duplicates when Inertia page reload and WebSocket both deliver the same message.

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
- [x] Migration + Model + Enums: `tasks` table — `TaskStatus`/`TaskPriority` enums, `withStatus()` scope, subtasks via `parent_task_id` (19 tests)
- [x] Migration + Model: `implementation_notes` table — `code_snippets` JSON (19 tests)

### 1.4 Project CRUD ✅
- [x] `CreateProjectService` + atomic actions (`CreateProject`, `AddProjectMember`, `SeedProjectAgents`)
- [x] Instruction `.md` files in `resources/instructions/`
- [x] List projects (`GET /projects` with membership scoping)
- [x] Project detail page (`GET /projects/{ulid}` with `ProjectPolicy` authorization)
- [x] `StoreProjectRequest` form validation
- [x] ULID route model binding (`getRouteKeyName`)

### 1.5 First Agent: Architect ✅
- [x] `ArchitectAgent` class implementing `Agent, Conversational, HasTools` (8 tests)
- [x] `GenericAgent` fallback class for agent types without dedicated class
- [x] `ReadsConversationHistory` concern — read history without triggering SDK middleware
- [x] Instructions loaded from `ProjectAgent` model + project context appended
- [x] Conversation persistence via `RemembersConversations` + custom store

### 1.6 First Artifact: Decisions (Partial) ✅
- [x] `CreateDecision` tool — creates decision record (10 tests)
- [x] `ListDecisions` tool — lists project decisions with optional status filter (10 tests)
- [ ] `UpdateDecision` tool
- [ ] Decisions list view in project UI

### 1.7 Chat System ✅
- [x] `ChatController` with multi-agent support (`agent_ids` array) (12 tests)
- [x] `StoreChatMessageRequest` validation with `prepareForValidation` cleanup
- [x] Raw store methods (`storeRawUserMessage`, `storeRawAssistantMessage`)
- [x] Contextual sidebar — 3-level drill-down (Projects → Project Nav → Conversations)
- [x] `ConversationsNavPanel` with cursor-paginated conversation list
- [x] Chat UI — ChatInput (textarea, agent toggles, file attach, hasContent state)
- [x] New conversation / continue conversation flow
- [x] ReactMarkdown rendering with `@tailwindcss/typography` prose
- [x] Scroll-to-bottom via `flex-col-reverse`
- [x] ConversationController with `index` and `show` methods

### Milestone: ✅ User can create a project, chat with agents, see responses with markdown. Conversations persist and are listed in sidebar.

---

## Phase 1.5 — Real-time Chat (Step 11b-iii) ✅

> Goal: Replace synchronous AI calls with async Jobs + Reverb broadcasting. Instant redirect, parallel agent responses, real-time message streaming.

### Architecture
```
POST /projects/{project}/chat
  → Save user message
  → Dispatch ProcessAgentMessage (1 job per agent, parallel via Redis)
  → Dispatch GenerateConversationTitle (if new conversation)
  → Redirect to show (IMMEDIATE)

Job ProcessAgentMessage:
  → Call AI agent (no timeout issues)
  → Save response to DB
  → Broadcast AgentMessageReceived on conversation.{id}

Job GenerateConversationTitle:
  → Call AI to summarize first message
  → Update conversation title
  → Broadcast ConversationTitleUpdated on conversation.{id}

Frontend show.tsx:
  → Echo.private(`conversation.${id}`)
  → Listen AgentMessageReceived → append message reactively
  → Listen ConversationTitleUpdated → update title + sidebar
```

### Tasks
- [x] `AgentMessageReceived` Event (broadcastable via PrivateChannel)
- [x] `ConversationTitleUpdated` Event (broadcastable via PrivateChannel)
- [x] `ProcessAgentMessage` Job (per agent, parallel dispatch, calls AI + saves + broadcasts)
- [x] `GenerateConversationTitle` Job (async title, placeholder for AI generation)
- [x] Channel auth: `conversation.{conversationId}` (user ownership check)
- [x] ChatController refactor: invokable, dispatch jobs + immediate redirect
- [x] Frontend Echo listeners: append messages (dedup by ID) + update title
- [x] Thinking bubbles (3-dot bounce animation while waiting)
- [x] Derived loading state (`waitingForResponse` from last message role)
- [x] Auto-disable input while waiting for response
- [x] Docker Reverb networking validated (backend:9001, frontend:9012)
- [x] E2E tested: multi-agent parallel responses via Horizon + Reverb

### Pending Polish
- [ ] Loading indicator per agent (multi-agent aware)
- [ ] Token streaming (SSE or WebSocket, show tokens as they arrive)
- [ ] AI-generated title (replace `Str::limit` with cheap model call)

### Milestone: ✅ Instant redirect after sending. Agent responses appear in real-time via WebSocket. Multi-agent parallel processing. Thinking bubbles while waiting.

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

### 2.4 Agent Management UI
- [ ] View project agents list
- [ ] Edit instructions per agent
- [ ] "Reset to default" button (re-copies from `.md`)
- [ ] Agent settings (provider preference, temperature)

### Milestone: Full meeting room experience. User chats naturally, Moderator routes, all artifact types are generated.

---

## Phase 3 — Task System

> Goal: Tasks are first-class citizens with their own views and dedicated technical chat.

- [ ] Task list view (filterable by status, priority, phase)
- [ ] Task board view (kanban-style by status)
- [ ] Task detail view with dedicated technical chat
- [ ] Implementation Notes generated from task conversations
- [ ] Manual task status update + ordering + subtasks

### Milestone: Complete task lifecycle — created by PM agent, discussed in technical chat, notes accumulated, status tracked.

---

## Phase 4 — Polish & Custom Agents

> Goal: Refined UX and extensibility via user-created agents.

- [ ] Custom agent creation (name + AI-assisted instructions)
- [ ] Artifact cross-references
- [ ] Conversation search
- [ ] Project dashboard / overview
- [ ] Base UI nativeButton warning fix
- [ ] Context window management strategy

### Milestone: Extensible system. Users can create specialized agents tailored to their workflow.

---

## Future Phases (Post-MVP)

- **Time Tracking:** Agent can log time from conversation
- **Financial Management:** Budget tracking, invoice generation
- **Integrations:** GitHub, Slack
- **Team Features:** Multiple users per project (infrastructure ready via project_members)
- **Templates:** Pre-configured project templates
- **Analytics:** AI-generated project health reports

---

## Implementation Steps Summary

### Data Layer (Steps 1-7) ✅ — 86 tests

| Step | What | Tests |
|------|------|-------|
| 1 | `projects` + `project_members` | 12 |
| 2 | `project_agents` + `AgentType` enum | 10 |
| 3 | Modified SDK migration | — |
| 4 | `ProjectConversationStore` | 6 |
| 5 | `decisions` + `business_rules` + enums | 17+17 |
| 6 | `tasks` + `implementation_notes` + enums | 19 |
| 7 | `CreateProjectService` + atomic actions | 9 |

### HTTP + Agent Layer (Steps 8-11a) ✅ — 45 tests

| Step | What | Tests |
|------|------|-------|
| 8 | Project routes + controller + policy | 15 |
| 9 | `ArchitectAgent` + `Promptable`/`RemembersConversations` | 8 |
| 10 | `CreateDecision` + `ListDecisions` tools | 10 |
| 11a | `ChatController` multi-agent + `GenericAgent` | 12 |

### Frontend (Steps 11b-i/ii) ✅

| Step | What |
|------|------|
| 11b-i | Contextual sidebar (ProjectsPanel → ProjectNavPanel → ConversationsNavPanel) |
| 11b-ii | Chat UI (ChatInput, messages, ReactMarkdown, scroll, conversation CRUD) |

### Real-time (Step 11b-iii) ✅ — 16 tests

| Step | What | Tests |
|------|------|-------|
| 11b-iii | Events (AgentMessageReceived, ConversationTitleUpdated), Jobs (ProcessAgentMessage, GenerateConversationTitle), Channel auth, ChatController refactor (invokable + dispatch), Frontend Echo listeners, thinking bubbles | 16 |

**Total: 154 tests, all passing.**

---

## Technical Notes for Future Chats

When starting a new chat about this project, provide these files for context:
- `VISION.md` — Full project vision, architecture, data model, and code examples
- `ROADMAP.md` — This file. Phased execution plan with current status.

Key architectural patterns:
- Agents as dedicated PHP classes with `Promptable` trait
- Tools as dedicated PHP classes implementing `Tool` interface — access params via `$request['key']` (ArrayAccess)
- Instructions stored in DB, defaults from `resources/instructions/*.md`
- `ProjectConversationStore` extends SDK's `DatabaseConversationStore`, bound in `boot()`
- Multi-agent chat: controller dispatches `ProcessAgentMessage` job per agent, stores user message once
- `ReadsConversationHistory` concern for reading history without SDK middleware
- `GenericAgent` fallback for agent types without a dedicated class
- Invisible Moderator with confidence-based routing (Phase 2)
- Artifacts as structured data in separate tables (not conversation history)
- String columns in DB + PHP Enums for validation
- ULID for public URLs + `(string)` cast on `Str::ulid()`
- Model casts: `usage`, `meta`, `tool_calls`, `tool_results` as `array`
- Contextual sidebar: 3-level drill-down via `sidebar` prop
- Chat uses `flex-col-reverse` for auto scroll-to-bottom
- `SidebarProvider` uses `h-svh` for scroll containment
- Cursor pagination for conversation lists
- ReactMarkdown + `@tailwindcss/typography` for message rendering
- `CursorPaginated<T>` generic type for paginated responses
- `prepareForValidation` on FormRequest to clean empty `agent_ids`
- Reverb broadcasting: `AgentMessageReceived` + `ConversationTitleUpdated` events on `PrivateChannel`
- Docker Reverb: backend `reverb:9001`, frontend `localhost:9012`, separate VITE_ env vars
- Echo listeners with dedup by message ID to prevent Inertia/WebSocket duplicates
- Derived `waitingForResponse` from last message role (no extra state)
- Agent fake testing: `ArchitectAgent::fake(['response'])` + `assertPrompted(fn () => true)`
- ChatController invokable with `resolveConversation()` + `dispatchAgentJobs()` private methods
