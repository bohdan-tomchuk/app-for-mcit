<?php

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
            $table->string('priority')->default('normal');
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_priority_check CHECK (priority IN ('low', 'normal', 'high'))");
        DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status IN ('new', 'in_progress', 'done'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
