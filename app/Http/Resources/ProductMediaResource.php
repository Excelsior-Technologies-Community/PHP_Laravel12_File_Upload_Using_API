<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    public function toArray($request)
    {
        $url = null;
        $thumbnailUrl = null;

        if ($this->file_path) {
            $url = asset('storage/' . preg_replace('#^app/public/#', '', $this->file_path));
        }
        if ($this->thumbnail_path) {
            $thumbnailUrl = asset('storage/' . preg_replace('#^app/public/#', '', $this->thumbnail_path));
        }

        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'url' => $url,
            'thumbnail_url' => $thumbnailUrl,
            'alt_text' => $this->alt_text,
            'is_primary' => $this->is_primary,
        ];
    }
}
