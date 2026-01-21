# Wayfinder API

Wayfinder generates TypeScript functions and types for Laravel routes, providing type safety and automatic synchronization between backend routes and frontend code.

## Table of Contents

- [Import Patterns](#import-patterns)
- [Return Values](#return-values)
- [URL Extraction](#url-extraction)
- [Form Integration](#form-integration)
- [Route Parameters](#route-parameters)
- [Query Parameters](#query-parameters)
- [HTTP Methods](#http-methods)
- [Generated File Locations](#generated-file-locations)

## Import Patterns

### Named Routes (`@/routes/`)

Import route functions from `@/routes/` for named Laravel routes:

```tsx
// routes/index.ts - top-level routes
import { dashboard, home, login, logout, register } from '@/routes';

// routes/[name]/index.ts - nested routes
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
```

### Controller Actions (`@/actions/`)

Import controller actions for full type-safe access to controller methods:

```tsx
// Default import for all methods
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
ProfileController.edit()
ProfileController.update()
ProfileController.destroy()

// Named imports for specific methods
import { edit, update, destroy } from '@/actions/App/Http/Controllers/Settings/ProfileController';
```

## Return Values

Route functions return `{ url: string, method: string }`:

```tsx
import { dashboard } from '@/routes';

dashboard()
// => { url: '/dashboard', method: 'get' }

dashboard().url
// => '/dashboard'

dashboard().method
// => 'get'
```

## URL Extraction

Use `.url()` to get just the URL string:

```tsx
import { edit } from '@/routes/profile';

// For breadcrumbs
const breadcrumbs = [
    { title: 'Profile', href: edit().url },
];

// For router.visit()
router.visit(edit().url);
```

## Form Integration

Use `.form()` to get form attributes (`{ action, method }`):

```tsx
import { Form } from '@inertiajs/react';
import { store } from '@/routes/login';

<Form {...store.form()}>
    {/* form fields */}
</Form>
// Renders: <form action="/login" method="post">
```

### With Controller Actions

```tsx
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';

<Form {...ProfileController.update.form()}>
    {/* form fields */}
</Form>
```

### HTTP Method Spoofing

For non-GET/POST methods, Wayfinder automatically handles method spoofing:

```tsx
import { destroy } from '@/actions/.../ResourceController';

destroy.form()
// => { action: '/resource?_method=DELETE', method: 'post' }
```

## Route Parameters

Pass parameters directly to route functions:

```tsx
import { show } from '@/routes/post';

// Single parameter
show(123)
// => { url: '/posts/123', method: 'get' }

// Route model binding with key
// For routes like {post:slug}
show('my-post-slug')
// => { url: '/posts/my-post-slug', method: 'get' }

// Object with properties
show({ id: 123 })
// or for slug binding
show({ slug: 'my-post-slug' })
```

## Query Parameters

### Adding Query Parameters

```tsx
import { index } from '@/routes/posts';

index({ query: { page: 2, per_page: 10 } })
// => { url: '/posts?page=2&per_page=10', method: 'get' }
```

### Merging with Current Query

Use `mergeQuery` to merge with `window.location.search`:

```tsx
import { index } from '@/routes/posts';

// Current URL: /posts?status=active
index({ mergeQuery: { page: 2 } })
// => { url: '/posts?status=active&page=2', method: 'get' }

// Remove a parameter by setting to null
index({ mergeQuery: { status: null, page: 2 } })
// => { url: '/posts?page=2', method: 'get' }
```

## HTTP Methods

Call specific HTTP method variants:

```tsx
import { show } from '@/routes/post';

show(1)       // Default method
// => { url: '/posts/1', method: 'get' }

show.get(1)   // Explicit GET
// => { url: '/posts/1', method: 'get' }

show.head(1)  // HEAD request
// => { url: '/posts/1', method: 'head' }
```

For POST/PATCH/DELETE routes:

```tsx
import { store, update, destroy } from '@/routes/post';

store.post()
// => { url: '/posts', method: 'post' }

update.patch(1)
// => { url: '/posts/1', method: 'patch' }

destroy.delete(1)
// => { url: '/posts/1', method: 'delete' }
```

## Generated File Locations

Wayfinder generates files in these locations:

```
resources/js/
├── routes/              # Named routes
│   ├── index.ts         # Top-level named routes (home, dashboard, etc.)
│   ├── login/
│   │   └── index.ts     # Routes for 'login.*' names
│   ├── password/
│   │   └── index.ts     # Routes for 'password.*' names
│   └── ...
├── actions/             # Controller actions
│   └── App/
│       └── Http/
│           └── Controllers/
│               ├── Settings/
│               │   ├── ProfileController.ts
│               │   └── PasswordController.ts
│               └── ...
└── wayfinder.ts         # Type definitions
```

### Regenerating Routes

After changing Laravel routes, regenerate Wayfinder files:

```bash
php artisan wayfinder:generate
```

If using the Vite plugin (`@laravel/vite-plugin-wayfinder`), routes regenerate automatically during development.
