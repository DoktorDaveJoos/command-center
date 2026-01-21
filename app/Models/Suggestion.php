<?php

namespace App\Models;

use App\Enums\SuggestionStatus;
use App\Enums\SuggestionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    /** @use HasFactory<\Database\Factories\SuggestionFactory> */
    use HasFactory;

    protected $fillable = [
        'extraction_id',
        'type',
        'payload',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SuggestionType::class,
            'payload' => 'array',
            'status' => SuggestionStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Extraction, $this>
     */
    public function extraction(): BelongsTo
    {
        return $this->belongsTo(Extraction::class);
    }

    /**
     * Get the inbox item through the extraction.
     */
    public function inboxItem(): ?InboxItem
    {
        return $this->extraction?->inboxItem;
    }

    /**
     * @param  Builder<Suggestion>  $query
     * @return Builder<Suggestion>
     */
    public function scopeProposed(Builder $query): Builder
    {
        return $query->where('status', SuggestionStatus::Proposed);
    }

    /**
     * @param  Builder<Suggestion>  $query
     * @return Builder<Suggestion>
     */
    public function scopeForWorkspace(Builder $query, int $workspaceId): Builder
    {
        return $query->whereHas('extraction.inboxItem', function (Builder $q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        });
    }

    /**
     * Accept this suggestion.
     */
    public function accept(): bool
    {
        $this->status = SuggestionStatus::Accepted;

        return $this->save();
    }

    /**
     * Reject this suggestion.
     */
    public function reject(): bool
    {
        $this->status = SuggestionStatus::Rejected;

        return $this->save();
    }
}
