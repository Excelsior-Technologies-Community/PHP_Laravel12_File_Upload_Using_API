<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'details' => $this->details,
            'image' => $this->image ? asset('image/' . $this->image) : null,
            'size' => $this->size,
            'color' => $this->color,
            'category' => $this->category,
            'media' => ProductMediaResource::collection($this->whenLoaded('media')),
            'media_urls' => $this->media_urls,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
