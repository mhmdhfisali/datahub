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
        Schema::table('datasets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('record_count')->default(0)->after('category');
            $table->unsignedInteger('column_count')->default(0)->after('record_count');
            $table->unsignedBigInteger('file_size_bytes')->nullable()->after('excel_file');
            $table->string('file_type')->nullable()->after('file_size_bytes'); // xlsx, csv, json
            $table->unsignedTinyInteger('health_score')->default(100)->after('file_type'); // 0-100
            $table->json('health_report')->nullable()->after('health_score');
            $table->timestamp('last_ingested_at')->nullable()->after('health_report');
            $table->string('ingestion_status')->default('success')->after('last_ingested_at'); // success, warning, failed
            $table->unsignedInteger('current_version')->default(1)->after('ingestion_status');
        });

        Schema::create('dataset_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('payload');
            $table->unsignedInteger('record_count')->default(0);
            $table->unsignedTinyInteger('health_score')->default(100);
            $table->string('change_summary')->nullable();
            $table->timestamps();

            $table->unique(['dataset_id', 'version_number']);
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('log_name')->default('default');
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('dataset_versions');

        Schema::table('datasets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'record_count',
                'column_count',
                'file_size_bytes',
                'file_type',
                'health_score',
                'health_report',
                'last_ingested_at',
                'ingestion_status',
                'current_version',
            ]);
        });
    }
};
