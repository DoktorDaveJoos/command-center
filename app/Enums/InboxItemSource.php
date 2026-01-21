<?php

namespace App\Enums;

enum InboxItemSource: string
{
    case Email = 'email';
    case Manual = 'manual';
    case Share = 'share';
}
