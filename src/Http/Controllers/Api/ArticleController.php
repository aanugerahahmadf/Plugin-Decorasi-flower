<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Article::where('is_published', true)->latest()->get(['*']),
        ]);
    }
}
