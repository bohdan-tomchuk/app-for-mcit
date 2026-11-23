<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var list<string> */
    private const TITLES = [
        'Не працює принтер на другому поверсі',
        'ПРИНТЕР друкує смугами',
        'Замінити картридж у Принтері бухгалтерії',
        'Не вмикається монітор',
        'Скинути пароль до корпоративної пошти',
        'Повільно працює комп\'ютер',
        'Немає доступу до спільної папки',
        'Потрібен доступ до VPN',
        'Встановити Microsoft Office',
        'Не працює Wi-Fi в переговорній',
        'Зависає 1С під час формування звіту',
        'Замінити клавіатуру та мишу',
        'Налаштувати пошту на телефоні',
        'Не відкривається PDF-файл',
        'Потрібне додаткове місце на диску',
        'Проєктор не бачить ноутбук',
        'Оновити антивірус на робочому місці',
        'Не працює IP-телефон',
        'Налаштувати сканер для відділу кадрів',
        'Втрачено доступ до облікового запису',
    ];

    public function run(): void
    {
        $admin = $this->createUser('Адміністратор', 'admin@example.com', UserRole::Admin);
        $users = [
            $this->createUser('Користувач 1', 'user1@example.com', UserRole::User),
            $this->createUser('Користувач 2', 'user2@example.com', UserRole::User),
        ];

        foreach ($users as $user) {
            foreach (self::TITLES as $i => $title) {
                // 10 нових, 6 у роботі, 4 завершені
                $status = match (true) {
                    $i < 10 => TicketStatus::New,
                    $i < 16 => TicketStatus::InProgress,
                    default => TicketStatus::Done,
                };

                $ticket = Ticket::forceCreate([
                    'user_id' => $user->id,
                    'title' => $title,
                    'description' => 'Опис заявки: '.$title.'.',
                    'priority' => TicketPriority::Normal,
                    'status' => $status,
                    'created_at' => $created = now()->subDays(random_int(20, 29)),
                ]);

                $this->seedHistory($ticket, $admin, $created);
            }
        }
    }

    /**
     * Без фабрик: Faker є dev-залежністю, а seeder працює й у production-образі.
     */
    private function createUser(string $name, string $email, UserRole $role): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function seedHistory(Ticket $ticket, User $admin, CarbonInterface $after): void
    {
        $steps = match ($ticket->status) {
            TicketStatus::New => [],
            TicketStatus::InProgress => [[TicketStatus::New, TicketStatus::InProgress]],
            TicketStatus::Done => [
                [TicketStatus::New, TicketStatus::InProgress],
                [TicketStatus::InProgress, TicketStatus::Done],
            ],
        };

        $at = $after->copy();
        foreach ($steps as [$from, $to]) {
            $at = $at->addHours(random_int(1, 48));
            TicketStatusChange::create([
                'ticket_id' => $ticket->id,
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $admin->id,
                'created_at' => $at,
            ]);
        }
    }
}
