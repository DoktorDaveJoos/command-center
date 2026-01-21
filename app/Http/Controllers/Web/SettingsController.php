<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace();
        $inboundDomain = config('services.inbound_domain');

        return Inertia::render('settings/workspace', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'created_at' => $workspace->created_at,
            ],
            'inboundEmail' => "inbox+{$workspace->inbound_email_token}@{$inboundDomain}",
        ]);
    }
}
