<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $query = ContentBlock::where('is_active', true);

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        $blocks = $query->get()->map(function ($block) {
            // Automatically decode JSON content if type is json
            if ($block->type === 'json' && is_string($block->content)) {
                $block->content = json_decode($block->content);
            }
            return $block;
        });

        // Return as key-value pairs for easier frontend consumption
        $formatted = $blocks->mapWithKeys(function ($block) {
            return [$block->key => $block->content];
        });

        return response()->json($formatted);
    }
}
