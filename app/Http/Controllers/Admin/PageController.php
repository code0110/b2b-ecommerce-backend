<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::query()->orderByDesc('created_at');
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        return $query->paginate(20);
    }

    public function show($id)
    {
        return Page::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'content' => ['nullable', 'string'],
            'sections' => ['nullable', 'array'],
            'is_published' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $page = Page::create($data);
        return response()->json($page, 201);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug,' . $page->id],
            'content' => ['nullable', 'string'],
            'sections' => ['nullable', 'array'],
            'is_published' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $page->update($data);
        return response()->json($page);
    }

    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();
        return response()->json(['message' => 'Page deleted']);
    }
}
