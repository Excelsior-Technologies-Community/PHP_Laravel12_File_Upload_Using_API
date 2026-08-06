<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'details',
        'image',
        'size',
        'color',
        'category'
    ];

    protected $appends = ['media_urls'];

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function getMediaUrlsAttribute(): array
    {
        return $this->media()->get()->map(function ($media) {
            return [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'file_path' => $media->file_path,
                'file_type' => $media->file_type,
                'mime_type' => $media->mime_type,
                'file_size' => $media->file_size,
                'thumbnail_path' => $media->thumbnail_path,
                'alt_text' => $media->alt_text,
                'is_primary' => $media->is_primary,
                        'url' => asset('storage/' . preg_replace('#^app/public/#', '', $media->file_path)),
                        'thumbnail_url' => $media->thumbnail_path ? asset('storage/' . preg_replace('#^app/public/#', '', $media->thumbnail_path)) : null,
            ];
        })->toArray();
    }
}
