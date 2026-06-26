<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications_eventsecure', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->enum('type', ['email', 'push', 'sms'])->default('email');
            $table->text('contenu');
            $table->enum('statut', ['en_attente', 'envoye', 'lu', 'echec'])->default('en_attente');
            $table->dateTime('date_envoi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_eventsecure');
    }
};