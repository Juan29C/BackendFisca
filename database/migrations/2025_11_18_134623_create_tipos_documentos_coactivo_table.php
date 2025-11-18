<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposDocumentosCoactivoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipos_documentos_coactivo', function (Blueprint $table) {
            $table->increments('id_tipo_doc_coactivo'); // Cambiado 'id' por un nombre más específico
            $table->string('descripcion', 100)->unique(); // Aseguramos que la descripción sea única
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
        Schema::dropIfExists('tipos_documentos_coactivo');
    }
}