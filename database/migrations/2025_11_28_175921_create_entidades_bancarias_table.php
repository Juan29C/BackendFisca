<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntidadesBancariasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entidades_bancarias', function (Blueprint $table) {
            $table->increments('id_entidad_bancaria'); 
            $table->string('nombre', 255);
            $table->string('ruc', 15)->unique(); 
            $table->string('direccion', 255);

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
        Schema::dropIfExists('entidades_bancarias');
    }
}
