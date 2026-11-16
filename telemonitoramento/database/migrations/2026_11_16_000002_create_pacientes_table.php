<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas');
            $table->string('nome');
            $table->string('cpf', 11)->unique();
            $table->date('data_nascimento');
            $table->string('telefone', 20);
            $table->string('prioridade', 10)->default('baixa');
            $table->date('proximo_contato_em')->nullable();
            $table->timestamp('ultimo_acompanhamento_em')->nullable();
            $table->timestamps();

            $table->index(['programa_id', 'prioridade', 'proximo_contato_em']);
        });

        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->foreignId('user_id')->constrained('users');
            $table->string('status', 20)->default('reservado');
            $table->string('motivo_remocao', 40)->nullable();
            $table->string('observacao_remocao', 500)->nullable();
            $table->timestamp('finalizada_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['paciente_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('pacientes');
    }
};
