<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->clearCaches($category);
    }

    public function updated(Category $category): void
    {
        // Если parent_id изменился — это тоже влияет на деревья
        $this->clearCaches($category);
    }

    public function deleted(Category $category): void
    {
        $this->clearCaches($category);
    }


protected function clearCaches(Category $category): void
{
    // Сбрасываем кеш самой категории (на случай, если она используется как корень)
    Cache::forget("category_ids_tree_{$category->id}");

    // Сбрасываем кеш всех её родителей (вверх по цепочке)
    $parentId = $category->parent_id;
    while ($parentId) {
        Cache::forget("category_ids_tree_{$parentId}");
        $parent = Category::find($parentId);
        $parentId = $parent?->parent_id;
    }

    // Также сбрасываем общий кеш дерева из Inertia
    Cache::forget('categories_tree');
}
}