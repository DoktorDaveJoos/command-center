<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Extraction
 */
class ExtractionResource extends JsonResource
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
            'inbox_item_id' => $this->inbox_item_id,
            'model_version' => $this->model_version,
            'prompt_version' => $this->prompt_version,
            'raw_response' => $this->raw_response,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'suggestions' => SuggestionResource::collection($this->whenLoaded('suggestions')),
        ];
    }
}
