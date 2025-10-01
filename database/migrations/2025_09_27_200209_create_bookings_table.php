<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Resource owner/Service provider
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('google_calendar_event_id')->nullable(); // For integration with Google Calendar
            $table->timestamps();

            // Optimize performance for single-user systems
            $table->index(['status', 'booking_date'], 'idx_status_date');
            // For multi-user systems
            // $table->index(['user_id', 'status', 'booking_date'], 'idx_user_status_date');

            $table->index(['booking_date', 'start_time', 'end_time'], 'idx_time_conflict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
