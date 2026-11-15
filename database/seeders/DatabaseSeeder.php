<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categories = Category::factory(3)->for($user)->create();

        Task::factory(10)
            ->for($user)
            ->state(fn () => ['category_id' => $categories->random()->id])
            ->create();
    }
}
