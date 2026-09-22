<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_proceso', function (Blueprint $table) {
            $table->id('id_pro');
            $table->string('codigo_pro', 60)->unique();
            $table->string('nombre_pro', 255);
            $table->string('entidad_pro', 150);
            $table->string('regimen_pro', 150)->nullable();
            $table->date('fecha_evaluacion_pro')->nullable();
            $table->string('hora_evaluacion_pro', 60)->nullable();
            $table->string('lugar_evaluacion_pro', 255)->nullable();
            $table->unsignedTinyInteger('estado_pro')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_puesto', function (Blueprint $table) {
            $table->id('id_pue');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro');
            $table->string('codigo_pue', 20);
            $table->string('nombre_pue', 150);
            $table->unsignedTinyInteger('estado_pue')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id_pro', 'codigo_pue']);
        });

        Schema::create('tbl_inscripcion', function (Blueprint $table) {
            $table->id('id_ins');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro');
            $table->foreignId('id_pue')->constrained('tbl_puesto', 'id_pue');
            $table->unsignedInteger('numero_ins')->nullable();
            $table->string('documento_ins', 12);
            $table->string('apellidos_nombres_ins', 200);
            $table->timestamps();

            $table->unique(['id_pro', 'documento_ins']);
            $table->index(['id_pro', 'id_pue']);
        });

        Schema::create('tbl_importacion', function (Blueprint $table) {
            $table->id('id_imp');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro');
            $table->foreignId('id_usu')->nullable()->constrained('tbl_usuario', 'id_usu');
            $table->string('tipo_imp', 30);
            $table->string('archivo_imp', 255);
            $table->char('hash_imp', 64);
            $table->unsignedInteger('filas_imp')->default(0);
            $table->unsignedInteger('creados_imp')->default(0);
            $table->unsignedInteger('actualizados_imp')->default(0);
            $table->json('errores_imp')->nullable();
            $table->boolean('aplicada_imp')->default(false);
            $table->timestamps();

            $table->index(['id_pro', 'tipo_imp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_importacion');
        Schema::dropIfExists('tbl_inscripcion');
        Schema::dropIfExists('tbl_puesto');
        Schema::dropIfExists('tbl_proceso');
    }
};
