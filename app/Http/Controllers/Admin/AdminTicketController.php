<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminTicketChangeStatusPostRequest;
use App\Http\Requests\TicketPostRequest;
use App\Http\Resources\TicketResource;
use App\Repositories\TicketRepository;
use Ars\Responder\Facades\Responder;

class AdminTicketController extends Controller
{
    public TicketRepository $repository;

    public function __construct(TicketRepository $ticket)
    {
        $this->repository = $ticket;
    }

    public function index()
    {
        $data = $this->repository->paginate();
        $data = TicketResource::collection($data);

        return Responder::paginate($data);
    }

    public function show($ticketId)
    {
        $data = $this->repository ->where('id', $ticketId)
            ->with([
                'user',
                'replies' => function ($query) {
                    $query->with('user');
                }
            ])->firstOrFail();
        $data = TicketResource::make($data);

        return Responder::ok($data);
    }

    public function changeStatus(AdminTicketChangeStatusPostRequest $request, $ticketId)
    {
        $this->repository->updateById($ticketId, $request->validated());

        $data = $this->repository->find($ticketId);

        return Responder::ok($data);
    }
}
