<?php

use App\Models\InteractiveClassScore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interactive_class_scores')) {
            return;
        }

        if (! Schema::hasColumn('interactive_class_scores', 'track')) {
            Schema::table('interactive_class_scores', function (Blueprint $table) {
                $table->string('track', 20)
                    ->default(InteractiveClassScore::TRACK_ENGLISH)
                    ->after('study_program');
            });
        }

        DB::table('interactive_class_scores')
            ->whereNull('track')
            ->orWhere('track', '')
            ->update(['track' => InteractiveClassScore::TRACK_ENGLISH]);

        $existing = collect(DB::select('SHOW INDEX FROM interactive_class_scores'))
            ->pluck('Key_name')
            ->unique()
            ->flip();

        Schema::table('interactive_class_scores', function (Blueprint $table) use ($existing) {
            if (! $existing->has('ics_track_idx')) {
                $table->index('track', 'ics_track_idx');
            }

            if (! $existing->has('ics_track_srn_sem_idx')) {
                $table->index(['track', 'srn_normalized', 'semester'], 'ics_track_srn_sem_idx');
            }

            if (! $existing->has('ics_track_name_year_sem_idx')) {
                $table->index(['track', 'name_normalized', 'source_year', 'semester'], 'ics_track_name_year_sem_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interactive_class_scores')) {
            return;
        }

        $hasTrackColumn = Schema::hasColumn('interactive_class_scores', 'track');

        Schema::table('interactive_class_scores', function (Blueprint $table) {
            foreach (['ics_track_idx', 'ics_track_srn_sem_idx', 'ics_track_name_year_sem_idx'] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable) {
                }
            }
        });

        if ($hasTrackColumn) {
            Schema::table('interactive_class_scores', function (Blueprint $table) {
                $table->dropColumn('track');
            });
        }
    }
};
