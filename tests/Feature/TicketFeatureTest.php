<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_user_can_create_a_ticket(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/tickets', [
            'title'       => 'Test Ticket',
            'description' => 'This is a test ticket.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'title', 'description', 'status', 'created_at']
        ]);
    }

    public function test_admin_can_change_ticket_status(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->admin)->postJson("/api/admin/tickets/{$ticket->id}/change-status", [
            'status' => 'closed',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['data' => ['status' => 'closed']]);
    }

    public function test_non_admin_users_cannot_change_ticket_status(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->user)->postJson("/api/admin/tickets/{$ticket->id}/change-status", [
            'status' => 'closed',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_reply_to_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($ticket->user)->postJson("/api/tickets/{$ticket->id}/reply", [
            'message' => 'This is a user reply.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'message', 'ticket_id', 'created_at']
        ]);
    }

    public function test_admin_can_reply_to_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($this->admin)->postJson("/api/admin/tickets/{$ticket->id}/reply", [
            'message' => 'This is an admin reply.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'message', 'ticket_id', 'created_at']
        ]);
    }

    public function test_user_can_view_their_own_tickets(): void
    {
        Ticket::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/tickets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => ['id', 'title', 'description', 'status', 'created_at']]
        ]);
    }

    public function test_admin_can_view_all_tickets(): void
    {
        Ticket::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/admin/tickets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => ['id', 'title', 'description', 'status', 'created_at']]
        ]);
    }
}
