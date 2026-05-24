<?php

namespace App\Http\Requests\Global\Chunk;

use App\Http\Requests\BaseFormRequest;

class ChunkFileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'file_name' => 'required|string',
            'chunk_number' => 'required|min:1',
            'chunk_file' => 'required|file',
        ];
    }
}
