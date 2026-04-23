<?php

namespace Modules\Export\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    protected $basicResource;
    protected $creatorResource;


    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->basicResource = config('export.basic_resource');
        $this->creatorResource = config('export.creator_resource');
    }
}
