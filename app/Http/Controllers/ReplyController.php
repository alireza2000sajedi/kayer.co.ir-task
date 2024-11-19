<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplyPostRequest;
use App\Http\Resources\ReplyResource;
use App\Models\Ticket;
use App\Repositories\ReplyRepository;
use Ars\Responder\Facades\Responder;
use Illuminate\Support\Facades\Gate;

class ReplyController extends Controller
{
    public ReplyRepository $repository;

    public function __construct(ReplyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function store(ReplyPostRequest $request, Ticket $ticket)
    {
        Gate::authorize('create', $ticket);

        $payload = $request->validated();
        $payload['ticket_id'] = $ticket->id;
        $payload['user_id'] = auth()->id();

        $result = $this->repository->create($payload);
        $result = ReplyResource::make($result);

        return Responder::created($result);
    }
}
