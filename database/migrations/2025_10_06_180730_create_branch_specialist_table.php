<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_specialist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('specialist_id')->constrained('specialists')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['branch_id', 'specialist_id']);
        });

        DB::table('specialists')->select('id', 'branch_id')->chunkById(200, function ($specialists) {
            $now = now();
            $pivotRows = [];

            foreach ($specialists as $specialist) {
                if (! $specialist->branch_id) {
                    continue;
                }

                $pivotRows[] = [
                    'branch_id' => $specialist->branch_id,
                    'specialist_id' => $specialist->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($pivotRows)) {
                DB::table('branch_specialist')->upsert($pivotRows, ['branch_id', 'specialist_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_specialist');
    }
};
