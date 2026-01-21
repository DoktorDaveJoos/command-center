import { Form, Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/inbox';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inbox',
        href: index().url,
    },
    {
        title: 'New Entry',
        href: '#',
    },
];

export default function InboxCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Inbox Entry" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">New Entry</h1>
                    <p className="text-sm text-muted-foreground">Manually add content to your inbox</p>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Create Entry</CardTitle>
                        <CardDescription>
                            Add notes, copy text, or paste content you want to process.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...store.form()} className="flex flex-col gap-6">
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="raw_subject">Subject (optional)</Label>
                                        <Input
                                            id="raw_subject"
                                            name="raw_subject"
                                            placeholder="Brief description or title"
                                        />
                                        <InputError message={errors.raw_subject} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="raw_content">Content</Label>
                                        <textarea
                                            id="raw_content"
                                            name="raw_content"
                                            required
                                            rows={8}
                                            className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            placeholder="Paste or type the content you want to process..."
                                        />
                                        <InputError message={errors.raw_content} />
                                        <p className="text-xs text-muted-foreground">
                                            This can be an email, a note, a reminder, or any text you want AI to extract
                                            actionable items from.
                                        </p>
                                    </div>

                                    <div className="flex gap-3">
                                        <Button type="submit" disabled={processing}>
                                            {processing && <Spinner className="mr-2" />}
                                            Create Entry
                                        </Button>
                                        <Button type="button" variant="outline" asChild>
                                            <Link href={index().url}>Cancel</Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
