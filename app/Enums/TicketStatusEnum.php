<?php

namespace App\Enums;

enum TicketStatusEnum: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Closed = 'closed';
}
