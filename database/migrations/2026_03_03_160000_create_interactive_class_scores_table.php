<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interactive_class_scores')) {
            $this->ensureMissingIndexes();

            return;
        }

        Schema::create('interactive_class_scores', function (Blueprint $table) {
            $table->id();
            $table->string('srn', 50)->nullable();
            $table->string('srn_normalized', 50)->nullable();
            $table->string('name')->nullable();
            $table->string('name_normalized')->nullable();
            $table->string('study_program')->nullable();
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->decimal('score', 5, 2);
            $table->string('grade', 10)->nullable();
            $table->string('source_file')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['srn_normalized', 'semester'], 'ics_srn_semester_idx');
            $table->index(['name_normalized', 'source_year', 'semester'], 'ics_name_year_sem_idx');
            $table->index('srn_normalized', 'ics_srn_idx');
            $table->index('name_normalized', 'ics_name_idx');
            $table->index('semester', 'ics_semester_idx');
            $table->index('source_year', 'ics_year_idx');
            $table->index('grade', 'ics_grade_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactive_class_scores');
    }

    private function ensureMissingIndexes(): void
    {
        $existing = collect(DB::select('SHOW INDEX FROM interactive_class_scores'))
            ->pluck('Key_name')
            ->unique()
            ->flip();

        Schema::table('interactive_class_scores', function (Blueprint $table) use ($existing) {
            if (! $existing->has('ics_srn_semester_idx') && ! $existing->has('interactive_class_scores_srn_normalized_semester_index')) {
                $table->index(['srn_normalized', 'semester'], 'ics_srn_semester_idx');
            }

            if (! $existing->has('ics_name_year_sem_idx')) {
                $table->index(['name_normalized', 'source_year', 'semester'], 'ics_name_year_sem_idx');
            }

            if (! $existing->has('ics_srn_idx') && ! $existing->has('interactive_class_scores_srn_normalized_index')) {
                $table->index('srn_normalized', 'ics_srn_idx');
            }

            if (! $existing->has('ics_name_idx') && ! $existing->has('interactive_class_scores_name_normalized_index')) {
                $table->index('name_normalized', 'ics_name_idx');
            }

            if (! $existing->has('ics_semester_idx') && ! $existing->has('interactive_class_scores_semester_index')) {
                $table->index('semester', 'ics_semester_idx');
            }

            if (! $existing->has('ics_year_idx') && ! $existing->has('interactive_class_scores_source_year_index')) {
                $table->index('source_year', 'ics_year_idx');
            }

            if (! $existing->has('ics_grade_idx') && ! $existing->has('interactive_class_scores_grade_index')) {
                $table->index('grade', 'ics_grade_idx');
            }
        });
    }
};
