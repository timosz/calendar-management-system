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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Service provider');
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('google_calendar_event_id')->nullable()->comment('For Google Calendar sync');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'booking_date'], 'idx_user_date');
            $table->index('status');
            $table->index('booking_date');
            $table->index('google_calendar_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
