<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $subjects = DB::table('subjects')->whereNotNull('enrollment_token')->get();

        foreach ($subjects as $subject) {
            if (strlen($subject->enrollment_token) === 36) {
                do {
                    $newToken = Str::random(8);
                } while (DB::table('subjects')->where('enrollment_token', $newToken)->exists());

                DB::table('subjects')
                    ->where('id', $subject->id)
                    ->update(['enrollment_token' => $newToken]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed for rollback
    }
};
