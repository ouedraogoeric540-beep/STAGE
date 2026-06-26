<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisateur_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre', 191);
            $table->text('description')->nullable();
            $table->string('image', 191)->nullable();
            $table->dateTime('date');
            $table->string('lieu', 191);
            $table->integer('capacite_max');
            $table->enum('statut', ['actif', 'termine', 'annule'])->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};