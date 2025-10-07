<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTipoResolucionTable extends Migration
{
    public function up()
    {
        Schema::create('tipo_resolucion', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique();
            $table->text('descripcion')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tipo_resolucion');
    }
}
