<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->when($request->routeIs('tickets.show'), fn () => $this->description),
            'priority' => ['value' => $this->priority->value, 'label' => $this->priority->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->when($request->routeIs('tickets.show'), fn () => $this->updated_at?->toIso8601String()),
            'author' => $this->whenLoaded('author', fn () => $this->author->name),
        ];
    }
}
