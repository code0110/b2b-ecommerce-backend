<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Handle generic file upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // max 10MB
            'folder' => 'nullable|string|max:50',
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'uploads');
        
        // Generate a clean filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store in public disk
        $path = $file->storeAs($folder, $filename, 'public');

        return response()->json([
            'path' => Storage::url($path), // Returns /storage/uploads/filename.jpg
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
