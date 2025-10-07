<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResolucionTable extends Migration
{
    public function up()
    {
        Schema::create('resolucion', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_expediente');
            $table->string('numero', 50)->nullable();
            $table->date('fecha')->nullable();
            $table->string('lugar_emision', 100)->nullable();
            $table->text('texto')->nullable();
            $table->unsignedInteger('id_tipo_resolucion');

            $table->foreign('id_expediente')->references('id')->on('expediente')->onDelete('restrict');
            $table->foreign('id_tipo_resolucion')->references('id')->on('tipo_resolucion')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resolucion');
    }
}
