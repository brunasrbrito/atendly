<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\KnowledgeSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request, KnowledgeSearch $search): View
    {
        $q = trim((string) $request->query('q'));
        $category = $request->integer('categoria') ?: null;

        return view('search', [
            'q' => $q,
            'category' => $category,
            'categories' => Category::orderBy('name')->get(),
            'terms' => $search->terms($q),
            'results' => $q === '' ? collect() : $search->search($q, 20, categoryId: $category),
        ]);
    }
}
