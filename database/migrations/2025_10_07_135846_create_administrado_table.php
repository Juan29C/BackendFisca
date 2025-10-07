<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdministradoTable extends Migration
{
    public function up()
    {
        Schema::create('administrado', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('tipo', ['natural', 'juridica']);
            $table->string('dni', 15)->nullable();
            $table->string('ruc', 15)->nullable();
            $table->string('nombre_completo', 200)->nullable();
            $table->string('razon_social', 150)->nullable();
            $table->string('domicilio', 255)->nullable();
            $table->string('vinculo', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('administrado');
    }
}
