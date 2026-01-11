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
        Schema::create('email_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type', ['admin', 'staff']);
            $table->string('recipient_id', 36); // VARCHAR(36) for both BIGINT and UUID
            $table->uuid('event_id')->nullable();
            $table->string('notification_type', 100);
            $table->timestamp('sent_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');

            $table->index(['recipient_type', 'recipient_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notifications');
    }
};
