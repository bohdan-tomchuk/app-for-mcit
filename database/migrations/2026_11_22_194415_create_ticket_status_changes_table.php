<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->index()->constrained()->restrictOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        DB::statement("ALTER TABLE ticket_status_changes ADD CONSTRAINT ticket_status_changes_from_check CHECK (from_status IN ('new', 'in_progress', 'done'))");
        DB::statement("ALTER TABLE ticket_status_changes ADD CONSTRAINT ticket_status_changes_to_check CHECK (to_status IN ('new', 'in_progress', 'done'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_status_changes');
    }
};
