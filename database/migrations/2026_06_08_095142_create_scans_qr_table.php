<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scans_qr', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code', 191)->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->enum('resultat', ['valide', 'deja_utilise', 'invalide', 'mauvais_evenement']);
            $table->dateTime('date_scan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans_qr');
    }
};