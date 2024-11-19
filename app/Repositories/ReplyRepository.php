<?php

namespace App\Repositories;

use App\Models\Reply;
use App\Repositories\Src\BaseRepository;

class ReplyRepository extends BaseRepository
{
    public function model()
    {
        return Reply::class;
    }
}
