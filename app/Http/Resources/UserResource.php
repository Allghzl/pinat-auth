<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'puid' => $this->id,

            'name' => $this->name,

            'email' => $this->email,

            'avatar_key' => $this->avatar_key,

            'avatar_url' => $this->avatar_key
                ? Storage::disk('avatar')->temporaryUrl(
                    $this->avatar_key,
                    now()->addMinutes(10),
                )
                : null,

            'email_verified_at' => $this->email_verified_at?->toIso8601String(),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
