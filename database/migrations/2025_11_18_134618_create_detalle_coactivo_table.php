<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleCoactivoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_coactivo', function (Blueprint $table) {
            $table->increments('id_detalle');
            
            // FK a coactivos
            $table->unsignedInteger('id_coactivo');
            $table->foreign('id_coactivo')
                  ->references('id_coactivo')
                  ->on('coactivos')
                  ->onDelete('cascade'); // Si se elimina el expediente, se elimina el detalle

            // Documentos
            $table->string('res_sancion_codigo', 50)->nullable();
            $table->date('res_sancion_fecha')->nullable();
            $table->string('res_consentida_codigo', 50)->nullable();
            $table->date('res_consentida_fecha')->nullable();
            $table->string('papeleta_codigo', 50)->nullable();
            $table->date('papeleta_fecha')->nullable();

            // Tipo de papeleta: enum
            $table->enum('tipo_papeleta', [
                'Papeleta de Infracción Administrativa',
                'Papeleta de Notificación Preventiva',
            ])->nullable();

            // Infracción
            $table->string('codigo_infraccion', 50)->nullable();
            $table->text('descripcion_infraccion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_coactivo');
    }
}