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
        Schema::create('staff_role_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('registration_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedTinyInteger('preference_order');
            $table->timestamps();

            $table->foreign('registration_id')
                ->references('id')
                ->on('staff_event_registrations')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('event_roles')
                ->onDelete('cascade');

            $table->unique(['registration_id', 'preference_order']);
            $table->index('registration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_role_preferences');
    }
};
