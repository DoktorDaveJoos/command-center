<?php

namespace App\Services;

use App\Enums\SuggestionStatus;
use App\Enums\SuggestionType;
use App\Models\Extraction;
use App\Models\InboxItem;
use App\Models\Suggestion;
use Illuminate\Support\Facades\DB;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class ExtractionService
{
    private const MODEL_VERSION = 'gpt-4o-mini';

    private const PROMPT_VERSION = 'v1.0.0';

    public function extract(InboxItem $inboxItem): Extraction
    {
        $prompt = $this->buildPrompt($inboxItem);
        $schema = $this->buildSchema();

        $response = Prism::structured()
            ->using('openai', self::MODEL_VERSION)
            ->withSystemPrompt($this->getSystemPrompt())
            ->withPrompt($prompt)
            ->withSchema($schema)
            ->asStructured();

        return DB::transaction(function () use ($inboxItem, $response) {
            $extraction = Extraction::create([
                'inbox_item_id' => $inboxItem->id,
                'model_version' => self::MODEL_VERSION,
                'prompt_version' => self::PROMPT_VERSION,
                'raw_response' => $response->structured,
            ]);

            $this->createSuggestionsFromResponse($extraction, $response->structured);

            $inboxItem->markAsParsed();

            return $extraction;
        });
    }

    private function buildPrompt(InboxItem $inboxItem): string
    {
        $subject = $inboxItem->raw_subject ?? '(No subject)';
        $content = $inboxItem->raw_content;
        $timezone = config('app.timezone', 'UTC');
        $locale = config('app.locale', 'en');
        $currentDate = now()->toDateString();

        return <<<PROMPT
        Subject: {$subject}

        Content:
        {$content}

        ---
        Timezone: {$timezone}
        Locale: {$locale}
        Current Date: {$currentDate}
        PROMPT;
    }

    private function getSystemPrompt(): string
    {
        return <<<'SYSTEM'
        You are an AI assistant that extracts actionable items from unstructured text content such as emails, notes, and messages.

        ## Your Task

        Analyze the provided content and extract:
        1. **Events** - Calendar events with dates, times, and locations
        2. **Reminders** - Things the user should be reminded about
        3. **Tasks** - Action items or to-dos

        ## Rules

        1. **Only extract what is explicitly mentioned** - Do not hallucinate or infer dates/times that aren't present
        2. **Partial success is acceptable** - If only some items can be extracted, that's fine
        3. **Be conservative** - When in doubt, don't extract
        4. **Use ISO 8601 format for dates** - YYYY-MM-DD for dates, YYYY-MM-DDTHH:MM:SS for datetimes
        5. **Preserve context** - Include relevant details in titles and descriptions
        SYSTEM;
    }

    private function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'extraction_result',
            description: 'Extracted actionable items from the content',
            properties: [
                new ArraySchema(
                    name: 'events',
                    description: 'Calendar events extracted from the content',
                    items: new ObjectSchema(
                        name: 'event',
                        description: 'A calendar event',
                        properties: [
                            new StringSchema(
                                name: 'title',
                                description: 'The title of the event',
                            ),
                            new StringSchema(
                                name: 'date',
                                description: 'The date of the event in YYYY-MM-DD format',
                            ),
                            new StringSchema(
                                name: 'time',
                                description: 'The time of the event in HH:MM format (24-hour)',
                                nullable: true,
                            ),
                            new StringSchema(
                                name: 'end_time',
                                description: 'The end time of the event in HH:MM format (24-hour)',
                                nullable: true,
                            ),
                            new StringSchema(
                                name: 'location',
                                description: 'The location of the event',
                                nullable: true,
                            ),
                        ],
                        requiredFields: ['title', 'date'],
                    ),
                ),
                new ArraySchema(
                    name: 'reminders',
                    description: 'Reminders extracted from the content',
                    items: new ObjectSchema(
                        name: 'reminder',
                        description: 'A reminder',
                        properties: [
                            new StringSchema(
                                name: 'message',
                                description: 'The reminder message',
                            ),
                            new StringSchema(
                                name: 'remind_at',
                                description: 'When to remind in ISO 8601 datetime format',
                                nullable: true,
                            ),
                            new StringSchema(
                                name: 'offset',
                                description: 'Relative time offset like "1 day before", "2 hours before"',
                                nullable: true,
                            ),
                        ],
                        requiredFields: ['message'],
                    ),
                ),
                new ArraySchema(
                    name: 'tasks',
                    description: 'Tasks or to-dos extracted from the content',
                    items: new ObjectSchema(
                        name: 'task',
                        description: 'A task or to-do item',
                        properties: [
                            new StringSchema(
                                name: 'title',
                                description: 'The title of the task',
                            ),
                            new StringSchema(
                                name: 'due_date',
                                description: 'The due date in YYYY-MM-DD format',
                                nullable: true,
                            ),
                            new StringSchema(
                                name: 'priority',
                                description: 'The priority: low, medium, or high',
                                nullable: true,
                            ),
                        ],
                        requiredFields: ['title'],
                    ),
                ),
            ],
            requiredFields: ['events', 'reminders', 'tasks'],
        );
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    private function createSuggestionsFromResponse(Extraction $extraction, ?array $response): void
    {
        if ($response === null) {
            return;
        }

        // Create event suggestions
        foreach ($response['events'] ?? [] as $event) {
            Suggestion::create([
                'extraction_id' => $extraction->id,
                'type' => SuggestionType::Event,
                'payload' => $event,
                'status' => SuggestionStatus::Proposed,
            ]);
        }

        // Create reminder suggestions
        foreach ($response['reminders'] ?? [] as $reminder) {
            Suggestion::create([
                'extraction_id' => $extraction->id,
                'type' => SuggestionType::Reminder,
                'payload' => $reminder,
                'status' => SuggestionStatus::Proposed,
            ]);
        }

        // Create task suggestions
        foreach ($response['tasks'] ?? [] as $task) {
            Suggestion::create([
                'extraction_id' => $extraction->id,
                'type' => SuggestionType::Task,
                'payload' => $task,
                'status' => SuggestionStatus::Proposed,
            ]);
        }
    }
}
