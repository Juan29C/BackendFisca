<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_expediente', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('id_expediente');
            $table->unsignedInteger('id_estado');

            $table->string('titulo', 200)->nullable();     // ej: "Recepción desde AIFIS"
            $table->text('descripcion')->nullable();       // ej: "Recepción desde AIFIS - Bloque AAA-001"

            $table->json('meta')->nullable();              // ej: {"origen":"AIFIS","bloque":"AAA-001"}

            

            $table->timestamps();

            // FKs
            $table->foreign('id_expediente')
                ->references('id')->on('expediente')
                ->onDelete('cascade');

            $table->foreign('id_estado')
                ->references('id')->on('estado_expediente')
                ->onDelete('restrict');

            $table->index(['id_expediente', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_expediente');
    }
};
