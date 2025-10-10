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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('primeiro_nome');
            $table->string('segundo_nome');
            $table->string('apelido');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('senha');
            $table->string('telefone');
            $table->string('numero_documento')->unique();
            $table->date('data_nascimento');
            $table->enum('tipo_usuario', ['administrador', 'usuario'])->default('usuario');
            $table->boolean('aceite_comunicacoes_email')->default(false);
            $table->boolean('aceite_comunicacoes_sms')->default(false);
            $table->boolean('aceite_comunicacoes_whatsapp')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
