<?php

namespace App\Trait\Global;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait DeletesFile
{
    protected function performFileDelete(Model $file): void
    {
        Media::delete($file->getRawOriginal('path'));
        $file->delete();
    }
}
