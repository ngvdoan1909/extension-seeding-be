<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CommissionCollection extends ResourceCollection
{
    public function toArray(Request $request)
    {
        return CommissionResource::collection($this->collection);
    }
}
