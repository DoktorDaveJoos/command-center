import { Head, Link } from '@inertiajs/react';
import { Archive, Mail, Plus, Sparkles } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import { create, index, show } from '@/routes/inbox';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inbox',
        href: index().url,
    },
];

interface InboxItem {
    id: number;
    source: string;
    raw_subject: string | null;
    raw_content: string;
    status: string;
    received_at: string;
    created_at: string;
}

interface PageProps {
    items: {
        data: InboxItem[];
        links: {
            first: string | null;
            last: string | null;
            prev: string | null;
            next: string | null;
        };
        meta: {
            current_page: number;
            last_page: number;
            total: number;
        };
    };
    counts: {
        all: number;
        new: number;
        parsed: number;
        archived: number;
    };
    currentStatus: string | null;
}

function StatusBadge({ status }: { status: string }) {
    const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
        new: 'default',
        parsed: 'secondary',
        archived: 'outline',
    };

    return (
        <Badge variant={variants[status] || 'default'} className="capitalize">
            {status}
        </Badge>
    );
}

function SourceBadge({ source }: { source: string }) {
    const icons: Record<string, typeof Mail> = {
        email: Mail,
        manual: Plus,
        share: Sparkles,
    };

    const Icon = icons[source] || Mail;

    return (
        <Badge variant="outline" className="gap-1 capitalize">
            <Icon className="h-3 w-3" />
            {source}
        </Badge>
    );
}

function InboxItemCard({ item }: { item: InboxItem }) {
    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <Link href={show(item.id).url} className="block">
            <Card className="transition-all hover:border-primary/50 hover:shadow-md">
                <CardHeader className="pb-2">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0 flex-1">
                            <CardTitle className="truncate text-base font-medium">
                                {item.raw_subject || '(No subject)'}
                            </CardTitle>
                            <CardDescription className="mt-1 line-clamp-2 text-sm">
                                {item.raw_content.slice(0, 150)}
                                {item.raw_content.length > 150 && '...'}
                            </CardDescription>
                        </div>
                        <div className="flex shrink-0 flex-col items-end gap-2">
                            <StatusBadge status={item.status} />
                            <span className="text-xs text-muted-foreground">{formatDate(item.received_at)}</span>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="pt-0">
                    <div className="flex items-center gap-2">
                        <SourceBadge source={item.source} />
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}

export default function InboxIndex({ items, counts, currentStatus }: PageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inbox" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Inbox</h1>
                        <p className="text-sm text-muted-foreground">Review and process incoming items</p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Entry
                        </Link>
                    </Button>
                </div>

                <ToggleGroup
                    type="single"
                    value={currentStatus || 'all'}
                    className="justify-start"
                >
                    <ToggleGroupItem value="all" asChild>
                        <Link href={index().url} preserveState>
                            All ({counts.all})
                        </Link>
                    </ToggleGroupItem>
                    <ToggleGroupItem value="new" asChild>
                        <Link href={index({ query: { status: 'new' } }).url} preserveState>
                            New ({counts.new})
                        </Link>
                    </ToggleGroupItem>
                    <ToggleGroupItem value="parsed" asChild>
                        <Link href={index({ query: { status: 'parsed' } }).url} preserveState>
                            Parsed ({counts.parsed})
                        </Link>
                    </ToggleGroupItem>
                    <ToggleGroupItem value="archived" asChild>
                        <Link href={index({ query: { status: 'archived' } }).url} preserveState>
                            <Archive className="mr-1 h-3 w-3" />
                            Archived ({counts.archived})
                        </Link>
                    </ToggleGroupItem>
                </ToggleGroup>

                <div className="flex flex-col gap-3">
                    {items.data.length === 0 ? (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                                <Mail className="mb-4 h-12 w-12 text-muted-foreground/50" />
                                <h3 className="text-lg font-medium">No items yet</h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Create a manual entry or forward emails to get started.
                                </p>
                                <Button className="mt-4" asChild>
                                    <Link href={create().url}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Create Entry
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ) : (
                        items.data.map((item) => <InboxItemCard key={item.id} item={item} />)
                    )}
                </div>

                {items.meta.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {items.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={items.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="text-sm text-muted-foreground">
                            Page {items.meta.current_page} of {items.meta.last_page}
                        </span>
                        {items.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={items.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
