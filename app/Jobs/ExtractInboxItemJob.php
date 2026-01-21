<?php

namespace App\Jobs;

use App\Models\InboxItem;
use App\Services\ExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExtractInboxItemJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public InboxItem $inboxItem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExtractionService $extractionService): void
    {
        $extractionService->extract($this->inboxItem);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Extraction failed for inbox item', [
            'inbox_item_id' => $this->inboxItem->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
