<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Cek apakah sebuah index sudah ada di tabel */
    private function hasIndex(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    /** Cek apakah sebuah foreign key sudah ada (berdasarkan constraint_name) */
    private function hasForeign(string $table, string $fkName): bool
    {
        $db = DB::getDatabaseName();
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('constraint_type', 'FOREIGN KEY')
            ->where('constraint_name', $fkName)
            ->exists();
    }

    public function up(): void
    {
        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            // Kolom: tutor_id
            if (! Schema::hasColumn('basic_listening_survey_responses', 'tutor_id')) {
                $table->foreignId('tutor_id')->nullable()->after('user_id');
            }

            // Kolom: supervisor_id
            if (! Schema::hasColumn('basic_listening_survey_responses', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('tutor_id');
            }

            // Kolom: meta (opsional)
            if (! Schema::hasColumn('basic_listening_survey_responses', 'meta')) {
                $table->json('meta')->nullable()->after('submitted_at');
            }
        });

        // Tambah / perbarui FK dengan nama eksplisit (hindari bentrok nama otomatis)
        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            // ----- FK tutor_id → users.id
            $fkTutor = 'fk_blsr_tutor_id_users';
            if ($this->hasForeign('basic_listening_survey_responses', $fkTutor)) {
                $table->dropForeign($fkTutor);
            }
            // Jika sebelumnya ada FK tanpa nama ini, coba drop by column (silent fail jika tak ada)
            try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}

            $table->foreign('tutor_id', $fkTutor)
                ->references('id')->on('users')
                ->nullOnDelete();

            // ----- FK supervisor_id → basic_listening_supervisors.id
            $fkSup = 'fk_blsr_supervisor_id_bls';
            if ($this->hasForeign('basic_listening_survey_responses', $fkSup)) {
                $table->dropForeign($fkSup);
            }
            try { $table->dropForeign(['supervisor_id']); } catch (\Throwable $e) {}

            $table->foreign('supervisor_id', $fkSup)
                ->references('id')->on('basic_listening_supervisors')
                ->nullOnDelete();
        });

        // Index bantu: tambahkan hanya jika belum ada
        $idxTutor = 'basic_listening_survey_responses_survey_id_tutor_id_index';
        if (! $this->hasIndex('basic_listening_survey_responses', $idxTutor)) {
            Schema::table('basic_listening_survey_responses', function (Blueprint $table) use ($idxTutor) {
                $table->index(['survey_id', 'tutor_id'], $idxTutor);
            });
        }

        $idxSup = 'basic_listening_survey_responses_survey_id_supervisor_id_index';
        if (! $this->hasIndex('basic_listening_survey_responses', $idxSup)) {
            Schema::table('basic_listening_survey_responses', function (Blueprint $table) use ($idxSup) {
                $table->index(['survey_id', 'supervisor_id'], $idxSup);
            });
        }
    }

    public function down(): void
    {
        // Drop index jika ada
        $idxTutor = 'basic_listening_survey_responses_survey_id_tutor_id_index';
        if ($this->hasIndex('basic_listening_survey_responses', $idxTutor)) {
            Schema::table('basic_listening_survey_responses', function (Blueprint $table) use ($idxTutor) {
                $table->dropIndex($idxTutor);
            });
        }

        $idxSup = 'basic_listening_survey_responses_survey_id_supervisor_id_index';
        if ($this->hasIndex('basic_listening_survey_responses', $idxSup)) {
            Schema::table('basic_listening_survey_responses', function (Blueprint $table) use ($idxSup) {
                $table->dropIndex($idxSup);
            });
        }

        // Drop FK dengan nama eksplisit (fallback drop by column kalau perlu)
        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            $fkTutor = 'fk_blsr_tutor_id_users';
            $fkSup   = 'fk_blsr_supervisor_id_bls';

            try { $table->dropForeign($fkTutor); } catch (\Throwable $e) {}
            try { $table->dropForeign($fkSup); } catch (\Throwable $e) {}

            try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['supervisor_id']); } catch (\Throwable $e) {}

            if (Schema::hasColumn('basic_listening_survey_responses', 'tutor_id')) {
                $table->dropColumn('tutor_id');
            }
            if (Schema::hasColumn('basic_listening_survey_responses', 'supervisor_id')) {
                $table->dropColumn('supervisor_id');
            }
            if (Schema::hasColumn('basic_listening_survey_responses', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
