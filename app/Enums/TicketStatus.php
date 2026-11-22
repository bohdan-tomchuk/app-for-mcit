<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Нова',
            self::InProgress => 'У роботі',
            self::Done => 'Завершена',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::InProgress],
            self::InProgress => [self::Done],
            self::Done => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
