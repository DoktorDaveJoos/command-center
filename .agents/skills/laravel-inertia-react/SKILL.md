---
name: laravel-inertia-react
description: Guide for building frontend applications with Laravel, Inertia.js v2, React 19, and Wayfinder. Triggers on: (1) Creating Inertia pages or components, (2) Form handling with Inertia, (3) Navigation and routing, (4) Wayfinder API integration, (5) Data fetching patterns (deferred props, shared data), (6) Frontend/backend connectivity questions.
---

# Laravel + Inertia + React Frontend

This stack uses Laravel 12, Inertia.js v2, React 19, and Wayfinder for type-safe routing.

## Quick Reference

| Task | Import | Usage |
|------|--------|-------|
| Form submission | `import { Form } from '@inertiajs/react'` | `<Form {...store.form()}>` |
| Navigation | `import { Link } from '@inertiajs/react'` | `<Link href={route()}>` |
| Page head | `import { Head } from '@inertiajs/react'` | `<Head title="Page" />` |
| Shared data | `import { usePage } from '@inertiajs/react'` | `usePage<SharedData>().props` |
| Route functions | `import { routeName } from '@/routes'` | `dashboard()` returns `{ url, method }` |
| Controller actions | `import Controller from '@/actions/...'` | `Controller.method.form()` |

## Directory Structure

```
resources/js/
├── pages/              # Inertia page components
│   ├── auth/           # Authentication pages
│   └── settings/       # Settings pages
├── components/         # Reusable components
│   └── ui/             # UI primitives (button, input, etc.)
├── layouts/            # Page layouts
│   ├── app-layout.tsx  # Main app layout
│   └── auth-layout.tsx # Auth pages layout
├── hooks/              # Custom React hooks
├── types/              # TypeScript definitions
├── routes/             # Wayfinder generated routes
└── actions/            # Wayfinder generated controller actions
```

## Page Structure Pattern

```tsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { routeName } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface PageProps {
    data: SomeType;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Page Title', href: routeName().url },
];

export default function PageName({ data }: PageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Page Title" />
            {/* Page content */}
        </AppLayout>
    );
}
```

## Form Pattern

Use `<Form>` with Wayfinder's `.form()` method for type-safe form submission:

```tsx
import { Form } from '@inertiajs/react';
import { store } from '@/routes/resource';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

<Form
    {...store.form()}
    resetOnSuccess={['password']}
    className="flex flex-col gap-6"
>
    {({ processing, errors }) => (
        <>
            <Input name="email" type="email" />
            <InputError message={errors.email} />
            <Button disabled={processing}>
                {processing && <Spinner />}
                Submit
            </Button>
        </>
    )}
</Form>
```

**See [references/forms.md](references/forms.md) for complete form handling guide.**

## Navigation Pattern

```tsx
import { Link } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { dashboard } from '@/routes';
import { show } from '@/routes/post';

// Standard navigation
<Link href={dashboard()}>Dashboard</Link>

// With prefetching
<Link href={dashboard()} prefetch>Dashboard</Link>

// Styled text link
<TextLink href={show(postId)}>View Post</TextLink>
```

**See [references/navigation.md](references/navigation.md) for active states and programmatic navigation.**

## Shared Data Access

```tsx
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

const { auth, name } = usePage<SharedData>().props;
// auth.user contains the current user
```

**See [references/data-fetching.md](references/data-fetching.md) for deferred props and polling.**

## Available Components

**UI Components** (`@/components/ui/`):
`Button`, `Input`, `Label`, `Checkbox`, `Select`, `Card`, `Dialog`, `Sheet`, `Alert`, `Badge`, `Avatar`, `Skeleton`, `Spinner`, `Separator`, `Tooltip`, `DropdownMenu`, `NavigationMenu`, `Breadcrumb`, `Sidebar`, `Collapsible`, `Toggle`, `ToggleGroup`, `InputOtp`

**App Components** (`@/components/`):
`InputError`, `TextLink`, `Heading`, `Breadcrumbs`, `AppSidebar`, `AppHeader`, `AppShell`, `AppLogo`, `NavMain`, `NavUser`, `NavFooter`, `UserInfo`, `DeleteUser`, `AppearanceTabs`

## Available Layouts

| Layout | Import | Use Case |
|--------|--------|----------|
| `AppLayout` | `@/layouts/app-layout` | Main app pages (sidebar) |
| `AuthLayout` | `@/layouts/auth-layout` | Auth pages (login, register) |
| `SettingsLayout` | `@/layouts/settings/layout` | Settings pages |
| `AppSidebarLayout` | `@/layouts/app/app-sidebar-layout` | Direct sidebar layout |
| `AppHeaderLayout` | `@/layouts/app/app-header-layout` | Header-only layout |
| `AuthCardLayout` | `@/layouts/auth/auth-card-layout` | Card-style auth |
| `AuthSimpleLayout` | `@/layouts/auth/auth-simple-layout` | Simple auth |
| `AuthSplitLayout` | `@/layouts/auth/auth-split-layout` | Split-screen auth |

## Available Hooks

| Hook | Import | Purpose |
|------|--------|---------|
| `useActiveUrl` | `@/hooks/use-active-url` | Check if URL is active |
| `useMobile` | `@/hooks/use-mobile` | Detect mobile viewport |
| `useClipboard` | `@/hooks/use-clipboard` | Copy to clipboard |
| `useAppearance` | `@/hooks/use-appearance` | Theme management |

## Wayfinder Integration

Wayfinder generates type-safe route functions. Import from:
- `@/routes/` - Named routes (e.g., `dashboard`, `login`)
- `@/actions/` - Controller actions (e.g., `ProfileController.update`)

**See [references/wayfinder.md](references/wayfinder.md) for complete Wayfinder API.**
