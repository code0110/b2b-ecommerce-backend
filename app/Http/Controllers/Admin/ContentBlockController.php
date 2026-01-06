<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\Request;

class ContentBlockController extends Controller
{
    public function index(Request $request)
    {
        $query = ContentBlock::query();

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    public function show(ContentBlock $contentBlock)
    {
        return response()->json($contentBlock);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:content_blocks,key|max:255',
            'group' => 'required|string|max:255',
            'type' => 'required|string|in:text,html,image,json',
            'content' => 'required',
            'title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'meta' => 'nullable|array'
        ]);

        $contentBlock = ContentBlock::create($validated);

        return response()->json($contentBlock, 201);
    }

    public function update(Request $request, ContentBlock $contentBlock)
    {
        $validated = $request->validate([
            'content' => 'required',
            'title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'meta' => 'nullable|array'
        ]);

        $contentBlock->update($validated);

        return response()->json($contentBlock);
    }

    public function destroy(ContentBlock $contentBlock)
    {
        $contentBlock->delete();
        return response()->noContent();
    }
}
