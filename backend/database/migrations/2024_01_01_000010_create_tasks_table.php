<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            /*
             * status uses a MySQL ENUM column rather than a separate lookup table
             * because the set of values is small, fixed, and known at design time.
             * A string column with a CHECK constraint would be equally valid in
             * PostgreSQL, but ENUM integrates better with MySQL 8's optimizer.
             */
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');

            /*
             * cascadeOnDelete: when a user (assignee) is deleted, all tasks
             * assigned to them are also deleted. This avoids orphaned rows and
             * keeps referential integrity without nullable FK tricks.
             */
            $table->foreignId('assigned_to')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('due_date')->nullable();
            $table->timestamps();

            /*
             * Composite index on (assigned_to, status):
             * The most common query is "tasks for user X filtered by status Y".
             * A composite index on (assigned_to, status) lets MySQL satisfy both
             * the WHERE clause columns with a single index range scan,
             * avoiding a full table scan on the outer rows.
             */
            $table->index(['assigned_to', 'status'], 'tasks_assigned_status_idx');

            /*
             * Index on due_date:
             * Enables efficient ORDER BY / WHERE due_date range queries
             * (e.g. "overdue tasks", future sorting features).
             */
            $table->index('due_date', 'tasks_due_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
