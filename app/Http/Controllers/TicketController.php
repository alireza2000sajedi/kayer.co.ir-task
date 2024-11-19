<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketPostRequest;
use App\Http\Resources\TicketResource;
use App\Repositories\TicketRepository;
use Ars\Responder\Facades\Responder;

class TicketController extends Controller
{
    public TicketRepository $repository;

    public function __construct(TicketRepository $ticket)
    {
        $this->repository = $ticket;
    }

    public function index()
    {
        $data = $this->repository->where('user_id', auth()->id())->paginate();
        $data = TicketResource::collection($data);

        return Responder::paginate($data);
    }

    public function show($ticketId)
    {
        $data = $this->repository->where('user_id', auth()->id())->where('id', $ticketId)
            ->with([
                'user',
                'replies' => function ($query) {
                    $query->with('user');
                }
            ])->firstOrFail();
        $data = TicketResource::make($data);

        return Responder::ok($data);
    }

    public function store(TicketPostRequest $request)
    {
        $payload = $request->validated();
        $payload['user_id'] = auth()->id();

        $result = $this->repository->create($payload);
        $result = TicketResource::make($result);

        return Responder::created($result);
    }
}
