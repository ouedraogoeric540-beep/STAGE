<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->enum('type', [
                'concert', 'conference', 'sport',
                'soiree', 'festival', 'theatre',
                'exposition', 'autre'
            ])->default('autre')->after('titre');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};