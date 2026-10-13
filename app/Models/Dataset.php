<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dataset extends Model
{
    use HasFactory;
    use Prunable;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'record_count',
        'column_count',
        'excel_file',
        'file_size_bytes',
        'file_type',
        'payload',
        'health_score',
        'health_report',
        'ai_summary',
        'ai_suggested_tags',
        'last_ingested_at',
        'ingestion_status',
        'ingestion_job_id',
        'ingestion_progress',
        'current_version',
    ];

    protected $casts = [
        'payload' => 'array',
        'health_report' => 'array',
        'ai_suggested_tags' => 'array',
        'mask_pii' => 'boolean',
        'last_ingested_at' => 'datetime',
        'health_score' => 'integer',
        'record_count' => 'integer',
        'column_count' => 'integer',
        'ingestion_progress' => 'integer',
        'current_version' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the prunable model query (auto cleanup trashed datasets older than 30 days).
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DatasetVersion::class)->orderByDesc('version_number');
    }

    /**
     * Get quality rating badge color
     */
    public function getHealthColorAttribute(): string
    {
        if ($this->health_score >= 80) {
            return 'success';
        }

        if ($this->health_score >= 50) {
            return 'warning';
        }

        return 'danger';
    }

    /**
     * Get human label for health status
     */
    public function getHealthLabelAttribute(): string
    {
        if ($this->health_score >= 80) {
            return 'High Quality';
        }

        if ($this->health_score >= 50) {
            return 'Moderate';
        }

        return 'Needs Cleaning';
    }

    /**
     * Retrieve the model for a bound value, including soft-deleted datasets for restore & audit workflows.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withTrashed()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
