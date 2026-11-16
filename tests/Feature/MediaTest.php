<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('media route streams files from storage disk', function (): void {
    Storage::fake(config('filesystems.default', 's3'));

    $file = UploadedFile::fake()->image('test.jpg');
    $path = $file->store('articles', config('filesystems.default', 's3'));

    $response = $this->get(route('media.show', ['path' => $path]));

    $response->assertOk();
});

test('media route returns 404 if file does not exist', function (): void {
    Storage::fake(config('filesystems.default', 's3'));

    $response = $this->get(route('media.show', ['path' => 'articles/non-existent.jpg']));

    $response->assertNotFound();
});
