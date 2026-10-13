<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'user_id',
        'version_number',
        'payload',
        'record_count',
        'health_score',
        'change_summary',
    ];

    protected $casts = [
        'payload' => 'array',
        'version_number' => 'integer',
        'record_count' => 'integer',
        'health_score' => 'integer',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
