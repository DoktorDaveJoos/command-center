# Navigation

## Table of Contents

- [Link Component](#link-component)
- [TextLink Component](#textlink-component)
- [Active State Detection](#active-state-detection)
- [Prefetching](#prefetching)
- [Programmatic Navigation](#programmatic-navigation)
- [Breadcrumbs](#breadcrumbs)
- [Navigation Items Array](#navigation-items-array)

## Link Component

Use Inertia's `Link` component for client-side navigation:

```tsx
import { Link } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { show } from '@/routes/post';

// Basic link with Wayfinder route
<Link href={dashboard()}>Dashboard</Link>

// With route parameters
<Link href={show(postId)}>View Post</Link>

// External URL (normal navigation)
<Link href="https://example.com">External</Link>

// POST request link
<Link href={logout()} method="post" as="button">
    Log out
</Link>
```

## TextLink Component

Use `TextLink` for styled inline links:

```tsx
import TextLink from '@/components/text-link';
import { request } from '@/routes/password';

<TextLink href={request()} className="text-sm">
    Forgot password?
</TextLink>
```

TextLink provides:
- Underline decoration
- Hover transition effects
- Dark mode support

## Active State Detection

Use the `useActiveUrl` hook to check if a URL is active:

```tsx
import { Link } from '@inertiajs/react';
import { useActiveUrl } from '@/hooks/use-active-url';

function NavItem({ href, children }) {
    const { urlIsActive } = useActiveUrl();

    return (
        <Link
            href={href}
            className={urlIsActive(href) ? 'text-primary' : 'text-muted'}
        >
            {children}
        </Link>
    );
}
```

The hook returns:
- `currentUrl`: The current URL path
- `urlIsActive(href)`: Function to check if href matches current URL

## Prefetching

Enable prefetching to load pages before the user clicks:

```tsx
// Prefetch on hover (default)
<Link href={dashboard()} prefetch>Dashboard</Link>

// Prefetch on mount
<Link href={dashboard()} prefetch="mount">Dashboard</Link>

// Prefetch on hover with cacheFor
<Link href={dashboard()} prefetch cacheFor="1m">Dashboard</Link>
```

## Programmatic Navigation

Use the `router` for programmatic navigation:

```tsx
import { router } from '@inertiajs/react';
import { dashboard } from '@/routes';

// Navigate to a page
router.visit(dashboard().url);

// With options
router.visit(dashboard().url, {
    method: 'get',
    preserveState: true,
    preserveScroll: true,
});

// POST request
router.post(store().url, data);

// Reload current page
router.reload();

// Reload with specific props
router.reload({ only: ['users'] });
```

## Breadcrumbs

Define breadcrumbs as an array and pass to the layout:

```tsx
import { type BreadcrumbItem } from '@/types';
import { edit } from '@/routes/profile';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

export default function Profile() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            {/* Page content */}
        </AppLayout>
    );
}
```

For nested pages:

```tsx
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: settings().url },
    { title: 'Password', href: edit().url },
];
```

## Navigation Items Array

Pattern for building navigation menus:

```tsx
import { type NavItem } from '@/types';
import { Home, Settings, Users } from 'lucide-react';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/users';
import { edit as settingsEdit } from '@/routes/settings';

const navItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: Home,
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: Users,
    },
    {
        title: 'Settings',
        href: settingsEdit(),
        icon: Settings,
    },
];
```

Render with active state:

```tsx
import { Link } from '@inertiajs/react';
import { useActiveUrl } from '@/hooks/use-active-url';

function NavMenu({ items }: { items: NavItem[] }) {
    const { urlIsActive } = useActiveUrl();

    return (
        <nav>
            {items.map((item) => (
                <Link
                    key={item.title}
                    href={item.href}
                    prefetch
                    className={urlIsActive(item.href) ? 'active' : ''}
                >
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            ))}
        </nav>
    );
}
```
