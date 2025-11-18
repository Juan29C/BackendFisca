<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentoCoactivoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documento_coactivo', function (Blueprint $table) {
            $table->increments('id_doc_coactivo'); // Cambiado 'id' por un nombre más específico
            
            // Llave foránea a la tabla 'coactivos' (el expediente)
            $table->unsignedInteger('id_coactivo'); 
            $table->foreign('id_coactivo')
                  ->references('id_coactivo') // Columna PRIMARY KEY de la tabla coactivos
                  ->on('coactivos')
                  ->onDelete('cascade'); // Si se elimina el coactivo, se eliminan sus documentos

            // Llave foránea a la tabla 'tipos_documentos_coactivo'
            $table->unsignedInteger('id_tipo_doc_coactivo');
            $table->foreign('id_tipo_doc_coactivo')
                  ->references('id_tipo_doc_coactivo')
                  ->on('tipos_documentos_coactivo')
                  ->onDelete('restrict');

            $table->string('codigo_doc', 50)->nullable();
            $table->date('fecha_doc')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ruta', 2048)->nullable(); // Para guardar la ruta del archivo físico

            $table->timestamps(); // Opcional, pero recomendado en Laravel
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documento_coactivo');
    }
}