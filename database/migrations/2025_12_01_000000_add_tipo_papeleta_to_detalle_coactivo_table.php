<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoPapeletaToDetalleCoactivoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('detalle_coactivo', 'tipo_papeleta')) {
            Schema::table('detalle_coactivo', function (Blueprint $table) {
                $table->enum('tipo_papeleta', [
                    'Papeleta de Infracción Administrativa',
                    'Papeleta de Notificación Preventiva',
                ])->nullable()->after('papeleta_fecha');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('detalle_coactivo', 'tipo_papeleta')) {
            Schema::table('detalle_coactivo', function (Blueprint $table) {
                $table->dropColumn('tipo_papeleta');
            });
        }
    }
}
