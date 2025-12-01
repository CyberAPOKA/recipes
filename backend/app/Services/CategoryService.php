<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Get all categories ordered by name.
     */
    public function getAllCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }
}

