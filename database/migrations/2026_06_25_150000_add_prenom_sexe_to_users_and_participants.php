<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom', 191)->nullable()->after('name');
            $table->enum('sexe', ['M', 'F'])->nullable()->after('telephone');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->string('prenom', 191)->nullable()->after('nom');
            $table->enum('sexe', ['M', 'F'])->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'sexe']);
        });
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'sexe']);
        });
    }
};
