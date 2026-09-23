<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Una hoja de respuestas por inscripcion, tal como la califico la
         * lectora optica. `id_imp` es la carga que la escribio por ultima vez.
         */
        Schema::create('tbl_examen', function (Blueprint $table) {
            $table->id('id_exa');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro');
            $table->foreignId('id_ins')->unique()->constrained('tbl_inscripcion', 'id_ins');
            $table->foreignId('id_imp')->nullable()->constrained('tbl_importacion', 'id_imp');
            $table->string('apellidos_nombres_exa', 200)->nullable();
            $table->boolean('nombre_coincide_exa')->default(true);
            $table->decimal('puntaje_exa', 7, 3);
            $table->unsignedSmallInteger('aciertos_exa');
            $table->unsignedSmallInteger('errores_exa');
            $table->unsignedSmallInteger('blancos_exa');
            $table->unsignedSmallInteger('dobles_exa');
            $table->text('respuestas_exa')->nullable();
            $table->timestamps();

            $table->index(['id_pro', 'puntaje_exa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_examen');
    }
};
