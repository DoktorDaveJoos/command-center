import { Head, Link, router } from '@inertiajs/react';
import { Archive, ArrowLeft, Mail, Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { extract } from '@/routes/inbox-items';
import { index, show } from '@/routes/inbox';
import { index as suggestionsIndex } from '@/routes/suggestions';
import { type BreadcrumbItem } from '@/types';

interface Suggestion {
    id: number;
    type: string;
    payload: Record<string, unknown>;
    status: string;
}

interface Extraction {
    id: number;
    model_version: string;
    prompt_version: string;
    created_at: string;
    suggestions: Suggestion[];
}

interface InboxItem {
    id: number;
    source: string;
    raw_subject: string | null;
    raw_content: string;
    status: string;
    received_at: string;
    created_at: string;
    extractions: Extraction[];
    latest_extraction: Extraction | null;
}

interface PageProps {
    item: InboxItem;
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

function SuggestionTypeBadge({ type }: { type: string }) {
    const colors: Record<string, string> = {
        event: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        reminder: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        task: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    };

    return (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ${colors[type] || ''}`}>
            {type}
        </span>
    );
}

export default function InboxShow({ item }: PageProps) {
    const [extracting, setExtracting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Inbox',
            href: index().url,
        },
        {
            title: item.raw_subject || 'Item Details',
            href: show(item.id).url,
        },
    ];

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleExtract = () => {
        setExtracting(true);
        router.post(
            extract(item.id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setExtracting(false);
                    router.reload();
                },
            }
        );
    };

    const allSuggestions = item.extractions.flatMap((e) => e.suggestions);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={item.raw_subject || 'Inbox Item'} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {item.raw_subject || '(No subject)'}
                        </h1>
                        <div className="mt-1 flex items-center gap-2">
                            <StatusBadge status={item.status} />
                            <SourceBadge source={item.source} />
                            <span className="text-sm text-muted-foreground">
                                Received {formatDate(item.received_at)}
                            </span>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {item.status === 'new' && (
                            <Button onClick={handleExtract} disabled={extracting}>
                                {extracting ? (
                                    <>
                                        <Spinner className="mr-2" />
                                        Extracting...
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="mr-2 h-4 w-4" />
                                        Run Extraction
                                    </>
                                )}
                            </Button>
                        )}
                        {item.status !== 'archived' && (
                            <Button variant="outline">
                                <Archive className="mr-2 h-4 w-4" />
                                Archive
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Content</CardTitle>
                                <CardDescription>Original content received</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="whitespace-pre-wrap rounded-lg bg-muted/50 p-4 text-sm">
                                    {item.raw_content}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Suggestions</CardTitle>
                                <CardDescription>
                                    {allSuggestions.length > 0
                                        ? `${allSuggestions.length} items extracted`
                                        : 'Run extraction to generate suggestions'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {allSuggestions.length === 0 ? (
                                    <div className="flex flex-col items-center py-6 text-center">
                                        <Sparkles className="mb-2 h-8 w-8 text-muted-foreground/50" />
                                        <p className="text-sm text-muted-foreground">
                                            No suggestions yet
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {allSuggestions.slice(0, 5).map((suggestion) => (
                                            <div
                                                key={suggestion.id}
                                                className="flex items-start gap-3 rounded-lg border p-3"
                                            >
                                                <SuggestionTypeBadge type={suggestion.type} />
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">
                                                        {String(
                                                            suggestion.payload.title ||
                                                                suggestion.payload.message ||
                                                                'Untitled'
                                                        )}
                                                    </p>
                                                    <p className="text-xs capitalize text-muted-foreground">
                                                        {suggestion.status}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                        {allSuggestions.length > 5 && (
                                            <Button variant="link" className="w-full" asChild>
                                                <Link href={suggestionsIndex().url}>
                                                    View all {allSuggestions.length} suggestions
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {item.extractions.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Extraction History</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {item.extractions.map((extraction, idx) => (
                                            <div key={extraction.id}>
                                                {idx > 0 && <Separator className="my-3" />}
                                                <div className="text-sm">
                                                    <p className="font-medium">
                                                        {extraction.model_version}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatDate(extraction.created_at)}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {extraction.suggestions.length} suggestions
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
