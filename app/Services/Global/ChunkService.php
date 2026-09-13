<?php

namespace App\Services\Global;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkService
{
    public function upload($data, $is_final): false|string
    {
        // Get the file name from the uploaded file data
        $fileName = pathinfo($data['file_name'], PATHINFO_FILENAME);
        $chunkDir = 'chunks/'.(auth()->id() ?? 1)."/$fileName";

        // Ensure the chunks directory is new dir for first chunk
        when(Storage::exists($chunkDir) && (int) $data['chunk_number'] === 1, fn () => Storage::deleteDirectory($chunkDir));

        // Create the chunks directory if it does not exist
        when(! Storage::exists($chunkDir), fn () => Storage::makeDirectory($chunkDir));

        // Save the chunk
        $file = Storage::putFileAs($chunkDir, $data['chunk_file'], $data['chunk_number']);

        if ($is_final) {
            return $this->combineChunks($chunkDir, $data['file_name']);
        }

        return $file;
    }

    public function combineChunks($chunkDir, $fileName): string
    {
        // Create the final directory if it does not exist
        $parts = explode('/', $fileName, 2);

        // set folder name if exists
        $folder = $parts[1] ?? null ? $parts[0] : 'files';
        $fileName = $parts[1] ?? $parts[0];

        when(! Storage::exists($folder), fn () => Storage::makeDirectory($folder));

        // Generate unique file name for the final file and its path
        $finalPath = "$folder/(".Str::limit(strrev(time()), 4, '').")_$fileName";
        $finalFile = Storage::path($finalPath);

        // Merge all chunks into the final file
        $finalFileOpen = fopen($finalFile, 'ab');
        for ($i = 1, $iMax = count(Storage::files($chunkDir)); $i <= $iMax; $i++) {
            fwrite($finalFileOpen, Storage::get("$chunkDir/$i"));
            Storage::delete("$chunkDir/$i"); // delete chunk after appending
        }

        // Close the final file and delete the chunks directory
        fclose($finalFileOpen);
        Storage::deleteDirectory($chunkDir);

        return $finalPath;
    }
}
