<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create chat_sessions table
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        // 2. Add chat_session_id to chat_histories table
        Schema::table('chat_histories', function (Blueprint $table) {
            $table->foreignId('chat_session_id')
                ->nullable()
                ->after('user_id')
                ->constrained('chat_sessions')
                ->onDelete('cascade');
        });

        // 3. Migrate existing chat histories
        $this->migrateExistingHistories();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_histories', function (Blueprint $table) {
            $table->dropForeign(['chat_session_id']);
            $table->dropColumn('chat_session_id');
        });

        Schema::dropIfExists('chat_sessions');
    }

    /**
     * Group existing chat history by user, create a default session for each,
     * and associate the chat history records with that session.
     */
    private function migrateExistingHistories(): void
    {
        $userIds = DB::table('chat_histories')
            ->distinct()
            ->pluck('user_id');

        $now = now();

        foreach ($userIds as $userId) {
            // Create a default session for this user
            $sessionId = DB::table('chat_sessions')->insertGetId([
                'user_id' => $userId,
                'title' => 'Archived Session',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Update all histories for this user to point to the new session
            DB::table('chat_histories')
                ->where('user_id', $userId)
                ->whereNull('chat_session_id')
                ->update(['chat_session_id' => $sessionId]);
        }
    }
};
