<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $fillable = [
        'product_id',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'alt_text',
        'is_primary',
        'checksum',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
    ];

    protected $appends = [
        'human_file_size',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }

        return round(
            $bytes / (1024 * 1024 * 1024),
            2
        ) . ' GB';
    }
}
