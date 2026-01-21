<?php

namespace App\Http\Requests;

use App\Enums\InboxItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInboxItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inboxItem = $this->route('inbox_item');

        return $inboxItem && $this->user()?->workspaces()
            ->where('workspaces.id', $inboxItem->workspace_id)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(InboxItemStatus::class)],
        ];
    }
}
