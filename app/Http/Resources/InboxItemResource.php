<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InboxItem
 */
class InboxItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'source' => $this->source->value,
            'raw_subject' => $this->raw_subject,
            'raw_content' => $this->raw_content,
            'received_at' => $this->received_at->toIso8601String(),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'extractions' => ExtractionResource::collection($this->whenLoaded('extractions')),
            'suggestions' => SuggestionResource::collection($this->whenLoaded('suggestions')),
        ];
    }
}
