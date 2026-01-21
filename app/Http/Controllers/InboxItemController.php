<?php

namespace App\Http\Controllers;

use App\Enums\InboxItemSource;
use App\Enums\InboxItemStatus;
use App\Http\Requests\StoreInboxItemRequest;
use App\Http\Requests\UpdateInboxItemRequest;
use App\Http\Resources\InboxItemResource;
use App\Jobs\ExtractInboxItemJob;
use App\Models\InboxItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InboxItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InboxItem::class);

        $workspace = $request->user()->currentWorkspace();

        $query = InboxItem::query()
            ->forWorkspace($workspace->id)
            ->orderBy('received_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return InboxItemResource::collection($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInboxItemRequest $request): InboxItemResource
    {
        $workspace = $request->user()->currentWorkspace();

        $inboxItem = InboxItem::create([
            'workspace_id' => $workspace->id,
            'source' => InboxItemSource::Manual,
            'raw_subject' => $request->validated('raw_subject'),
            'raw_content' => $request->validated('raw_content'),
            'received_at' => now(),
            'status' => InboxItemStatus::New,
        ]);

        return new InboxItemResource($inboxItem);
    }

    /**
     * Display the specified resource.
     */
    public function show(InboxItem $inboxItem): InboxItemResource
    {
        $this->authorize('view', $inboxItem);

        if (class_exists(\App\Models\Extraction::class)) {
            $inboxItem->load(['extractions', 'suggestions']);
        }

        return new InboxItemResource($inboxItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInboxItemRequest $request, InboxItem $inboxItem): InboxItemResource
    {
        $inboxItem->update([
            'status' => $request->validated('status'),
        ]);

        return new InboxItemResource($inboxItem);
    }

    /**
     * Trigger extraction for an inbox item.
     */
    public function extract(InboxItem $inboxItem): JsonResponse
    {
        $this->authorize('update', $inboxItem);

        ExtractInboxItemJob::dispatch($inboxItem);

        return response()->json([
            'message' => 'Extraction job has been queued.',
        ], 202);
    }
}
