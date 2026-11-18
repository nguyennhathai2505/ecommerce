<?php

namespace App\Services;

use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService{


    public function getAllCategories(){
        return Category::with('parent')->get();
    }

public function getActiveChildrenByParentSlug(string $parentSlug)
{
    return Category::query()
        ->where('status', 'active')
        ->where(function ($query) use ($parentSlug) {
            $query->whereHas('parent', function ($q) use ($parentSlug) {
                $q->where('slug', $parentSlug)
                  ->where('status', 'active');
            })
            ->orWhereNull('parent_id');
        })
        ->orderBy('name')
        ->get();
}

    public function getCategoryById($id){
        return Category::with('parent','children')->findOrFail($id);
    }

    public function createCategory(array $data){
        return Category::create($data);
    }

    public function updateCategory($id, array $data){
        $category = Category::findOrFail($id);
        $category->update($data);
        return $category;
    }

    public function deleteCategory($id){
        $category = Category::findOrFail($id);
        if($category->products()->count()>0){
            throw new Exception("Không thể xóa danh mục này vì danh mục đang tồn tại sản phẩm");
        }
        return $category->delete();
    }
}