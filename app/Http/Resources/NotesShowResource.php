<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotesShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note_id' => $this->note_id,
            'note_id' => $this->note_id,
            'title' => $this->title,
            'category' => $this->category,
            'note_content' => $this->note_content,
            'star' => new StarShowResource($this->whenLoaded('stars')),
            'images_count' => $this->whenCounted('images'),
            'images' => ImagesResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at->format('d M Y'),
        ];
    }
}
