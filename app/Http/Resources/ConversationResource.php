<?php

// App\Http\Resources\ConversationResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'conversation_id' => $this->conversation_id,
            'name' => $this->name,
            'is_group' => $this->is_group,
            'participants' => $this->participants->map(fn($p) => [
                'user_id' => $p->user_id,
                'name' => $p->name,
                'avatar_url' => $p->avatar_url,
            ]),
        ];
    }
}
