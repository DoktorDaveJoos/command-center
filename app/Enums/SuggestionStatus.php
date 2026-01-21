<?php

namespace App\Enums;

enum SuggestionStatus: string
{
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
