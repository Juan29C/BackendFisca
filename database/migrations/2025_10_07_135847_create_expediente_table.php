<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpedienteTable extends Migration
{
    public function up()
    {
        Schema::create('expediente', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo_expediente', 50);
            
            $table->date('fecha_inicio');
            $table->unsignedInteger('id_administrado');
            $table->unsignedInteger('id_estado');
            $table->date('fecha_vencimiento')->nullable();

            $table->foreign('id_administrado')->references('id')->on('administrado')->onDelete('restrict');
            $table->foreign('id_estado')->references('id')->on('estado_expediente')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expediente');
    }
}
