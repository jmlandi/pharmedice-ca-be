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
        Schema::table('usuarios', function (Blueprint $table) {
            // Tornar segundo_nome nullable para suportar usuários do Google com apenas um nome
            $table->string('segundo_nome')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Reverter para não-nulo (mas isso pode falhar se houver dados com null)
            $table->string('segundo_nome')->nullable(false)->change();
        });
    }
};
