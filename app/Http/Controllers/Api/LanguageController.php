<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['code', 'label', 'native_label', 'sort_order']);

        return response()->json([
            'status' => true,
            'data' => $languages,
        ]);
    }
}
