<?php

namespace App\Enums;

enum InboxItemStatus: string
{
    case New = 'new';
    case Parsed = 'parsed';
    case Archived = 'archived';
}
