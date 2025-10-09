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
        Schema::create('permissoes_de_usuario', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignUlid('permissao_id')->constrained('permissoes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissoes_de_usuario');
    }
};
