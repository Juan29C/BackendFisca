<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposDocumentosTable extends Migration
{
    public function up()
    {
        Schema::create('tipos_documentos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('descripcion', 100);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tipos_documentos');
    }
}
