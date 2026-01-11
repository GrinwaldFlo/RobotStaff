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
        Schema::create('staff_event_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_id');
            $table->uuid('event_id');
            $table->text('comment')->nullable();
            $table->boolean('help_before_event')->default(false);
            $table->string('team_affiliation')->nullable();
            $table->boolean('is_first_participation')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->unsignedBigInteger('assigned_role_id')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');

            $table->foreign('assigned_role_id')
                ->references('id')
                ->on('event_roles')
                ->onDelete('set null');

            $table->index('staff_id');
            $table->index('event_id');
            $table->unique(['staff_id', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_event_registrations');
    }
};
