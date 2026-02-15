# Project Vision: AI-Powered Project Manager

> **Status:** Early planning / Ideation
> **Type:** Open Source — Serious side project
> **Target Audience:** Freelancers & Solopreneurs
> **Stack:** Laravel 12 + Inertia.js + React | Laravel AI SDK
> **AI Providers:** Multi-provider (Anthropic, OpenAI, Gemini, etc.)

---

## The Problem

Developers and freelancers hate traditional project management software. It treats management as bureaucracy — you have to feed the system for it to work. It's work to generate work.

The idea was born from a real pain: using Claude's chat as a project manager worked surprisingly well — conversational, natural, no friction. Until the chat hit its limits and died. The conversation was lost, and with it, all the context, decisions, and structure that had been built.

**Core insight:** The conversational approach works. What's missing is persistence, structure, and scoped context.

---

## The Vision

**"The user talks to the AI, and project management happens as a consequence."**

Instead of dashboards, kanbans, and forms, the primary interface is a **chat**. The user describes their project, discusses decisions, defines rules — and the AI organizes everything behind the scenes. Traditional views (task lists, roadmaps, boards) exist as **read-only visualizations** of what the AI has already organized, not as the primary input method.

---

## Architecture: The Meeting Room

### Hierarchy

```
📁 Project
├── 💬 Conversations (meeting room)
│   ├── "Defining stack and approach"
│   ├── "Payment split rules"
│   └── "Replanning phase 2"
├── 📚 Artifacts (generated from conversations)
│   ├── Decisions: "AdonisJS", "Non-custodial", ...
│   ├── Business Rules: "Immediate split", "0.5% fee", ...
│   └── Roadmap: Phases, milestones
└── 📝 Tasks
    ├── HD Wallet Derivation → 💬 technical chat
    └── Transaction Scanner → 💬 technical chat
```

### Model: `Project > Conversation > Agent`

The user opens a conversation within a project (like a meeting room) and talks naturally. They don't need to pick an agent — a **Moderator** agent receives the message, classifies it, and delegates to the appropriate specialized agent. The response comes back seamlessly.

Example flow:

```
User: "Should the split happen on-chain or off-chain?"

Moderator (internal reasoning):
→ Topics: business rule (how split works) + architecture (on-chain vs off-chain)
→ Relevant roles: Business Analyst + Architect
→ Responds combining both perspectives

User sees: A coherent answer covering both the business rationale
           and technical implementation.
```

---

## Agents

### Pre-defined Agents

The app ships with well-crafted agents that cover the most common needs:

#### 1. Architect

- **Purpose:** Technical decisions, stack choices, patterns, tradeoffs
- **Reads:** Business Rules, previous Decisions
- **Writes:** Decisions/ADRs
- **Personality:** Senior dev you call for advice. Doesn't manage, doesn't create tasks. Discusses, questions, suggests.
- **Example:** *"Should I use AdonisJS or NestJS? Monorepo or separate projects?"*

#### 2. Project Manager (PM)

- **Purpose:** Organize execution, create tasks, estimate effort, track progress
- **Reads:** Decisions, Business Rules, Tasks/Roadmap
- **Writes:** Tasks/Roadmap, updates status and estimates
- **Personality:** Organized, deadline-aware, proactive about risks.
- **Example:** *"Create the roadmap for Alfred"*, *"The client added scope, recalculate impact"*

#### 3. Business Analyst

- **Purpose:** Define domain rules, requirements, acceptance criteria
- **Reads:** Decisions, Business Rules
- **Writes:** Business Rules, suggests adjustments to Decisions
- **Personality:** Asks the right questions about the domain. Ensures rules are clear and complete.
- **Example:** *"The gateway is non-custodial, what does that mean for refunds?"*

#### 4. Technical (Code Buddy)

- **Purpose:** Lives in the task chat. Helps with implementation, code review, debugging.
- **Reads:** Decisions, Business Rules, specific Task, Implementation Notes
- **Writes:** Implementation Notes, updates task status
- **Personality:** Practical, focused on the code at hand. Knows the project context but stays operational.
- **Example:** *"How do I implement HD Wallet derivation?"*, *"Review this endpoint"*

### The Moderator (Invisible / System-level)

The Moderator is the orchestrator — the "brain" of the meeting room. It is **invisible to the user**: it never appears in the agent list, cannot be edited or deleted, and the user doesn't need to know it exists. It is pure infrastructure.

It is **not** a single agent that role-plays all personas. Instead, it:

1. Receives the user's message
2. Classifies which agent(s) are relevant
3. Delegates to the appropriate agent(s)
4. Returns the response to the user

This means each specialized agent is a **separate agent class** with its own instructions, tools, and context. The Moderator routes, not role-plays.

### Agents are Per-Project

When a project is created, the system automatically instantiates the pre-defined agents **for that project**. Each project has its own set of agents with their own instructions, which can be customized per project.

This means:

- The Architect of a crypto project can emphasize security and gas optimization
- The Architect of an e-commerce project can emphasize scalability and UX
- The PM of a tight-deadline project can be more aggressive about time estimates
- Each agent's instructions are **editable by the user** per project

Benefits:

- Agents are more intelligent with fewer tokens (context is baked into instructions, not loaded every message)
- Users can fine-tune agent behavior for specific project needs
- Custom agents live naturally alongside pre-defined ones

### Custom Agents

Users can create additional custom agents for specific needs (DBA, Security Reviewer, UX Consultant, etc.). The system provides:

- A simple form: name + description of what the role does
- An internal agent that helps generate optimized instructions from the description
- The custom agent is stored in the database and instantiated dynamically via Anonymous Agents
- Custom agents have the same capabilities as pre-defined ones (tools, artifact access)

---

## Artifacts: The Knowledge Layer

Artifacts are **structured data generated from conversations**. They are the bridge between agents — instead of sharing full conversation histories (which grow infinitely), agents read and write discrete, typed artifacts.

### Why Artifacts?

The problem with the "chat that died": everything lived **in the conversation**. Decisions, tasks, context, code — all in a linear history that only grew. With artifacts, conversations are **ephemeral** (kept for logs), but real knowledge lives in structured artifacts.

Each agent reads artifacts for context and writes artifacts as output. Conversations can even be deleted without losing project knowledge.

### Artifact Types (separate tables)

#### 1. Decisions (ADRs - Architecture Decision Records)

- **What:** The "why" behind choices
- **Written by:** Architect (primarily), Business Analyst
- **Schema:**
    - `title` — What was decided
    - `choice` — The chosen option
    - `reasoning` — Why this was chosen
    - `alternatives_considered` — What was discarded
    - `context` — What prompted this decision
    - `status` — Active, Superseded, Deprecated
- **Example:** *"Use AdonisJS 6 because better DX for APIs, native TypeScript, good ORM. Alternatives: NestJS, Fastify, Python/FastAPI"*

#### 2. Business Rules

- **What:** Domain truths that rarely change
- **Written by:** Business Analyst (primarily), Architect
- **Schema:**
    - `title` — Short description
    - `description` — Full rule definition
    - `category` — Grouping (Payments, Security, etc.)
    - `status` — Active, Deprecated
- **Example:** *"Non-custodial gateway: never holds funds. Split is immediate after payment confirmation."*

#### 3. Tasks / Roadmap

- **What:** The "when" and "how much"
- **Written by:** PM (primarily)
- **Schema:**
    - `title` — Task name
    - `description` — What needs to be done
    - `phase` / `milestone` — Grouping
    - `status` — Backlog, In Progress, Done, Blocked
    - `priority` — High, Medium, Low
    - `estimate` — Effort estimate
    - `dependencies` — Other tasks
- **Example:** *"Implement HD Wallet Derivation — Phase 1, High priority, ~8h estimate"*

#### 4. Implementation Notes

- **What:** The "how it was done"
- **Written by:** Technical (Code Buddy) from task chats
- **Schema:**
    - `task_id` — Which task this relates to
    - `title` — Short summary
    - `content` — Detailed notes (approaches tried, problems found, solutions applied)
    - `code_snippets` — Relevant code (optional)
- **Example:** *"Nonce management resolved with sequential queue to avoid race conditions in concurrent transfers"*

### Agent × Artifact Access Matrix

```
                    Reads                      Writes

Architect     → Business Rules              → Decisions/ADRs
              → Previous Decisions

Analyst       → Decisions                   → Business Rules
              → Business Rules

PM            → Decisions                   → Tasks/Roadmap
              → Business Rules
              → Tasks/Roadmap

Technical     → Decisions                   → Implementation Notes
(task chat)   → Business Rules              → Task status updates
              → Specific Task
              → Implementation Notes

Moderator     → All (read-only)             → Delegates to appropriate agent
```

---

## UX Flow

### First-time Experience

1. User creates a project (simple form: name, description, optional tags)
2. Lands on the project page — centered chat interface
3. Starts talking: *"This is a crypto payment gateway, I need to decide on the stack..."*
4. Moderator routes to Architect, conversation begins naturally

### Day-to-day

1. User opens project
2. Sees: chat area (primary) + side panel with artifacts/tasks (secondary)
3. Can start a new conversation or continue an existing one
4. Can click on a task to open its detail view with dedicated technical chat
5. Can browse artifacts (decisions, rules) as reference

### Task Detail View

- Task metadata: title, status, priority, estimate, phase
- Implementation Notes (accumulated from past chats)
- Dedicated chat (Technical agent, scoped to this task)
- The chat here is **on-demand** — not every task needs a conversation

---

## Data Model

### Core Tables

```
projects
├── id
├── name
├── description
├── settings (json — default provider, etc.)
├── timestamps

project_agents
├── id
├── project_id (FK → projects)
├── type (enum: architect | pm | technical | custom)
├── name ("Architect", "PM", or custom name)
├── instructions (text — editable per project)
├── is_system (bool — true = Moderator, invisible to user)
├── is_default (bool — true = came pre-defined, can't be deleted)
├── settings (json — provider preference, temperature, model, etc.)
├── timestamps

conversations
├── id
├── project_id (FK → projects)
├── title (nullable — auto-generated or user-defined)
├── status (enum: active | archived)
├── timestamps

conversation_messages
├── id
├── conversation_id (FK → conversations)
├── project_agent_id (FK → project_agents, nullable for user messages)
├── role (enum: user | assistant)
├── content (text)
├── timestamps
```

**Key detail:** `project_agent_id` is on the **message**, not the conversation. In a single conversation (meeting room), different agents can respond to different messages — the Moderator delegates to whoever is relevant.

### Artifact Tables (separate, per type)

```
decisions
├── id
├── project_id (FK → projects)
├── conversation_message_id (FK — which message generated this, nullable)
├── title, choice, reasoning
├── alternatives_considered (json)
├── context (text)
├── status (enum: active | superseded | deprecated)
├── timestamps

business_rules
├── id
├── project_id (FK → projects)
├── conversation_message_id (FK, nullable)
├── title, description
├── category (string — Payments, Security, etc.)
├── status (enum: active | deprecated)
├── timestamps

tasks
├── id
├── project_id (FK → projects)
├── conversation_message_id (FK, nullable)
├── title, description
├── phase, milestone (nullable)
├── status (enum: backlog | in_progress | done | blocked)
├── priority (enum: high | medium | low)
├── estimate (nullable — hours or story points)
├── sort_order (int)
├── parent_task_id (FK, nullable — for subtasks)
├── timestamps

implementation_notes
├── id
├── task_id (FK → tasks)
├── conversation_message_id (FK, nullable)
├── title, content (text)
├── code_snippets (json, nullable)
├── timestamps
```

---

## Technical Implementation (Laravel AI SDK)

### Agents as Classes (Per-Project)

Pre-defined agents use a base class pattern. The instructions come from the database (editable) combined with dynamic artifact context:

```php
// App\Ai\Agents\ArchitectAgent
class ArchitectAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public Project $project,
        public ProjectAgent $agentConfig,
    ) {}

    public function instructions(): string
    {
        $decisions = $this->project->decisions()->active()->get();
        $rules = $this->project->businessRules()->active()->get();

        // Combines user-editable instructions with dynamic context
        return view('ai.instructions.architect', [
            'customInstructions' => $this->agentConfig->instructions,
            'project' => $this->project,
            'decisions' => $decisions,
            'rules' => $rules,
        ])->render();
    }

    public function tools(): iterable
    {
        return [
            new CreateDecision($this->project),
            new UpdateDecision($this->project),
            new ListDecisions($this->project),
            new ListBusinessRules($this->project),
        ];
    }
}
```

### Moderator Agent (System-level, Invisible)

```php
// App\Ai\Agents\ModeratorAgent
class ModeratorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public Project $project) {}

    public function instructions(): string
    {
        // Loads all visible agents for this project
        $agents = $this->project->agents()
            ->where('is_system', false)
            ->get();

        return view('ai.instructions.moderator', [
            'project' => $this->project,
            'agents' => $agents,
        ])->render();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'target_agent' => $schema->string()->required(),
            'reasoning' => $schema->string()->required(),
        ];
    }
}
```

### Custom Agents (Dynamic, Per-Project)

```php
use function Laravel\Ai\{agent};

$agentConfig = ProjectAgent::find($agentId); // From database

$response = agent(
    instructions: $agentConfig->instructions,
    tools: $agentConfig->resolveTools($project),
)->prompt($userMessage);
```

### Project Creation Flow

When a project is created, the system seeds the default agents:

```php
// App\Actions\CreateProject
class CreateProject
{
    public function handle(array $data): Project
    {
        $project = Project::create($data);

        // Invisible system agent
        $project->agents()->create([
            'type' => 'moderator',
            'name' => 'Moderator',
            'instructions' => '', // Uses system-level instructions
            'is_system' => true,
            'is_default' => true,
        ]);

        // Visible pre-defined agents
        collect(['architect', 'pm', 'technical'])->each(
            fn (string $type) => $project->agents()->create([
                'type' => $type,
                'name' => config("ai.default_agents.{$type}.name"),
                'instructions' => config("ai.default_agents.{$type}.instructions"),
                'is_system' => false,
                'is_default' => true,
            ])
        );

        return $project;
    }
}
```

### Tools (Artifact Management)

```php
// App\Ai\Tools\CreateDecision
class CreateDecision implements Tool
{
    public function __construct(public Project $project) {}

    public function description(): string
    {
        return 'Record an architecture decision (ADR) for the project.';
    }

    public function handle(Request $request): string
    {
        $decision = $this->project->decisions()->create([
            'title' => $request['title'],
            'choice' => $request['choice'],
            'reasoning' => $request['reasoning'],
            'alternatives_considered' => $request['alternatives'],
            'status' => 'active',
        ]);

        return "Decision recorded: {$decision->title}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'choice' => $schema->string()->required(),
            'reasoning' => $schema->string()->required(),
            'alternatives' => $schema->array()->items($schema->string()),
        ];
    }
}
```

---

## Scope: Phase 1 (MVP)

### In Scope

- Project CRUD with automatic agent seeding (Moderator + Architect + PM + Technical)
- Meeting room chat with invisible Moderator routing to specialized agents
- Per-project agents with editable instructions
- Artifact system: Decisions, Business Rules, Tasks, Implementation Notes
- Task list/board view (read from artifacts)
- Task detail view with dedicated technical chat
- Multi-provider AI support (user provides their own API key)
- Conversation persistence (RemembersConversations)

### Out of Scope (Future Phases)

- Time tracking
- Financial management (invoicing, payments)
- Custom agent creation (user-defined roles)
- Business Analyst agent (can be added after MVP)
- Team/collaboration features
- Integrations (GitHub, Slack, etc.)

---

## Open Questions

1. **Conversation lifecycle:** When should a conversation be "closed"? Auto-archive after X days of inactivity? User manually closes?
2. **Artifact conflicts:** What if the PM creates a task that contradicts an Architect decision? Should there be validation?
3. **Context window management:** How much artifact context to load into each agent's instructions? Need a strategy to avoid token limit issues.
4. **Chat in task vs. conversation in project:** Should the technical chat in a task be accessible from the project conversation list, or completely separate?
5. **Default instructions versioning:** When the app updates default agent instructions (e.g. better prompts), should existing projects get the update or keep their customized version?
6. **Project name:** The app needs a name!
