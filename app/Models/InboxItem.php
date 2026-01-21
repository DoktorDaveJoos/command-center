<?php

namespace App\Models;

use App\Enums\InboxItemSource;
use App\Enums\InboxItemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InboxItem extends Model
{
    /** @use HasFactory<\Database\Factories\InboxItemFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'source',
        'raw_subject',
        'raw_content',
        'received_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => InboxItemSource::class,
            'status' => InboxItemStatus::class,
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<Extraction, $this>
     */
    public function extractions(): HasMany
    {
        return $this->hasMany(Extraction::class);
    }

    /**
     * @return HasOne<Extraction, $this>
     */
    public function latestExtraction(): HasOne
    {
        return $this->hasOne(Extraction::class)->latestOfMany();
    }

    /**
     * @return HasManyThrough<Suggestion, Extraction, $this>
     */
    public function suggestions(): HasManyThrough
    {
        return $this->hasManyThrough(Suggestion::class, Extraction::class);
    }

    /**
     * @param  Builder<InboxItem>  $query
     * @return Builder<InboxItem>
     */
    public function scopeForWorkspace(Builder $query, int $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function markAsParsed(): void
    {
        $this->update(['status' => InboxItemStatus::Parsed]);
    }

    public function archive(): void
    {
        $this->update(['status' => InboxItemStatus::Archived]);
    }
}
