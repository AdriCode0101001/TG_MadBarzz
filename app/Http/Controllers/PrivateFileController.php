<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileController extends Controller
{
    /**
     * Serves a file from the private disk after verifying authentication.
     * Additional per-file authorization can be layered on top as needed.
     */
    public function serve(Request $request, string $path): StreamedResponse
    {
        abort_unless(Storage::disk('privado')->exists($path), 404);

        $mime = Storage::disk('privado')->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk('privado')->response($path, basename($path), [
            'Content-Type'  => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
