<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('client_id')->index();
            $table->string('client_alias');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'client_id']);
        });

        // Migrate existing distinct clients from client_cases table
        if (Schema::hasTable('client_cases')) {
            $existingClients = DB::table('client_cases')
                ->whereNotNull('client_id')
                ->where('client_id', '!=', '')
                ->select(
                    'user_id',
                    'client_id',
                    DB::raw('MAX(client_alias) as client_alias'),
                    DB::raw('MIN(created_at) as created_at'),
                    DB::raw('MAX(updated_at) as updated_at')
                )
                ->groupBy('user_id', 'client_id')
                ->get();

            foreach ($existingClients as $client) {
                DB::table('clients')->updateOrInsert(
                    ['user_id' => $client->user_id, 'client_id' => $client->client_id],
                    [
                        'client_alias' => $client->client_alias ?? $client->client_id,
                        'created_at'   => $client->created_at ?? now(),
                        'updated_at'   => $client->updated_at ?? now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
