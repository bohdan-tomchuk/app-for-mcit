<?php

namespace App\Http\Resources;

use App\Models\TicketStatusChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketStatusChange
 */
class TicketStatusChangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => ['value' => $this->from_status->value, 'label' => $this->from_status->label()],
            'to_status' => ['value' => $this->to_status->value, 'label' => $this->to_status->label()],
            'actor' => $this->actor->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
