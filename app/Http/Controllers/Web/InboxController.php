<?php

namespace App\Http\Controllers\Web;

use App\Enums\InboxItemSource;
use App\Enums\InboxItemStatus;
use App\Http\Controllers\Controller;
use App\Models\InboxItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace();

        $query = InboxItem::query()
            ->forWorkspace($workspace->id)
            ->orderBy('received_at', 'desc');

        $status = $request->input('status');
        if ($status) {
            $query->where('status', $status);
        }

        $items = $query->paginate(20);

        // Get counts by status for tabs
        $counts = [
            'all' => InboxItem::forWorkspace($workspace->id)->count(),
            'new' => InboxItem::forWorkspace($workspace->id)->where('status', InboxItemStatus::New)->count(),
            'parsed' => InboxItem::forWorkspace($workspace->id)->where('status', InboxItemStatus::Parsed)->count(),
            'archived' => InboxItem::forWorkspace($workspace->id)->where('status', InboxItemStatus::Archived)->count(),
        ];

        return Inertia::render('inbox/index', [
            'items' => $items,
            'counts' => $counts,
            'currentStatus' => $status,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inbox/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'raw_subject' => 'nullable|string|max:255',
            'raw_content' => 'required|string',
        ]);

        $workspace = $request->user()->currentWorkspace();

        $inboxItem = InboxItem::create([
            'workspace_id' => $workspace->id,
            'source' => InboxItemSource::Manual,
            'raw_subject' => $validated['raw_subject'] ?? null,
            'raw_content' => $validated['raw_content'],
            'received_at' => now(),
            'status' => InboxItemStatus::New,
        ]);

        return redirect()->route('inbox.show', $inboxItem);
    }

    public function show(InboxItem $inboxItem): Response
    {
        $this->authorize('view', $inboxItem);

        $inboxItem->load(['extractions.suggestions', 'latestExtraction']);

        return Inertia::render('inbox/show', [
            'item' => $inboxItem,
        ]);
    }
}
