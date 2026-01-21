<?php

namespace App\Enums;

enum SuggestionType: string
{
    case Event = 'event';
    case Reminder = 'reminder';
    case Task = 'task';
}
