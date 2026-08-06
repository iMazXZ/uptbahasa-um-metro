<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Interactive Class scores for Pendidikan Bahasa Inggris (6 semesters)
            $table->unsignedTinyInteger('interactive_class_1')->nullable()->after('nilaibasiclistening');
            $table->unsignedTinyInteger('interactive_class_2')->nullable()->after('interactive_class_1');
            $table->unsignedTinyInteger('interactive_class_3')->nullable()->after('interactive_class_2');
            $table->unsignedTinyInteger('interactive_class_4')->nullable()->after('interactive_class_3');
            $table->unsignedTinyInteger('interactive_class_5')->nullable()->after('interactive_class_4');
            $table->unsignedTinyInteger('interactive_class_6')->nullable()->after('interactive_class_5');

            // Interactive Bahasa Arab scores for 3 Prodi Islam (2 fields)
            $table->unsignedTinyInteger('interactive_bahasa_arab_1')->nullable()->after('interactive_class_6');
            $table->unsignedTinyInteger('interactive_bahasa_arab_2')->nullable()->after('interactive_bahasa_arab_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'interactive_class_1',
                'interactive_class_2',
                'interactive_class_3',
                'interactive_class_4',
                'interactive_class_5',
                'interactive_class_6',
                'interactive_bahasa_arab_1',
                'interactive_bahasa_arab_2',
            ]);
        });
    }
};
