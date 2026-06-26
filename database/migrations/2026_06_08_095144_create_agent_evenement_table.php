<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agent_evenement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->boolean('actif')->default(true);
            $table->dateTime('date_affectation');
            $table->unique(['agent_id', 'evenement_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_evenement');
    }
};