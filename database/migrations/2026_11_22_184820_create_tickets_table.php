<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained();
            $table->string('title');
            $table->text('description');
            $table->string('priority')->default(TicketPriority::Normal->value);
            $table->string('status')->default(TicketStatus::New->value)->index();
            $table->timestamps();
        });

        $in = fn (array $cases) => implode(', ', array_map(fn ($c) => "'{$c->value}'", $cases));

        DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_priority_check CHECK (priority IN ('.$in(TicketPriority::cases()).'))');
        DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status IN ('.$in(TicketStatus::cases()).'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
