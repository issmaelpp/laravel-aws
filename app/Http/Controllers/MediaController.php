<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Stream a media file from the storage disk.
     */
    public function show(string $path): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.default', 's3'));

        abort_if(! $disk->exists($path), 404);

        return $disk->response($path);
    }
}
