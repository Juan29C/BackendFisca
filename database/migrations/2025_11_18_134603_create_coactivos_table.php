<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoactivosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coactivos', function (Blueprint $table) {
            $table->increments('id_coactivo');
            $table->string('codigo_expediente_coactivo', 100)->unique();
            
            // FK a administrado
            $table->unsignedInteger('id_administrado');
            $table->foreign('id_administrado')
                  ->references('id')
                  ->on('administrado')
                  ->onDelete('restrict'); 

            $table->string('ejecutor_coactivo', 200);
            $table->string('auxiliar_coactivo', 200)->nullable();
            $table->date('fecha_inicio')->default(DB::raw('CURRENT_DATE()'));

            // Montos
            $table->decimal('monto_deuda', 10, 2)->default(0.00);
            $table->decimal('monto_costas', 10, 2)->default(0.00);
            $table->decimal('monto_gastos_admin', 10, 2)->default(0.00);

            $table->string('estado', 100)->default('En Ejecución');
            $table->text('observaciones')->nullable();

            $table->timestamps(); // Columnas created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coactivos');
    }
}