<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generate business_code for existing businesses that don't have one
        $businesses = DB::table('businesses')->whereNull('business_code')->orWhere('business_code', '')->get();

        foreach ($businesses as $business) {
            $code = 'BUS-' . strtoupper(Str::random(6));

            // Ensure unique
            while (DB::table('businesses')->where('business_code', $code)->exists()) {
                $code = 'BUS-' . strtoupper(Str::random(6));
            }

            DB::table('businesses')->where('id', $business->id)->update([
                'business_code' => $code
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to remove codes on down
    }
};
