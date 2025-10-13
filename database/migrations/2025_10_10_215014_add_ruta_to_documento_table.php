<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRutaToDocumentoTable extends Migration
{
    public function up()
    {
        Schema::table('documento', function (Blueprint $table) {
            $table->string('ruta', 2048)->nullable()->after('descripcion');
            // Opcional: índice si vas a buscar por ruta
            // $table->index('ruta');
        });
    }

    public function down()
    {
        Schema::table('documento', function (Blueprint $table) {
            $table->dropColumn('ruta');
        });
    }
}
