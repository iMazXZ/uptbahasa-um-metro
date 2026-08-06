<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_online_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('listening_audio_path')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('last_import_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('ept_online_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('ept_online_forms')->cascadeOnDelete();
            $table->enum('type', ['listening', 'structure', 'reading']);
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('audio_path')->nullable();
            $table->unsignedInteger('audio_duration_seconds')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'type']);
            $table->index(['form_id', 'sort_order']);
        });

        Schema::create('ept_online_passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('ept_online_forms')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('ept_online_sections')->cascadeOnDelete();
            $table->string('passage_code');
            $table->string('title')->nullable();
            $table->longText('content');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'passage_code']);
            $table->index(['section_id', 'sort_order']);
        });

        Schema::create('ept_online_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('ept_online_forms')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('ept_online_sections')->cascadeOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained('ept_online_passages')->nullOnDelete();
            $table->string('part_label')->nullable();
            $table->string('group_code')->nullable();
            $table->unsignedSmallInteger('number_in_section');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->longText('instruction')->nullable();
            $table->longText('prompt');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->char('correct_option', 1);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'section_id', 'number_in_section'], 'ept_online_questions_unique_per_section');
            $table->index(['form_id', 'sort_order']);
            $table->index(['section_id', 'sort_order']);
        });

        Schema::create('ept_online_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('ept_online_forms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ept_registration_id')->nullable()->constrained('ept_registrations')->nullOnDelete();
            $table->foreignId('ept_group_id')->nullable()->constrained('ept_groups')->nullOnDelete();
            $table->string('token_hash', 255);
            $table->string('token_hint', 64)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->unsignedSmallInteger('used_attempts')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'is_active']);
            $table->index(['ept_group_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('ept_online_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('ept_online_forms')->cascadeOnDelete();
            $table->foreignId('access_token_id')->nullable()->constrained('ept_online_access_tokens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ept_registration_id')->nullable()->constrained('ept_registrations')->nullOnDelete();
            $table->foreignId('ept_group_id')->nullable()->constrained('ept_groups')->nullOnDelete();
            $table->enum('current_section_type', ['listening', 'structure', 'reading'])->nullable();
            $table->enum('status', ['draft', 'in_progress', 'submitted', 'expired', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_section_started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('integrity_flags')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['ept_group_id', 'status']);
        });

        Schema::create('ept_online_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('ept_online_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('ept_online_questions')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('ept_online_sections')->cascadeOnDelete();
            $table->char('selected_option', 1)->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'section_id']);
        });

        Schema::create('ept_online_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('ept_online_attempts')->cascadeOnDelete();
            $table->unsignedSmallInteger('listening_raw')->nullable();
            $table->unsignedSmallInteger('structure_raw')->nullable();
            $table->unsignedSmallInteger('reading_raw')->nullable();
            $table->unsignedSmallInteger('listening_scaled')->nullable();
            $table->unsignedSmallInteger('structure_scaled')->nullable();
            $table->unsignedSmallInteger('reading_scaled')->nullable();
            $table->unsignedSmallInteger('total_scaled')->nullable();
            $table->string('scale_version')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('attempt_id');
            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_online_results');
        Schema::dropIfExists('ept_online_answers');
        Schema::dropIfExists('ept_online_attempts');
        Schema::dropIfExists('ept_online_access_tokens');
        Schema::dropIfExists('ept_online_questions');
        Schema::dropIfExists('ept_online_passages');
        Schema::dropIfExists('ept_online_sections');
        Schema::dropIfExists('ept_online_forms');
    }
};
