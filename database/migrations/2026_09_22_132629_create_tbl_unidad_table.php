<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidades de organizacion de la Corte (juzgados, salas, modulos). Cada
     * puesto convocado pertenece a una, y a traves del puesto sus postulantes.
     * El catalogo es de la Corte, no de un proceso: la misma sala aparece en
     * varias convocatorias.
     */
    public function up(): void
    {
        Schema::create('tbl_unidad', function (Blueprint $table) {
            $table->id('id_uni');
            $table->string('nombre_uni', 150)->unique();
            $table->unsignedTinyInteger('estado_uni')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('tbl_puesto', function (Blueprint $table) {
            $table->foreignId('id_uni')->nullable()->after('id_pro')->constrained('tbl_unidad', 'id_uni');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_puesto', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_uni');
        });

        Schema::dropIfExists('tbl_unidad');
    }
};
