<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_online_questions', function (Blueprint $table): void {
            $table->text('correct_option')->change();
        });

        DB::table('ept_online_questions')
            ->select(['id', 'correct_option'])
            ->orderBy('id')
            ->chunkById(200, function ($questions): void {
                foreach ($questions as $question) {
                    $value = strtoupper(trim((string) $question->correct_option));

                    if ($value === '' || $this->isEncrypted((string) $question->correct_option)) {
                        continue;
                    }

                    DB::table('ept_online_questions')
                        ->where('id', $question->id)
                        ->update(['correct_option' => Crypt::encryptString($value)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('ept_online_questions')
            ->select(['id', 'correct_option'])
            ->orderBy('id')
            ->chunkById(200, function ($questions): void {
                foreach ($questions as $question) {
                    $value = (string) $question->correct_option;

                    if (! $this->isEncrypted($value)) {
                        continue;
                    }

                    DB::table('ept_online_questions')
                        ->where('id', $question->id)
                        ->update(['correct_option' => Crypt::decryptString($value)]);
                }
            });

        Schema::table('ept_online_questions', function (Blueprint $table): void {
            $table->char('correct_option', 1)->change();
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
