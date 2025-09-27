<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unavailable_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable()->comment('NULL = all day unavailable');
            $table->time('end_time')->nullable()->comment('NULL = all day unavailable');
            $table->string('reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'start_date', 'end_date'], 'idx_user_dates');
            $table->index(['start_date', 'end_date'], 'idx_date_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unavailable_periods');
    }
};
