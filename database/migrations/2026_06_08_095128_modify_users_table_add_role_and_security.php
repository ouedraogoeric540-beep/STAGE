<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'organisateur', 'agent','participant'])->default('organisateur')->after('password');
            $table->boolean('statut')->default(true)->after('role');
            $table->integer('tentatives_connexion')->default(0)->after('statut');
            $table->dateTime('bloque_jusqu_a')->nullable()->after('tentatives_connexion');
            $table->string('telephone', 191)->nullable()->after('bloque_jusqu_a');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'statut', 'tentatives_connexion', 'bloque_jusqu_a', 'telephone']);
        });
    }
};