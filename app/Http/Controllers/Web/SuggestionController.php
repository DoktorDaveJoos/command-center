<?php

namespace App\Http\Controllers\Web;

use App\Enums\SuggestionStatus;
use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuggestionController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace();

        $query = Suggestion::query()
            ->forWorkspace($workspace->id)
            ->with('extraction.inboxItem')
            ->latest();

        // Filter by status
        $status = $request->input('status');
        if ($status) {
            $statusEnum = SuggestionStatus::tryFrom($status);
            if ($statusEnum) {
                $query->where('status', $statusEnum);
            }
        }

        // Filter by type
        $type = $request->input('type');
        if ($type) {
            $query->where('type', $type);
        }

        $suggestions = $query->paginate(20);

        // Get counts by status for tabs
        $counts = [
            'all' => Suggestion::forWorkspace($workspace->id)->count(),
            'proposed' => Suggestion::forWorkspace($workspace->id)->proposed()->count(),
            'accepted' => Suggestion::forWorkspace($workspace->id)->where('status', SuggestionStatus::Accepted)->count(),
            'rejected' => Suggestion::forWorkspace($workspace->id)->where('status', SuggestionStatus::Rejected)->count(),
        ];

        return Inertia::render('suggestions/index', [
            'suggestions' => $suggestions,
            'counts' => $counts,
            'currentStatus' => $status,
            'currentType' => $type,
        ]);
    }
}
