<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResolucionDocumentoTable extends Migration
{
    public function up()
    {
        Schema::create('resolucion_documento', function (Blueprint $table) {
            $table->unsignedInteger('id_resolucion');
            $table->unsignedInteger('id_documento');

            $table->primary(['id_resolucion', 'id_documento']);

            $table->foreign('id_resolucion')->references('id')->on('resolucion')->onDelete('cascade');
            $table->foreign('id_documento')->references('id')->on('documento')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resolucion_documento');
    }
}
