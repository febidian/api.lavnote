<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'notes_user_id' => $this->notes_user_id,
            'email' => $this->email,
            'created_at' => $this->created_at->format('d-m-Y')
        ];
    }
}
