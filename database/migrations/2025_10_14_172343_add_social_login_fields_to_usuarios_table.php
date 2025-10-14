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
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('provider')->nullable()->after('google_id')->comment('OAuth provider: google, facebook, etc.');
            $table->string('avatar')->nullable()->after('provider')->comment('URL do avatar do usuário');
            
            // Tornar campos opcionais para login social
            $table->string('senha')->nullable()->change();
            $table->string('telefone')->nullable()->change();
            $table->string('numero_documento')->nullable()->change();
            $table->date('data_nascimento')->nullable()->change();
            $table->string('apelido')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'provider', 'avatar']);
            
            // Reverter campos para não-nulos
            $table->string('senha')->nullable(false)->change();
            $table->string('telefone')->nullable(false)->change();
            $table->string('numero_documento')->nullable(false)->change();
            $table->date('data_nascimento')->nullable(false)->change();
            $table->string('apelido')->nullable(false)->change();
        });
    }
};
