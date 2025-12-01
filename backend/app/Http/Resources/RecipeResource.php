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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
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
            'comments' => $this->whenLoaded('comments', function () {
                return $this->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                        ],
                        'created_at' => $comment->created_at,
                    ];
                });
            }),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            'ratings_count' => $this->when(isset($this->ratings_count), $this->ratings_count),
            'average_rating' => $this->when(
                $this->relationLoaded('ratings') || isset($this->ratings_avg_rating),
                round($this->ratings_avg_rating ?? 0, 2)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

