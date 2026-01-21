import { Head, Link, router } from '@inertiajs/react';
import { Check, Copy, Mail, Settings } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useClipboard } from '@/hooks/use-clipboard';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workspace',
        href: '/workspace',
    },
];

interface Workspace {
    id: number;
    name: string;
    created_at: string;
}

interface PageProps {
    workspace: Workspace;
    inboundEmail: string;
}

export default function WorkspaceSettings({ workspace, inboundEmail }: PageProps) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === inboundEmail;

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workspace Settings" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Workspace Settings</h1>
                    <p className="text-sm text-muted-foreground">Manage your workspace configuration</p>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Settings className="h-5 w-5" />
                                Workspace Information
                            </CardTitle>
                            <CardDescription>Basic details about your workspace</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Workspace Name</Label>
                                <Input value={workspace.name} readOnly className="bg-muted" />
                            </div>
                            <div className="space-y-2">
                                <Label>Created</Label>
                                <Input value={formatDate(workspace.created_at)} readOnly className="bg-muted" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="h-5 w-5" />
                                Inbound Email
                            </CardTitle>
                            <CardDescription>Forward emails to this address to add them to your inbox</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Your Inbound Email Address</Label>
                                <div className="flex gap-2">
                                    <Input
                                        value={inboundEmail}
                                        readOnly
                                        className="font-mono text-sm"
                                    />
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        onClick={() => copy(inboundEmail)}
                                    >
                                        {isCopied ? (
                                            <Check className="h-4 w-4 text-green-600" />
                                        ) : (
                                            <Copy className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                            </div>

                            <div className="rounded-lg bg-muted/50 p-4">
                                <h4 className="text-sm font-medium">How to use</h4>
                                <ol className="mt-2 list-inside list-decimal space-y-1 text-sm text-muted-foreground">
                                    <li>Copy the email address above</li>
                                    <li>Set up email forwarding from your email provider</li>
                                    <li>Forward newsletters, receipts, or any emails you want to process</li>
                                    <li>Items will appear in your Inbox automatically</li>
                                </ol>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Separator />

                <Card>
                    <CardHeader>
                        <CardTitle>Account</CardTitle>
                        <CardDescription>Manage your account settings</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <p className="text-sm font-medium">Profile & Security</p>
                            <p className="text-sm text-muted-foreground">
                                Update your profile, change password, or enable two-factor authentication
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href="/settings/profile">Account Settings</Link>
                        </Button>
                    </CardContent>
                    <Separator />
                    <CardContent className="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <p className="text-sm font-medium">Sign Out</p>
                            <p className="text-sm text-muted-foreground">Sign out of your account on this device</p>
                        </div>
                        <Button variant="outline" onClick={handleLogout}>
                            Sign Out
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
