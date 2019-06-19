<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDetails extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name'          => $this->name,
            'company'       => $this->company,
            'designation'   => $this->designation,
            'mobile'        => $this->mobile,
            'status'        => $this->status,
            'ip_address'    => $this->ip_address,
            'region'        => $this->region,
            'country'       => $this->country,
            'image_path'    => $this->image_path
        ];       
    }
}
