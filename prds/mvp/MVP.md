Below is a **complete, implementation-ready PRD** for the **MVP (Backend + Web Frontend only)**, aligned with **Laravel best practices** and your chosen stack.

No app/iOS scope included.
This is written so that:

* you can hand it to Codex / Claude Code
* or use it as the canonical spec while building yourself

---

# PRD — Personal Command Center (MVP)

**Scope:** Backend (Laravel) + Web Frontend (React + Inertia)
**Out of scope:** Native mobile apps, calendar write access, advanced automation

---

## 1. Product Overview

### 1.1 Problem

Users receive important obligations through fragmented channels:

* Emails (appointments, school/kita notices, bookings)
* Messages (WhatsApp, tools, apps)
* Manual notes

They forget things because:

* information is unstructured
* reminders are implicit, not explicit
* calendars are passive

---

### 1.2 Solution (MVP)

A **personal command center** that:

1. Ingests unstructured input (starting with email)
2. Uses AI to extract meaning (events, tasks, reminders)
3. Shows **proposed actions**
4. Lets the user approve/reject
5. Keeps everything auditable and controllable

---

### 1.3 Target Users

* Singles
* Couples
* Families
* Knowledge workers
* Parents

**Important:** Product language and UX must remain **neutral**, not family-specific.

---

## 2. Tech Stack (Fixed)

### Backend

* Laravel (latest)
* PHP 8.3+
* Laravel Sanctum (SPA auth)
* Laravel Horizon (queues)
* PHPPrism (OpenAI-compatible API)
* Resend (Inbound email)
* MySQL or PostgreSQL

### Frontend

* React
* Inertia.js
* Tailwind CSS
* Laravel Breeze (React preset)

---

## 3. Non-Goals (MVP)

Explicitly **not included**:

* Calendar write access on backend
* WhatsApp API integration
* School/Kita APIs
* Android / iOS apps
* Fully automated acceptance
* Team / org billing
* Analytics dashboards

---

## 4. Core Domain Concepts

### 4.1 Workspace

A logical container for data.

* Default: 1 workspace per user
* Future: invite others

---

### 4.2 Inbox Item

Raw, unstructured input.

Sources:

* `email`
* `manual`
* `share` (future)

Inbox items are **immutable raw facts**.

---

### 4.3 Extraction

AI interpretation of an inbox item.

* Multiple extractions per inbox item allowed
* Each extraction tied to:

  * model version
  * prompt version

---

### 4.4 Suggestion

A proposed action derived from extraction.

Types:

* `event`
* `reminder`
* `task`

Suggestions require explicit user approval.

---

## 5. Backend Requirements (Laravel)

---

### 5.1 Authentication

**Requirements**

* Laravel Sanctum SPA authentication
* Cookie-based auth
* CSRF protection enabled
* Breeze React starter

**Auth Methods (MVP)**

* Email-based login (password or magic link — implementation choice)
* Sign in with Apple optional later

**Best Practices**

* No custom auth logic
* Use Laravel guards/middleware
* Use `auth:sanctum` consistently

---

### 5.2 Workspace Bootstrap

**On first login / registration**

* Create default workspace
* Assign user as `owner`

**Requirements**

* Atomic operation
* Idempotent
* No orphan users

---

### 5.3 Inbox Items

#### API

* `POST /api/inbox-items`
* `GET /api/inbox-items`
* `GET /api/inbox-items/{id}`
* `PATCH /api/inbox-items/{id}` (status only)

#### Fields

* source
* raw_subject
* raw_content
* received_at
* status (`new`, `parsed`, `archived`)

#### Rules

* Inbox items are never deleted in MVP
* Status changes are explicit
* Access always scoped to workspace

---

### 5.4 AI Extraction

#### Endpoint

* `POST /api/inbox-items/{id}/extract`

#### Behavior

1. Validate ownership
2. Send text to PHPPrism
3. Store extraction result
4. Generate suggestions
5. Mark inbox item as `parsed`

#### Requirements

* Extraction must be repeatable
* Prompt version stored
* Model version stored
* JSON output strictly validated

---

### 5.5 Suggestion Lifecycle

#### API

* `GET /api/suggestions?status=proposed`
* `POST /api/suggestions/{id}/accept`
* `POST /api/suggestions/{id}/reject`

#### Rules

* Suggestions default to `proposed`
* Accept/reject is irreversible (MVP)
* No side effects beyond state change

---

### 5.6 Inbound Email (Resend)

#### Flow

1. Email forwarded to:

   ```
   inbox+<workspace_token>@yourdomain.com
   ```
2. Resend webhook fires
3. Backend validates secret
4. InboxItem created
5. Job queued to fetch full content
6. InboxItem updated

#### Requirements

* Webhook must always return 200
* Missing workspace = logged + ignored
* Raw emails not stored permanently
* Horizon handles all async work

---

### 5.7 Queues & Horizon

**Queue Jobs**

* Fetch inbound email content
* (Later) AI extraction async

**Requirements**

* All external IO async
* Horizon dashboard enabled in non-prod
* Retries + backoff configured

---

## 6. AI Contract (Strict)

### 6.1 Input

* Subject (optional)
* Body text
* Locale + timezone

### 6.2 Output JSON Schema (example)

```json
{
  "events": [
    {
      "title": "Parents evening",
      "date": "2026-01-20",
      "time": "19:00",
      "location": null
    }
  ],
  "reminders": [
    {
      "message": "Prepare documents",
      "offset": "P1D"
    }
  ],
  "tasks": [
    {
      "title": "Bring 5€",
      "due_date": "2026-01-20"
    }
  ]
}
```

**Rules**

* Must validate against schema
* Partial success allowed
* No hallucinated dates

---

## 7. Frontend (React + Inertia)

---

### 7.1 Global Layout

**Requirements**

* Authenticated layout
* Sidebar or top nav:

  * Inbox
  * Suggestions
  * Settings

---

### 7.2 Pages (Mandatory)

---

#### 7.2.1 Login / Auth

* Email input
* Login flow
* Error handling

---

#### 7.2.2 Inbox Index

**Route**

```
GET /inbox
```

**Displays**

* List of inbox items
* Source badge
* Status
* Received date

**Actions**

* View
* Archive
* Extract (CTA)

---

#### 7.2.3 Inbox Detail

**Route**

```
GET /inbox/{id}
```

**Displays**

* Raw subject
* Raw content (read-only)
* Extraction history
* Suggestions (if any)

**Actions**

* Run extraction
* Archive item

---

#### 7.2.4 Suggestions Index

**Route**

```
GET /suggestions
```

**Displays**

* Grouped by type
* Status filter
* Payload preview

**Actions**

* Accept
* Reject

---

#### 7.2.5 Suggestion Detail (optional MVP+)

* Payload JSON rendered human-readable
* Accept / Reject

---

#### 7.2.6 Manual Inbox Entry

**Route**

```
GET /inbox/new
```

**Form**

* Subject (optional)
* Content (required)

---

#### 7.2.7 Settings

**Sections**

* Workspace info
* Inbound email address
* Logout

---

## 8. UX Rules (Important)

* No auto-creation of events
* No hidden automation
* Every AI action is visible
* Every suggestion is reversible (until accepted)
* Clear language: “Suggested”, not “Created”

---

## 9. Security & Privacy

**Requirements**

* No full mailbox access
* Forwarded emails only
* Tokenized inbound addresses
* Raw email deletion policy (configurable)
* No third-party tracking

---

## 10. Success Criteria (MVP)

The MVP is successful if:

* User can forward an email
* Inbox item appears
* Extraction produces suggestions
* User accepts/rejects
* Flow feels trustworthy

**Key metric**

> “I don’t forget things anymore.”

---

## 11. Build Order (Recommended)

1. Auth + workspace bootstrap
2. Inbox CRUD
3. Inertia pages for Inbox
4. Extraction stub
5. Suggestions lifecycle
6. Resend inbound
7. Horizon + queues

