<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_it_has_the_expected_values(): void
    {
        $this->assertSame(
            ['pending', 'in_progress', 'done'],
            array_map(fn (TaskStatus $status) => $status->value, TaskStatus::cases())
        );
    }

    public function test_it_can_be_created_from_a_string(): void
    {
        $this->assertSame(TaskStatus::Done, TaskStatus::from('done'));
        $this->assertNull(TaskStatus::tryFrom('invalid'));
    }
}
