import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Check, CheckCircle, Clock, ListTodo, Lightbulb, X, XCircle } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import { accept, reject } from '@/routes/suggestions';
import { show as showInbox } from '@/routes/inbox';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Suggestions',
        href: '/suggestions',
    },
];

interface InboxItem {
    id: number;
    raw_subject: string | null;
}

interface Extraction {
    id: number;
    inbox_item: InboxItem;
}

interface Suggestion {
    id: number;
    type: string;
    payload: Record<string, unknown>;
    status: string;
    created_at: string;
    extraction: Extraction;
}

interface PageProps {
    suggestions: {
        data: Suggestion[];
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
        proposed: number;
        accepted: number;
        rejected: number;
    };
    currentStatus: string | null;
    currentType: string | null;
}

function TypeIcon({ type }: { type: string }) {
    const icons: Record<string, typeof Calendar> = {
        event: Calendar,
        reminder: Clock,
        task: ListTodo,
    };

    const Icon = icons[type] || Lightbulb;
    return <Icon className="h-5 w-5" />;
}

function StatusBadge({ status }: { status: string }) {
    const config: Record<string, { variant: 'default' | 'secondary' | 'outline' | 'destructive'; icon: typeof Check }> = {
        proposed: { variant: 'default', icon: Lightbulb },
        accepted: { variant: 'secondary', icon: CheckCircle },
        rejected: { variant: 'outline', icon: XCircle },
    };

    const { variant, icon: Icon } = config[status] || { variant: 'default', icon: Lightbulb };

    return (
        <Badge variant={variant} className="gap-1 capitalize">
            <Icon className="h-3 w-3" />
            {status}
        </Badge>
    );
}

function SuggestionCard({ suggestion, onAction }: { suggestion: Suggestion; onAction: (id: number, action: 'accept' | 'reject') => void }) {
    const [loading, setLoading] = useState<'accept' | 'reject' | null>(null);

    const typeColors: Record<string, string> = {
        event: 'border-l-blue-500',
        reminder: 'border-l-amber-500',
        task: 'border-l-green-500',
    };

    const formatPayload = () => {
        const payload = suggestion.payload;
        if (suggestion.type === 'event') {
            return {
                title: payload.title as string,
                details: [
                    payload.date && `Date: ${payload.date}`,
                    payload.time && `Time: ${payload.time}`,
                    payload.location && `Location: ${payload.location}`,
                ].filter(Boolean).join(' | '),
            };
        }
        if (suggestion.type === 'reminder') {
            return {
                title: payload.message as string,
                details: [
                    payload.remind_at && `Remind at: ${payload.remind_at}`,
                    payload.offset && `Offset: ${payload.offset}`,
                ].filter(Boolean).join(' | '),
            };
        }
        if (suggestion.type === 'task') {
            return {
                title: payload.title as string,
                details: [
                    payload.due_date && `Due: ${payload.due_date}`,
                    payload.priority && `Priority: ${payload.priority}`,
                ].filter(Boolean).join(' | '),
            };
        }
        return { title: 'Unknown', details: '' };
    };

    const handleAction = (action: 'accept' | 'reject') => {
        setLoading(action);
        const url = action === 'accept' ? accept(suggestion.id).url : reject(suggestion.id).url;
        router.post(url, {}, {
            preserveScroll: true,
            onFinish: () => {
                setLoading(null);
                onAction(suggestion.id, action);
            },
        });
    };

    const { title, details } = formatPayload();

    return (
        <Card className={`border-l-4 ${typeColors[suggestion.type] || ''} transition-all hover:shadow-md`}>
            <CardHeader className="pb-2">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5 rounded-lg bg-muted p-2">
                            <TypeIcon type={suggestion.type} />
                        </div>
                        <div>
                            <CardTitle className="text-base font-medium">{title}</CardTitle>
                            {details && (
                                <CardDescription className="mt-1">{details}</CardDescription>
                            )}
                        </div>
                    </div>
                    <StatusBadge status={suggestion.status} />
                </div>
            </CardHeader>
            <CardContent className="pt-0">
                <div className="flex items-center justify-between">
                    <Link
                        href={showInbox(suggestion.extraction.inbox_item.id).url}
                        className="text-xs text-muted-foreground hover:text-primary hover:underline"
                    >
                        From: {suggestion.extraction.inbox_item.raw_subject || '(No subject)'}
                    </Link>
                    {suggestion.status === 'proposed' && (
                        <div className="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleAction('reject')}
                                disabled={loading !== null}
                            >
                                {loading === 'reject' ? (
                                    <Spinner className="mr-1" />
                                ) : (
                                    <X className="mr-1 h-3 w-3" />
                                )}
                                Reject
                            </Button>
                            <Button
                                size="sm"
                                onClick={() => handleAction('accept')}
                                disabled={loading !== null}
                            >
                                {loading === 'accept' ? (
                                    <Spinner className="mr-1" />
                                ) : (
                                    <Check className="mr-1 h-3 w-3" />
                                )}
                                Accept
                            </Button>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function SuggestionsIndex({ suggestions, counts, currentStatus, currentType }: PageProps) {
    const buildFilterUrl = (status: string | null, type: string | null) => {
        const params = new URLSearchParams();
        if (status) params.set('status', status);
        if (type) params.set('type', type);
        const query = params.toString();
        return query ? `/suggestions?${query}` : '/suggestions';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Suggestions" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Suggestions</h1>
                    <p className="text-sm text-muted-foreground">Review and act on AI-extracted items</p>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <ToggleGroup
                        type="single"
                        value={currentStatus || 'all'}
                        className="justify-start"
                    >
                        <ToggleGroupItem value="all" asChild>
                            <Link href={buildFilterUrl(null, currentType)} preserveState>
                                All ({counts.all})
                            </Link>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="proposed" asChild>
                            <Link href={buildFilterUrl('proposed', currentType)} preserveState>
                                <Lightbulb className="mr-1 h-3 w-3" />
                                Proposed ({counts.proposed})
                            </Link>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="accepted" asChild>
                            <Link href={buildFilterUrl('accepted', currentType)} preserveState>
                                <CheckCircle className="mr-1 h-3 w-3" />
                                Accepted ({counts.accepted})
                            </Link>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="rejected" asChild>
                            <Link href={buildFilterUrl('rejected', currentType)} preserveState>
                                <XCircle className="mr-1 h-3 w-3" />
                                Rejected ({counts.rejected})
                            </Link>
                        </ToggleGroupItem>
                    </ToggleGroup>

                    <ToggleGroup
                        type="single"
                        value={currentType || 'all'}
                        className="justify-start"
                    >
                        <ToggleGroupItem value="all" asChild>
                            <Link href={buildFilterUrl(currentStatus, null)} preserveState>
                                All Types
                            </Link>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="event" asChild>
                            <Link href={buildFilterUrl(currentStatus, 'event')} preserveState>
                                <Calendar className="mr-1 h-3 w-3" />
                                Events
                            </Link>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="reminder" asChild>
                            <Link href={buildFilterUrl(currentStatus, 'reminder')} preserveState>
                                <Clock className="mr-1 h-3 w-3" />
                                Reminders
                            </Link>
                        </ToggleGroupItem>
                        <ToggleGroupItem value="task" asChild>
                            <Link href={buildFilterUrl(currentStatus, 'task')} preserveState>
                                <ListTodo className="mr-1 h-3 w-3" />
                                Tasks
                            </Link>
                        </ToggleGroupItem>
                    </ToggleGroup>
                </div>

                <div className="flex flex-col gap-3">
                    {suggestions.data.length === 0 ? (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                                <Lightbulb className="mb-4 h-12 w-12 text-muted-foreground/50" />
                                <h3 className="text-lg font-medium">No suggestions yet</h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Run extractions on your inbox items to generate suggestions.
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        suggestions.data.map((suggestion) => (
                            <SuggestionCard
                                key={suggestion.id}
                                suggestion={suggestion}
                                onAction={() => router.reload()}
                            />
                        ))
                    )}
                </div>

                {suggestions.meta.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {suggestions.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={suggestions.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="text-sm text-muted-foreground">
                            Page {suggestions.meta.current_page} of {suggestions.meta.last_page}
                        </span>
                        {suggestions.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={suggestions.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
