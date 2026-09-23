<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Descalificaciones que no salen de la lectora, como retirarse de la sala
     * o cometer una falta: el postulante queda NO APTO con puntaje 0 y el
     * motivo va a la columna de observaciones del Anexo 07.
     */
    public function up(): void
    {
        Schema::create('tbl_descalificacion', function (Blueprint $table) {
            $table->id('id_des');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro');
            $table->foreignId('id_ins')->unique()->constrained('tbl_inscripcion', 'id_ins');
            $table->string('motivo_des', 255);
            $table->foreignId('id_usu')->nullable()->constrained('tbl_usuario', 'id_usu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_descalificacion');
    }
};
