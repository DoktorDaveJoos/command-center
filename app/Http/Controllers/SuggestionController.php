<?php

namespace App\Http\Controllers;

use App\Enums\SuggestionStatus;
use App\Http\Resources\SuggestionResource;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SuggestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Suggestion::class);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $workspace = $user->currentWorkspace();

        $query = Suggestion::query()
            ->forWorkspace($workspace->id)
            ->with('extraction.inboxItem')
            ->latest();

        // Filter by status if provided
        if ($request->has('status')) {
            $status = SuggestionStatus::tryFrom($request->input('status'));
            if ($status) {
                $query->where('status', $status);
            }
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $suggestions = $query->paginate(20);

        return SuggestionResource::collection($suggestions);
    }

    /**
     * Display the specified resource.
     */
    public function show(Suggestion $suggestion): SuggestionResource
    {
        $this->authorize('view', $suggestion);

        $suggestion->load('extraction.inboxItem');

        return new SuggestionResource($suggestion);
    }

    /**
     * Accept a suggestion.
     */
    public function accept(Suggestion $suggestion): SuggestionResource
    {
        $this->authorize('update', $suggestion);

        $suggestion->accept();

        return new SuggestionResource($suggestion);
    }

    /**
     * Reject a suggestion.
     */
    public function reject(Suggestion $suggestion): SuggestionResource
    {
        $this->authorize('update', $suggestion);

        $suggestion->reject();

        return new SuggestionResource($suggestion);
    }
}
