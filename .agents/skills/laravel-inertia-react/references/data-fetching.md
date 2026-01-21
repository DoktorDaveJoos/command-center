# Data Fetching

## Table of Contents

- [Shared Data](#shared-data)
- [Page Props](#page-props)
- [Deferred Props](#deferred-props)
- [Polling](#polling)
- [Infinite Scrolling](#infinite-scrolling)
- [WhenVisible](#whenvisible)

## Shared Data

Access shared data available on all pages using `usePage`:

```tsx
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

export default function Component() {
    const { auth, name, sidebarOpen } = usePage<SharedData>().props;

    return (
        <div>
            <p>Welcome, {auth.user.name}</p>
            <p>App: {name}</p>
        </div>
    );
}
```

### SharedData Interface

```ts
// resources/js/types/index.d.ts
export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface Auth {
    user: User;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
}
```

## Page Props

Define page-specific props as an interface:

```tsx
interface PageProps {
    users: User[];
    filters: {
        search: string;
        status: string;
    };
    pagination: {
        current_page: number;
        last_page: number;
    };
}

export default function UsersIndex({ users, filters, pagination }: PageProps) {
    return (
        // ...
    );
}
```

### Combining with SharedData

```tsx
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

interface PageProps {
    posts: Post[];
}

export default function Posts({ posts }: PageProps) {
    // Access both page props and shared data
    const { auth } = usePage<SharedData>().props;

    return (
        <div>
            <p>Posts by {auth.user.name}</p>
            {posts.map(post => (
                <article key={post.id}>{post.title}</article>
            ))}
        </div>
    );
}
```

## Deferred Props

Defer loading of expensive data to improve initial page load.

### Server-side (Laravel)

```php
use Inertia\Inertia;

return Inertia::render('Dashboard', [
    'stats' => Inertia::defer(fn () => $this->getExpensiveStats()),
    'notifications' => Inertia::defer(fn () => $user->notifications),
]);
```

### Client-side (React)

```tsx
import { Deferred } from '@inertiajs/react';
import { Skeleton } from '@/components/ui/skeleton';

interface PageProps {
    stats?: Stats;
    notifications?: Notification[];
}

export default function Dashboard({ stats, notifications }: PageProps) {
    return (
        <div>
            <Deferred data="stats" fallback={<StatsSkeleton />}>
                <StatsDisplay stats={stats!} />
            </Deferred>

            <Deferred data="notifications" fallback={<NotificationsSkeleton />}>
                <NotificationsList notifications={notifications!} />
            </Deferred>
        </div>
    );
}

function StatsSkeleton() {
    return (
        <div className="grid gap-4 md:grid-cols-3">
            {[1, 2, 3].map(i => (
                <Skeleton key={i} className="h-24" />
            ))}
        </div>
    );
}
```

### Multiple Deferred Props with Same Group

Load related data together:

```php
return Inertia::render('Dashboard', [
    'stats' => Inertia::defer(fn () => $this->getStats(), 'analytics'),
    'chart' => Inertia::defer(fn () => $this->getChartData(), 'analytics'),
]);
```

```tsx
<Deferred data={['stats', 'chart']} fallback={<AnalyticsSkeleton />}>
    <Analytics stats={stats!} chart={chart!} />
</Deferred>
```

## Polling

Automatically refresh data at intervals:

```tsx
import { usePoll } from '@inertiajs/react';

export default function LiveDashboard({ metrics }) {
    // Poll every 5 seconds
    usePoll(5000);

    // Poll specific props only
    usePoll(5000, { only: ['metrics'] });

    // Conditional polling
    const [isLive, setIsLive] = useState(true);
    usePoll(5000, {}, { autoStart: isLive });

    return <MetricsDisplay metrics={metrics} />;
}
```

### Polling with Controls

```tsx
import { usePoll } from '@inertiajs/react';

export default function LiveFeed({ posts }) {
    const { start, stop, polling } = usePoll(3000, { only: ['posts'] }, {
        autoStart: false,
    });

    return (
        <div>
            <button onClick={polling ? stop : start}>
                {polling ? 'Pause' : 'Resume'} Live Updates
            </button>
            <PostList posts={posts} />
        </div>
    );
}
```

## Infinite Scrolling

Use merging props for infinite scroll lists.

### Server-side

```php
return Inertia::render('Posts/Index', [
    'posts' => Inertia::merge($posts->items()),
    'nextCursor' => $posts->nextCursor(),
]);
```

### Client-side

```tsx
import { router } from '@inertiajs/react';
import { useCallback } from 'react';

interface PageProps {
    posts: Post[];
    nextCursor: string | null;
}

export default function Posts({ posts, nextCursor }: PageProps) {
    const loadMore = useCallback(() => {
        if (!nextCursor) return;

        router.reload({
            data: { cursor: nextCursor },
            only: ['posts', 'nextCursor'],
        });
    }, [nextCursor]);

    return (
        <div>
            {posts.map(post => (
                <PostCard key={post.id} post={post} />
            ))}

            {nextCursor && (
                <button onClick={loadMore}>Load More</button>
            )}
        </div>
    );
}
```

## WhenVisible

Lazy load content when it becomes visible:

```tsx
import { WhenVisible } from '@inertiajs/react';
import { Skeleton } from '@/components/ui/skeleton';

export default function Feed({ initialPosts }) {
    return (
        <div>
            {initialPosts.map(post => (
                <PostCard key={post.id} post={post} />
            ))}

            <WhenVisible
                data="morePosts"
                fallback={<PostSkeleton />}
                params={{ page: 2 }}
            >
                {({ morePosts }) => (
                    morePosts.map(post => (
                        <PostCard key={post.id} post={post} />
                    ))
                )}
            </WhenVisible>
        </div>
    );
}
```

### WhenVisible with Buffer

Load before element is fully visible:

```tsx
<WhenVisible
    data="comments"
    fallback={<CommentsSkeleton />}
    buffer={200} // Start loading 200px before visible
>
    {({ comments }) => <CommentsList comments={comments} />}
</WhenVisible>
```
