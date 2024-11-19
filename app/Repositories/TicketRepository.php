<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Repositories\Src\BaseRepository;
use App\Repositories\Src\FilterRepository;

class TicketRepository extends BaseRepository
{
    use FilterRepository;

    public function model()
    {
        return Ticket::class;
    }

    public function defineFilterFields(): mixed
    {
        return [
            'status' => 'status'
        ];
    }
}
