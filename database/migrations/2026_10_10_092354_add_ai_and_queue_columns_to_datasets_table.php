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
            $table->text('ai_summary')->nullable()->after('health_report');
            $table->json('ai_suggested_tags')->nullable()->after('ai_summary');
            $table->string('ingestion_job_id')->nullable()->after('ingestion_status');
            $table->unsignedTinyInteger('ingestion_progress')->default(100)->after('ingestion_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropColumn([
                'ai_summary',
                'ai_suggested_tags',
                'ingestion_job_id',
                'ingestion_progress',
            ]);
        });
    }
};
