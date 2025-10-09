<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadoExpedienteTable extends Migration
{
    public function up()
    {
        Schema::create('estado_expediente', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique();
        });
    }

    public function down()
    {
        Schema::dropIfExists('estado_expediente');
    }
}
