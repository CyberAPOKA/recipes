<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeRating extends Model
{
    use HasFactory;
    protected $fillable = [
        'recipe_id',
        'user_id',
        'rating',
    ];

    /**
     * Get the recipe that owns the rating.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the user that made the rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
