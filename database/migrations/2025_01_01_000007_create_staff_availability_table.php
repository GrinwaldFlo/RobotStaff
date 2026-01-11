<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_availability', function (Blueprint $table) {
            $table->id();
            $table->uuid('registration_id');
            $table->unsignedBigInteger('event_day_id');
            $table->boolean('is_available_morning')->default(true);
            $table->boolean('is_available_afternoon')->default(true);
            $table->timestamps();

            $table->foreign('registration_id')
                ->references('id')
                ->on('staff_event_registrations')
                ->onDelete('cascade');

            $table->foreign('event_day_id')
                ->references('id')
                ->on('event_days')
                ->onDelete('cascade');

            $table->unique(['registration_id', 'event_day_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_availability');
    }
};
