<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
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
        $admin = User::factory()->admin()->create(['name' => 'Адміністратор', 'email' => 'admin@example.com']);
        $users = [
            User::factory()->create(['name' => 'Користувач 1', 'email' => 'user1@example.com']),
            User::factory()->create(['name' => 'Користувач 2', 'email' => 'user2@example.com']),
        ];

        foreach ($users as $user) {
            foreach (self::TITLES as $i => $title) {
                // 10 нових, 6 у роботі, 4 завершені
                $factory = match (true) {
                    $i < 10 => Ticket::factory(),
                    $i < 16 => Ticket::factory()->inProgress(),
                    default => Ticket::factory()->done(),
                };

                $ticket = $factory->for($user, 'author')->create([
                    'title' => $title,
                    'created_at' => $created = now()->subDays(fake()->numberBetween(20, 29)),
                ]);

                $this->seedHistory($ticket, $admin, $created);
            }
        }
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
            $at = $at->addHours(fake()->numberBetween(1, 48));
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
