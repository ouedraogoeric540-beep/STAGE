<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->string('nom', 191);
            $table->decimal('prix', 10, 2);
            $table->integer('quantite_total');
            $table->integer('quantite_vendue')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_tickets');
    }
};