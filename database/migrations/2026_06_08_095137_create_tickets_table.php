<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code_unique', 191)->unique();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->foreignId('categorie_id')->constrained('categories_tickets')->cascadeOnDelete();
            $table->string('qr_code', 191)->unique();
            $table->enum('statut', ['valide', 'utilise', 'expire'])->default('valide');
            $table->decimal('prix_paye', 10, 2);
            $table->string('pdf_path', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};