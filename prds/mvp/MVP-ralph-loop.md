# Personal Command Center MVP - Ralph Loop Implementation

## Project Overview

Build a personal command center that ingests unstructured input (primarily email), uses AI to extract meaning (events, tasks, reminders), shows proposed actions as suggestions, and lets users approve/reject them. The system prioritizes transparency and user control - no auto-creation, every AI action is visible.

**Tech Stack:** Laravel 12, React 19, Inertia.js v2, Tailwind CSS v4, Fortify, Horizon, SQLite

**Current State:** Authentication via Fortify is working. User model exists with 2FA support. Job infrastructure is in place. Need to build: Workspaces, Inbox Items, Extractions, Suggestions, Inbound Email, Frontend pages.

---

## Required Skills

**IMPORTANT:** Before implementing frontend phases (6-9), read and apply these skills:

| Skill | Path | Use For |
|-------|------|---------|
| `laravel-inertia-react` | `.agents/skills/laravel-inertia-react/SKILL.md` | Page structure, forms, navigation, components, hooks |
| `frontend-design` | `.agents/skills/frontend-design/SKILL.md` | Design thinking, aesthetic direction, typography, motion |
| `vercel-react-best-practices` | `.agents/skills/vercel-react-best-practices/SKILL.md` | React performance patterns, bundle optimization |
| `webapp-testing` | `.agents/skills/webapp-testing/SKILL.md` | Playwright browser testing |

**Skill Loading Strategy:**
1. Read `laravel-inertia-react` SKILL.md before Phase 6 - follow its patterns exactly
2. Read `frontend-design` SKILL.md before Phase 6 - establish aesthetic direction
3. Reference `vercel-react-best-practices` during React component implementation
4. Reference `webapp-testing` when writing browser tests

---

## Completion Criteria

This implementation is complete when ALL of the following are true:

1. **All tests pass**: `php artisan test --compact` exits with 0
2. **Lint passes**: `vendor/bin/pint --test` reports no errors
3. **Build succeeds**: `npm run build` completes without errors
4. **Core flow works**:
   - User can register and workspace is auto-created
   - User can create manual inbox items
   - User can trigger extraction on inbox items
   - Suggestions are created from extractions
   - User can accept/reject suggestions
5. **All routes exist**: `php artisan route:list` shows all required API and web routes
6. **Frontend is distinctive**: UI follows `frontend-design` skill principles

Only output `<promise>DONE</promise>` when ALL criteria are met.

---

## Phase 1: Workspace System

### Objectives
- Create workspaces table and model
- Create workspace_user pivot table for membership
- Implement automatic workspace creation on user registration
- Add workspace scoping to User model

### Implementation Steps

1. Create Workspace model with migration and factory:
   ```bash
   php artisan make:model Workspace -mf --no-interaction
   ```

2. Update the workspaces migration with fields:
   - `id` (primary key)
   - `name` (string, required)
   - `inbound_email_token` (string, unique, for email forwarding)
   - `timestamps`

3. Create pivot migration for workspace_user:
   ```bash
   php artisan make:migration create_workspace_user_table --no-interaction
   ```
   Fields:
   - `workspace_id` (foreign key)
   - `user_id` (foreign key)
   - `role` (string: 'owner', 'member')
   - `timestamps`
   - Primary key on (workspace_id, user_id)

4. Update Workspace model:
   - Add `$fillable`: name, inbound_email_token
   - Add `users()` belongsToMany relationship with pivot role
   - Add `owner()` method to get owner user
   - Add `generateInboundEmailToken()` method using `Str::random(32)`

5. Update User model:
   - Add `workspaces()` belongsToMany relationship with pivot role
   - Add `currentWorkspace()` method (returns first owned workspace for MVP)
   - Add `ownedWorkspaces()` filtered relationship

6. Create RegisteredUserObserver or use Fortify's CreateNewUser action:
   - After user creation, create default workspace named "{user.name}'s Workspace"
   - Attach user as owner
   - Generate inbound email token

7. Update the `app/Actions/Fortify/CreateNewUser.php` action to create workspace atomically with user creation using DB::transaction()

8. Create WorkspaceFactory with proper states

9. Write tests in `tests/Feature/WorkspaceTest.php`:
   - Test workspace is created on registration
   - Test user is attached as owner
   - Test inbound email token is unique
   - Test user can access their workspace

### Verification

```bash
php artisan migrate --force
php artisan test --compact --filter=WorkspaceTest
vendor/bin/pint --dirty
```

### Success Criteria
- [ ] Migrations run without errors
- [ ] New user registration creates workspace automatically
- [ ] User is attached as workspace owner
- [ ] Inbound email token is generated and unique
- [ ] All WorkspaceTest tests pass

---

## Phase 2: Inbox Items Backend

### Objectives
- Create inbox_items table and model
- Implement InboxItem API endpoints
- Add workspace scoping

### Implementation Steps

1. Create InboxItem model with migration, factory, and controller:
   ```bash
   php artisan make:model InboxItem -mfc --api --no-interaction
   ```

2. Update inbox_items migration with fields:
   - `id` (primary key)
   - `workspace_id` (foreign key to workspaces)
   - `source` (string: 'email', 'manual', 'share')
   - `raw_subject` (string, nullable)
   - `raw_content` (text, required)
   - `received_at` (datetime)
   - `status` (string: 'new', 'parsed', 'archived', default: 'new')
   - `timestamps`
   - Index on (workspace_id, status)
   - Index on (workspace_id, received_at)

3. Create InboxItemStatus enum in `app/Enums/InboxItemStatus.php`:
   - Cases: New, Parsed, Archived
   - Implement string values

4. Create InboxItemSource enum in `app/Enums/InboxItemSource.php`:
   - Cases: Email, Manual, Share

5. Update InboxItem model:
   - Add `$fillable` array
   - Cast status and source to enums
   - Cast received_at to datetime
   - Add `workspace()` belongsTo relationship
   - Add `extractions()` hasMany relationship (for later)
   - Add `suggestions()` hasManyThrough relationship (for later)
   - Add scope `scopeForWorkspace($query, $workspaceId)`
   - Add `markAsParsed()` method
   - Add `archive()` method

6. Create InboxItemPolicy:
   ```bash
   php artisan make:policy InboxItemPolicy --model=InboxItem --no-interaction
   ```
   - `viewAny`: user belongs to workspace
   - `view`: item belongs to user's workspace
   - `create`: user belongs to workspace
   - `update`: item belongs to user's workspace (status only)
   - `delete`: return false (items never deleted in MVP)

7. Update InboxItemController with:
   - `index()`: list items for current workspace, paginated, filterable by status
   - `store()`: create manual inbox item (source='manual', received_at=now)
   - `show()`: return single item with extractions and suggestions
   - `update()`: update status only (use Form Request)

8. Create Form Requests:
   - `StoreInboxItemRequest`: validates raw_subject (nullable string), raw_content (required string)
   - `UpdateInboxItemRequest`: validates status (required, in enum values)

9. Create InboxItemResource for API responses

10. Register API routes in `routes/api.php`:
    ```php
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('inbox-items', InboxItemController::class)->except(['destroy']);
    });
    ```

11. Write tests in `tests/Feature/InboxItemTest.php`:
    - Test listing inbox items (only own workspace)
    - Test creating manual inbox item
    - Test viewing inbox item
    - Test updating status
    - Test cannot delete
    - Test cannot access other workspace's items

### Verification

```bash
php artisan migrate --force
php artisan route:list | grep inbox
php artisan test --compact --filter=InboxItemTest
vendor/bin/pint --dirty
```

### Success Criteria
- [ ] Migrations run without errors
- [ ] All 4 API routes exist (index, store, show, update)
- [ ] Creating inbox item works with proper workspace scoping
- [ ] Status update works
- [ ] Cannot access other workspace's items
- [ ] All InboxItemTest tests pass

---

## Phase 3: Suggestions Backend

### Objectives
- Create suggestions table and model
- Implement suggestions API (list, accept, reject)
- Prepare for extraction integration

### Implementation Steps

1. Create Suggestion model with migration, factory, and controller:
   ```bash
   php artisan make:model Suggestion -mfc --api --no-interaction
   ```

2. Create extractions migration first (suggestions depend on it):
   ```bash
   php artisan make:migration create_extractions_table --no-interaction
   ```
   Fields:
   - `id` (primary key)
   - `inbox_item_id` (foreign key)
   - `model_version` (string)
   - `prompt_version` (string)
   - `raw_response` (json)
   - `timestamps`

3. Update suggestions migration with fields:
   - `id` (primary key)
   - `extraction_id` (foreign key to extractions)
   - `type` (string: 'event', 'reminder', 'task')
   - `payload` (json - contains the extracted data)
   - `status` (string: 'proposed', 'accepted', 'rejected', default: 'proposed')
   - `timestamps`
   - Index on (extraction_id)

4. Create Extraction model in `app/Models/Extraction.php`:
   - Add relationships: `inboxItem()`, `suggestions()`
   - Cast raw_response to array

5. Create SuggestionType enum:
   - Cases: Event, Reminder, Task

6. Create SuggestionStatus enum:
   - Cases: Proposed, Accepted, Rejected

7. Update Suggestion model:
   - Add `$fillable` array
   - Cast type and status to enums
   - Cast payload to array
   - Add `extraction()` belongsTo relationship
   - Add `inboxItem()` accessor through extraction
   - Add `scopeProposed($query)` scope
   - Add `scopeForWorkspace($query, $workspaceId)` through joins
   - Add `accept()` method - sets status to accepted
   - Add `reject()` method - sets status to rejected

8. Create SuggestionPolicy:
   - `viewAny`: user has workspace
   - `view`: suggestion belongs to user's workspace (via extraction -> inbox_item)
   - `accept`: suggestion is proposed and belongs to workspace
   - `reject`: suggestion is proposed and belongs to workspace

9. Update SuggestionController with:
   - `index()`: list suggestions for workspace, filterable by status and type
   - `show()`: return single suggestion with relationships
   - `accept()`: POST /suggestions/{id}/accept - change status to accepted
   - `reject()`: POST /suggestions/{id}/reject - change status to rejected

10. Create SuggestionResource for API responses

11. Register API routes:
    ```php
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('suggestions', [SuggestionController::class, 'index']);
        Route::get('suggestions/{suggestion}', [SuggestionController::class, 'show']);
        Route::post('suggestions/{suggestion}/accept', [SuggestionController::class, 'accept']);
        Route::post('suggestions/{suggestion}/reject', [SuggestionController::class, 'reject']);
    });
    ```

12. Write tests in `tests/Feature/SuggestionTest.php`:
    - Test listing suggestions (workspace scoped)
    - Test filtering by status
    - Test accepting suggestion
    - Test rejecting suggestion
    - Test cannot accept/reject already processed suggestion
    - Test cannot access other workspace's suggestions

### Verification

```bash
php artisan migrate --force
php artisan route:list | grep suggestion
php artisan test --compact --filter=SuggestionTest
vendor/bin/pint --dirty
```

### Success Criteria
- [ ] Both migrations run without errors
- [ ] Suggestion routes exist (index, show, accept, reject)
- [ ] Accept/reject changes status correctly
- [ ] Cannot re-process already accepted/rejected
- [ ] Workspace scoping works
- [ ] All SuggestionTest tests pass

---

## Phase 4: AI Extraction System

### Objectives
- Install and configure PHPPrism (or Prism) for OpenAI-compatible API
- Create extraction service with structured output
- Implement extraction endpoint
- Generate suggestions from extraction results

### Implementation Steps

1. Install Prism package:
   ```bash
   composer require prism-php/prism --no-interaction
   ```

2. Publish Prism config:
   ```bash
   php artisan vendor:publish --tag=prism-config --no-interaction
   ```

3. Configure Prism in `.env`:
   ```
   PRISM_PROVIDER=openai
   OPENAI_API_KEY=your-key-here
   ```

4. Create ExtractionService in `app/Services/ExtractionService.php`:
   - Method `extract(InboxItem $inboxItem): Extraction`
   - Build prompt with subject and content
   - Include locale and timezone from config
   - Call Prism with structured output schema
   - Store extraction result
   - Generate suggestions from result
   - Mark inbox item as parsed

5. Define the JSON schema for extraction output matching PRD:
   ```php
   [
       'events' => [['title', 'date', 'time', 'location']],
       'reminders' => [['message', 'offset']],
       'tasks' => [['title', 'due_date']]
   ]
   ```

6. Create prompt template in `resources/prompts/extraction.md`:
   - System prompt explaining the task
   - Output format requirements
   - Rules: no hallucinated dates, partial success allowed

7. Create ExtractInboxItemJob in `app/Jobs/ExtractInboxItemJob.php`:
   - Accepts InboxItem
   - Calls ExtractionService
   - Handles failures gracefully

8. Add extraction endpoint to InboxItemController:
   - `POST /api/inbox-items/{id}/extract`
   - Dispatch ExtractInboxItemJob
   - Return 202 Accepted

9. Update InboxItem model:
   - Add `extractions()` hasMany relationship
   - Add `latestExtraction()` hasOne relationship

10. Create tests in `tests/Feature/ExtractionTest.php`:
    - Test extraction endpoint triggers job
    - Test extraction creates extraction record
    - Test suggestions are created from extraction
    - Test inbox item is marked as parsed
    - Mock Prism for deterministic testing

### Verification

```bash
php artisan test --compact --filter=ExtractionTest
vendor/bin/pint --dirty
php artisan route:list | grep extract
```

### Success Criteria
- [ ] Prism package installed and configured
- [ ] Extraction endpoint exists
- [ ] Extraction creates record with model/prompt versions
- [ ] Suggestions are created from extraction results
- [ ] InboxItem status changes to 'parsed'
- [ ] All ExtractionTest tests pass

---

## Phase 5: Inbound Email (Resend Webhook)

### Objectives
- Create webhook endpoint for Resend inbound email
- Parse incoming emails and create inbox items
- Handle workspace lookup via token

### Implementation Steps

1. Create webhook controller:
   ```bash
   php artisan make:controller Webhooks/ResendInboundController --no-interaction
   ```

2. Implement webhook handler:
   - Parse inbound email payload from Resend
   - Extract workspace token from To address (inbox+{token}@domain.com)
   - Look up workspace by token
   - If workspace not found: log warning, return 200
   - Create InboxItem with source='email'
   - Return 200 (always, per PRD requirements)

3. Create FetchInboundEmailContentJob (for future full content fetch):
   - Placeholder for now, as Resend sends content in webhook

4. Register webhook route in `routes/web.php` (no auth):
   ```php
   Route::post('webhooks/resend/inbound', [ResendInboundController::class, 'handle'])
       ->name('webhooks.resend.inbound');
   ```

5. Add webhook secret verification middleware or inline check:
   - Verify Resend signature header
   - Reject invalid signatures with 401

6. Create config for Resend webhook:
   ```php
   // config/services.php
   'resend' => [
       'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
   ],
   ```

7. Write tests in `tests/Feature/ResendWebhookTest.php`:
   - Test valid webhook creates inbox item
   - Test invalid workspace token returns 200 but no item created
   - Test invalid signature returns 401
   - Test missing fields handled gracefully

### Verification

```bash
php artisan route:list | grep webhook
php artisan test --compact --filter=ResendWebhookTest
vendor/bin/pint --dirty
```

### Success Criteria
- [ ] Webhook endpoint exists at POST /webhooks/resend/inbound
- [ ] Valid email creates inbox item
- [ ] Invalid workspace token is handled gracefully
- [ ] Signature verification works
- [ ] Always returns 200 for valid signatures
- [ ] All ResendWebhookTest tests pass

---

## Phase 6: Frontend - Layout and Navigation

### Pre-Implementation: Read Required Skills

**STOP. Before writing any frontend code, read these skills:**

1. **Read `.agents/skills/laravel-inertia-react/SKILL.md`** completely
   - Note the page structure pattern
   - Note available layouts: `AppLayout`, `AuthLayout`, etc.
   - Note available components in `@/components/ui/`
   - Note hooks: `useActiveUrl`, `useMobile`, `useClipboard`

2. **Read `.agents/skills/frontend-design/SKILL.md`** completely
   - Establish a BOLD aesthetic direction for this app
   - Choose: refined minimalism, editorial, utilitarian, or another distinctive tone
   - Select distinctive typography (NOT Inter, Roboto, Arial)
   - Plan motion/animations for loading states and transitions

3. **Read `.agents/skills/vercel-react-best-practices/SKILL.md`** for:
   - `bundle-dynamic-imports` - Use next/dynamic for heavy components
   - `rerender-memo` - Extract expensive work into memoized components

### Design Direction (Establish Before Coding)

Document these decisions:
- **Tone**: [Choose: utilitarian/dashboard, editorial/magazine, refined/minimal, etc.]
- **Typography**: [Choose distinctive fonts, NOT generic]
- **Color palette**: [Define primary, accent, semantic colors]
- **Motion strategy**: [Loading skeletons, page transitions, micro-interactions]
- **Differentiation**: What makes this UI memorable?

### Objectives
- Create authenticated layout with navigation
- Set up Inertia pages structure following `laravel-inertia-react` patterns
- Implement sidebar/nav with Inbox, Suggestions, Settings links

### Implementation Steps

1. **Use existing layouts from `laravel-inertia-react` skill:**
   - Use `AppLayout` from `@/layouts/app-layout` for main pages
   - Use `AppSidebar` component for navigation
   - Follow the page structure pattern exactly:
   ```tsx
   import { Head } from '@inertiajs/react';
   import AppLayout from '@/layouts/app-layout';
   import { inbox } from '@/routes';
   import { type BreadcrumbItem } from '@/types';

   const breadcrumbs: BreadcrumbItem[] = [
       { title: 'Inbox', href: inbox().url },
   ];

   export default function InboxIndex({ items }: PageProps) {
       return (
           <AppLayout breadcrumbs={breadcrumbs}>
               <Head title="Inbox" />
               {/* Page content */}
           </AppLayout>
       );
   }
   ```

2. Update navigation in `AppSidebar` or create nav config:
   - Add links: Inbox, Suggestions, Settings
   - Use Wayfinder routes for type-safe navigation
   - Use `useActiveUrl` hook for active states

3. Create page components directory structure:
   ```
   resources/js/pages/
   ├── inbox/
   │   ├── index.tsx
   │   ├── show.tsx
   │   └── create.tsx
   ├── suggestions/
   │   └── index.tsx
   └── settings/
       └── index.tsx
   ```

4. Create placeholder pages following skill patterns

5. Register web routes in `routes/web.php`:
   ```php
   Route::middleware(['auth', 'verified'])->group(function () {
       Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
       Route::get('/inbox/new', [InboxController::class, 'create'])->name('inbox.create');
       Route::post('/inbox', [InboxController::class, 'store'])->name('inbox.store');
       Route::get('/inbox/{inboxItem}', [InboxController::class, 'show'])->name('inbox.show');

       Route::get('/suggestions', [SuggestionController::class, 'index'])->name('suggestions.index');

       Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
   });
   ```

6. Create web controllers:
   ```bash
   php artisan make:controller Web/InboxController --no-interaction
   php artisan make:controller Web/SuggestionController --no-interaction
   php artisan make:controller Web/SettingsController --no-interaction
   ```

7. Implement controllers to return Inertia responses with proper data

8. Run Wayfinder to generate TypeScript routes:
   ```bash
   php artisan wayfinder:generate
   ```

### Verification

```bash
php artisan route:list | grep -E "(inbox|suggestions|settings)"
npm run build
php artisan wayfinder:generate
```

### Success Criteria
- [ ] All web routes exist
- [ ] Wayfinder generates TypeScript actions
- [ ] `npm run build` succeeds
- [ ] Layout follows `laravel-inertia-react` patterns
- [ ] Navigation uses Wayfinder routes
- [ ] Design direction is established and distinctive

---

## Phase 7: Frontend - Inbox Pages

### Pre-Implementation Checklist

Reference these skill sections:
- `laravel-inertia-react/references/forms.md` - For manual entry form
- `laravel-inertia-react/references/data-fetching.md` - For deferred props
- `vercel-react-best-practices` rules:
  - `async-suspense-boundaries` - Use Suspense for streaming
  - `rendering-content-visibility` - For long lists
  - `rerender-transitions` - Use startTransition for non-urgent updates

### Objectives
- Implement Inbox Index page with item list
- Implement Inbox Detail page with extraction trigger
- Implement Manual Entry form

### Implementation Steps

1. **Update `resources/js/pages/inbox/index.tsx`:**
   - Follow `laravel-inertia-react` page structure pattern
   - Display paginated list of inbox items
   - Show: source badge, subject (truncated), status, received date
   - Action buttons: View, Archive, Extract (if status is 'new')
   - Use deferred props with `Deferred` component and `Skeleton` for loading
   - Filter by status (tabs using existing UI components)
   - Apply `frontend-design` aesthetics: motion on list items, distinctive badges

   ```tsx
   // Use deferred props pattern from laravel-inertia-react
   import { Deferred } from '@inertiajs/react';
   import { Skeleton } from '@/components/ui/skeleton';

   <Deferred data="items" fallback={<Skeleton className="h-20" />}>
       {/* List content */}
   </Deferred>
   ```

2. **Update `resources/js/pages/inbox/show.tsx`:**
   - Display full inbox item details
   - Raw subject and content (read-only, formatted)
   - Extraction history (if any)
   - Linked suggestions (if any)
   - Actions: Run Extraction (if not parsed), Archive
   - Use Wayfinder for action URLs

3. **Update `resources/js/pages/inbox/create.tsx`:**
   - Use `<Form>` component from Inertia with Wayfinder's `.form()`:
   ```tsx
   import { Form } from '@inertiajs/react';
   import { store } from '@/routes/inbox';
   import { Input } from '@/components/ui/input';
   import { Button } from '@/components/ui/button';
   import { Spinner } from '@/components/ui/spinner';
   import InputError from '@/components/input-error';

   <Form {...store.form()} className="flex flex-col gap-6">
       {({ processing, errors }) => (
           <>
               <Input name="raw_subject" placeholder="Subject (optional)" />
               <InputError message={errors.raw_subject} />

               <textarea name="raw_content" required />
               <InputError message={errors.raw_content} />

               <Button disabled={processing}>
                   {processing && <Spinner />}
                   Create Entry
               </Button>
           </>
       )}
   </Form>
   ```

4. **Create reusable components using existing UI primitives:**
   - `InboxItemCard.tsx` - Use `Card` from `@/components/ui/card`
   - `StatusBadge.tsx` - Use `Badge` from `@/components/ui/badge`
   - `SourceBadge.tsx` - Use `Badge` with icon

5. **Implement extraction trigger:**
   - Button calls POST /api/inbox-items/{id}/extract
   - Show loading state with `Spinner`
   - Use polling or optimistic update for completion

6. **Apply `frontend-design` principles:**
   - Distinctive typography for headings
   - Motion: staggered list animations on load
   - Meaningful color coding for status badges
   - Generous spacing and visual hierarchy

7. Update Web/InboxController:
   - `index()`: paginate items, include counts by status
   - `create()`: return empty form page
   - `store()`: create item, redirect to show
   - `show()`: load item with extractions and suggestions

8. Write browser tests using `webapp-testing` skill patterns:
   ```python
   # tests/browser/test_inbox.py
   from playwright.sync_api import sync_playwright

   with sync_playwright() as p:
       browser = p.chromium.launch(headless=True)
       page = browser.new_page()
       page.goto('http://localhost:8000/inbox')
       page.wait_for_load_state('networkidle')  # CRITICAL: Wait for JS
       # Test assertions...
       browser.close()
   ```

### Verification

```bash
npm run build
php artisan test --compact --filter=InboxTest
# If using Playwright:
python .agents/skills/webapp-testing/scripts/with_server.py \
  --server "php artisan serve" --port 8000 \
  -- python tests/browser/test_inbox.py
```

### Success Criteria
- [ ] Inbox index shows items with distinctive badges
- [ ] Manual entry form uses `<Form>` component pattern
- [ ] Item detail page shows all information
- [ ] Extraction can be triggered with loading state
- [ ] `npm run build` succeeds
- [ ] UI follows `frontend-design` aesthetic direction
- [ ] Forms follow `laravel-inertia-react` patterns exactly

---

## Phase 8: Frontend - Suggestions Page

### Pre-Implementation Checklist

Reference:
- `laravel-inertia-react` - Page structure, Link navigation
- `vercel-react-best-practices`:
  - `rerender-functional-setstate` - Use functional setState for stable callbacks
  - `client-swr-dedup` - Consider SWR for real-time updates

### Objectives
- Implement Suggestions Index with accept/reject actions
- Group suggestions by type
- Show connection to source inbox item

### Implementation Steps

1. **Update `resources/js/pages/suggestions/index.tsx`:**
   - Follow page structure pattern from `laravel-inertia-react`
   - List suggestions grouped by type (events, reminders, tasks)
   - Use tabs or toggle group for type filtering (use `ToggleGroup` from UI)
   - Status filter (proposed, accepted, rejected)
   - Each suggestion shows: type icon, payload preview, source item link
   - Action buttons: Accept, Reject (only for proposed)

2. **Create suggestion display components:**
   - `SuggestionCard.tsx` - Base card using `Card` component
   - `EventSuggestion.tsx` - Formatted event display with date/time
   - `ReminderSuggestion.tsx` - Formatted reminder with offset
   - `TaskSuggestion.tsx` - Formatted task with due date

3. **Implement accept/reject actions:**
   - Use Inertia router.post() to call accept/reject endpoints:
   ```tsx
   import { router } from '@inertiajs/react';
   import { accept, reject } from '@/routes/suggestion';

   const handleAccept = () => {
       router.post(accept(suggestion.id).url, {}, {
           preserveScroll: true,
           onSuccess: () => { /* Optional callback */ }
       });
   };
   ```
   - Disable buttons during processing
   - Show `Spinner` in button during action

4. **Apply `frontend-design` principles:**
   - Visual distinction between suggestion types (color, icon, layout)
   - Motion: smooth transitions on accept/reject
   - Satisfying feedback on action completion
   - Clear visual hierarchy

5. Update Web/SuggestionController:
   - `index()`: load suggestions with inbox item info, grouped/filtered

6. Add link from inbox item detail to its suggestions using `TextLink` component

7. Write browser tests following `webapp-testing` patterns

### Verification

```bash
npm run build
php artisan test --compact --filter=SuggestionTest
```

### Success Criteria
- [ ] Suggestions page shows items grouped by type
- [ ] Accept action works with loading state
- [ ] Reject action works with loading state
- [ ] Cannot re-process accepted/rejected suggestions
- [ ] `npm run build` succeeds
- [ ] UI is distinctive and follows design direction

---

## Phase 9: Frontend - Settings Page

### Objectives
- Display workspace information
- Show inbound email address with copy functionality
- Implement logout

### Implementation Steps

1. **Update `resources/js/pages/settings/index.tsx`:**
   - Use `SettingsLayout` if available, or `AppLayout`
   - Section: Workspace Info (name, created date) using `Card`
   - Section: Inbound Email Address
     - Display: `inbox+{token}@yourdomain.com`
     - Copy to clipboard button using `useClipboard` hook from `laravel-inertia-react`
     - Instructions for email forwarding
   - Section: Account
     - Logout button

   ```tsx
   import { useClipboard } from '@/hooks/use-clipboard';
   import { Button } from '@/components/ui/button';

   const { copy, copied } = useClipboard();

   <Button onClick={() => copy(inboundEmail)} variant="outline">
       {copied ? 'Copied!' : 'Copy'}
   </Button>
   ```

2. Update Web/SettingsController:
   - `index()`: load workspace with inbound email token
   - Compute full inbound email address from config

3. Add config for inbound email domain:
   ```php
   // config/services.php
   'inbound_domain' => env('INBOUND_EMAIL_DOMAIN', 'inbox.yourdomain.com'),
   ```

4. **Apply `frontend-design` principles:**
   - Clean, organized settings layout
   - Clear section headings
   - Helpful contextual information

### Verification

```bash
npm run build
```

### Success Criteria
- [ ] Settings page shows workspace info
- [ ] Inbound email address is displayed correctly
- [ ] Copy to clipboard works using `useClipboard` hook
- [ ] Logout works
- [ ] `npm run build` succeeds

---

## Self-Correction Patterns

### When Tests Fail

1. Read the complete error output
2. Identify the failing test and assertion
3. Check if it's a:
   - Missing migration: run `php artisan migrate`
   - Missing route: check routes/web.php or routes/api.php
   - Policy issue: check policy methods
   - Relationship issue: check model relationships
4. Fix the specific issue
5. Re-run the failing test: `php artisan test --filter=testName`
6. Once passing, run full suite

### When Build Fails

1. Check `npm run build` output for specific errors
2. Common issues:
   - TypeScript errors: fix type annotations
   - Missing imports: add the import
   - Wayfinder out of date: run `php artisan wayfinder:generate`
3. Fix and rebuild

### When Migrations Fail

1. Check if migration order is correct (dependencies)
2. Check foreign key references exist
3. For SQLite: some operations need workarounds
4. Consider `php artisan migrate:fresh` in development

### When Frontend Doesn't Match Skills

1. Re-read the relevant skill file
2. Check if you're using the correct imports
3. Verify component names match (`@/components/ui/button` not custom)
4. Ensure Wayfinder is generated: `php artisan wayfinder:generate`

### Preventing Infinite Loops

If the same error occurs 3 times:
1. STOP attempting the same fix
2. Try a fundamentally different approach
3. Check if a dependency is missing
4. Review the PRD for clarification

---

## Final Verification Checklist

Run all of these before marking complete:

```bash
# 1. Run all tests
php artisan test --compact

# 2. Check lint
vendor/bin/pint --test

# 3. Build frontend
npm run build

# 4. Verify routes exist
php artisan route:list | grep -E "(inbox|suggestion|webhook)"

# 5. Generate Wayfinder types
php artisan wayfinder:generate

# 6. Clear and verify caches
php artisan config:clear && php artisan route:clear && php artisan cache:clear
```

All commands must succeed with no errors.

---

## Completion

When ALL of the following are true:
- `php artisan test --compact` shows all tests passing
- `vendor/bin/pint --test` shows no formatting issues
- `npm run build` completes without errors
- All routes from the PRD exist
- Core flow (register → inbox → extract → suggest → accept) works
- Frontend uses patterns from `laravel-inertia-react` skill
- Frontend follows `frontend-design` aesthetic principles
- React components follow `vercel-react-best-practices` patterns

Output `<promise>DONE</promise>` when complete
