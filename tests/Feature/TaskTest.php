<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tasks(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_index_returns_only_own_tasks_with_pagination(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->for($user)->create();
        Task::factory()->count(2)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_user_can_create_task_with_default_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', ['title' => 'Write tests'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Write tests')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('tasks', ['title' => 'Write tests']);
    }

    public function test_title_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_cannot_use_another_users_category(): void
    {
        $foreign = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', ['title' => 'X', 'category_id' => $foreign->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_user_cannot_view_another_users_task(): void
    {
        $task = Task::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/tasks/{$task->id}")->assertForbidden();
    }

    public function test_user_can_update_task_status(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['status' => 'pending']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $task = Task::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'done'])->assertForbidden();
    }

    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_can_filter_by_status(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['status' => 'done']);
        Task::factory()->for($user)->create(['status' => 'pending']);
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks?status=done')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'done');
    }

    public function test_can_search_by_title(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['title' => 'Prepare interview', 'description' => null]);
        Task::factory()->for($user)->create(['title' => 'Buy milk', 'description' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks?search=interview')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_does_not_leak_other_users_tasks(): void
    {
        Task::factory()->create(['title' => 'secret report', 'description' => null]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/tasks?search=secret')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_invalid_sort_column_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/tasks?sort=password')->assertUnprocessable();
    }
}
