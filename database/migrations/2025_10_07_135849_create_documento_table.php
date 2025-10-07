<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentoTable extends Migration
{
    public function up()
    {
        Schema::create('documento', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_expediente');
            $table->unsignedInteger('id_tipo');
            $table->string('codigo_doc', 50)->nullable();
            $table->date('fecha_doc')->nullable();
            $table->text('descripcion')->nullable();

            $table->foreign('id_expediente')->references('id')->on('expediente')->onDelete('restrict');
            $table->foreign('id_tipo')->references('id')->on('tipos_documentos')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('documento');
    }
}
