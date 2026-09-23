<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos del pie del Anexo 07 (resultados de la evaluacion tecnica): el
     * puntaje minimo para ser apto, hasta cuando y a que correo envian sus
     * documentos los aptos, y la fecha, la ciudad y el comite que publican.
     */
    public function up(): void
    {
        Schema::table('tbl_proceso', function (Blueprint $table) {
            $table->decimal('puntaje_minimo_pro', 4, 2)->default(3.9)->after('lugar_evaluacion_pro');
            $table->date('fecha_limite_documentos_pro')->nullable()->after('puntaje_minimo_pro');
            $table->string('correo_documentos_pro', 150)->nullable()->after('fecha_limite_documentos_pro');
            $table->date('fecha_resultados_pro')->nullable()->after('correo_documentos_pro');
            $table->string('ciudad_resultados_pro', 60)->default('Pucallpa')->after('fecha_resultados_pro');
            $table->string('comite_pro', 20)->nullable()->after('ciudad_resultados_pro');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_proceso', function (Blueprint $table) {
            $table->dropColumn([
                'puntaje_minimo_pro',
                'fecha_limite_documentos_pro',
                'correo_documentos_pro',
                'fecha_resultados_pro',
                'ciudad_resultados_pro',
                'comite_pro',
            ]);
        });
    }
};
