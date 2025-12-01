<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
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
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'name' => $this->name,
            'prep_time_minutes' => $this->prep_time_minutes,
            'servings' => $this->servings,
            'image' => $this->image_url,
            'instructions' => $this->instructions,
            'ingredients' => $this->ingredients,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

