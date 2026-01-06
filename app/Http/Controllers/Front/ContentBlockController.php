<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\Request;

class ContentBlockController extends Controller
{
    public function index(Request $request)
    {
        $query = ContentBlock::where('is_active', true);

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        $blocks = $query->get();

        $mapped = $blocks->mapWithKeys(function ($block) {
            $content = $block->content;
            if ($block->type === 'json') {
                $content = json_decode($block->content);
            }
            return [$block->key => $content];
        });

        return response()->json($mapped);
    }
}
