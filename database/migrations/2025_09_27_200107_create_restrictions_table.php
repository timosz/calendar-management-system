<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable(); // NULL = all day restricted
            $table->time('end_time')->nullable(); // NULL = all day restricted
            $table->string('reason')->nullable();
            $table->enum('type', ['holiday', 'break', 'meeting', 'personal', 'maintenance', 'other'])->default('other');
            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restrictions');
    }
};
