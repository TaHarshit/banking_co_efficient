<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL-only: SQLite does not support ALTER TABLE ... MODIFY COLUMN with ENUM
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'rejected', 'deleted') NOT NULL DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MySQL-only: SQLite does not support ALTER TABLE ... MODIFY COLUMN with ENUM
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'active'");
        }
    }
};
