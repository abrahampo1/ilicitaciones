<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->string('expediente', 500)->nullable()->after('datos_raiz');
            $table->string('status_code', 10)->nullable()->index()->after('expediente');
            $table->string('tipo_contrato_code', 10)->nullable()->index()->after('status_code');
            $table->string('subtipo_contrato_code', 10)->nullable()->after('tipo_contrato_code');
            $table->string('procedimiento_code', 10)->nullable()->index()->after('subtipo_contrato_code');
            $table->string('urgencia_code', 10)->nullable()->after('procedimiento_code');
            $table->decimal('importe_sin_iva', 15, 2)->nullable()->after('urgencia_code');
            $table->decimal('importe_con_iva', 15, 2)->nullable()->index()->after('importe_sin_iva');
            $table->decimal('valor_estimado', 15, 2)->nullable()->after('importe_con_iva');
            $table->json('cpv_codes')->nullable()->after('valor_estimado');
            $table->string('comunidad_autonoma')->nullable()->after('cpv_codes');
            $table->string('nuts_code', 20)->nullable()->after('comunidad_autonoma');
            $table->string('lugar_ejecucion', 500)->nullable()->after('nuts_code');
            $table->decimal('duracion', 8, 2)->nullable()->after('lugar_ejecucion');
            $table->string('duracion_unidad', 10)->nullable()->after('duracion');
            $table->string('adjudicatario_nombre', 500)->nullable()->after('duracion_unidad');
            $table->string('adjudicatario_nif', 50)->nullable()->index()->after('adjudicatario_nombre');
            $table->decimal('importe_adjudicacion_sin_iva', 15, 2)->nullable()->after('adjudicatario_nif');
            $table->decimal('importe_adjudicacion_con_iva', 15, 2)->nullable()->after('importe_adjudicacion_sin_iva');
            $table->date('fecha_presentacion_limite')->nullable()->after('importe_adjudicacion_con_iva');
            $table->date('fecha_inicio')->nullable()->after('fecha_presentacion_limite');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->date('fecha_adjudicacion')->nullable()->index()->after('fecha_fin');
            $table->date('fecha_formalizacion')->nullable()->after('fecha_adjudicacion');
            $table->string('resultado_code', 10)->nullable()->after('fecha_formalizacion');
            $table->unsignedInteger('num_ofertas')->nullable()->after('resultado_code');
            $table->string('external_id', 500)->nullable()->unique()->after('num_ofertas');
            $table->string('link', 1000)->nullable()->after('external_id');
            $table->timestamp('synced_at')->nullable()->after('link');

            $table->index(['tipo_contrato_code', 'status_code']);
        });

        Schema::table('organismos', function (Blueprint $table) {
            if (! Schema::hasColumn('organismos', 'dir3_code')) {
                $table->string('dir3_code', 50)->nullable()->index()->after('sitio_web');
            }
            if (! Schema::hasColumn('organismos', 'organismo_superior')) {
                $table->string('organismo_superior', 500)->nullable()->after('dir3_code');
            }
        });

        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'nif')) {
                $table->string('nif', 50)->nullable()->index()->after('identificador');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->dropIndex(['tipo_contrato_code', 'status_code']);
            $table->dropColumn([
                'expediente', 'status_code', 'tipo_contrato_code', 'subtipo_contrato_code',
                'procedimiento_code', 'urgencia_code',
                'importe_sin_iva', 'importe_con_iva', 'valor_estimado',
                'cpv_codes', 'comunidad_autonoma', 'nuts_code', 'lugar_ejecucion',
                'duracion', 'duracion_unidad',
                'adjudicatario_nombre', 'adjudicatario_nif',
                'importe_adjudicacion_sin_iva', 'importe_adjudicacion_con_iva',
                'fecha_presentacion_limite', 'fecha_inicio', 'fecha_fin',
                'fecha_adjudicacion', 'fecha_formalizacion',
                'resultado_code', 'num_ofertas',
                'external_id', 'link', 'synced_at',
            ]);
        });

        Schema::table('organismos', function (Blueprint $table) {
            $table->dropColumn(['dir3_code', 'organismo_superior']);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['nif']);
        });
    }
};
