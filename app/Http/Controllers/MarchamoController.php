<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MarchamoController extends Controller
{
    /**
     * Consulta general de marchamos dentro del sistema.
     */
    public function index(Request $request): View
    {
        return view(
            'marchamos.index',
            $this->obtenerDatosConsulta($request)
        );
    }

    /**
     * Consulta general de marchamos en ventana independiente.
     */
    public function consultaVentana(Request $request): View
    {
        return view(
            'marchamos.index-ventana',
            $this->obtenerDatosConsulta($request)
        );
    }

    /**
     * Detalle histórico de marchamos asociados a una unidad.
     */
    public function detalleUnidad(
        Request $request,
        Unidad $unidad
    ): View {
        return view(
            'marchamos.detalle-unidad',
            $this->obtenerDatosDetalleUnidad($unidad)
        );
    }

    /**
     * Detalle histórico en ventana independiente.
     */
    public function detalleUnidadVentana(
        Request $request,
        Unidad $unidad
    ): View {
        return view(
            'marchamos.detalle-unidad-ventana',
            $this->obtenerDatosDetalleUnidad($unidad)
        );
    }

    /**
     * Prepara filtros, selectores y resultados de la consulta general.
     *
     * Esta consulta es histórica. No filtra por estado operativo de empresa,
     * unidad o licencia. Una condición inactiva, pendiente o vencida impide
     * operaciones nuevas, pero no oculta registros previamente existentes.
     */
    private function obtenerDatosConsulta(
        Request $request
    ): array {
        $request->validate([
            'unidad_ids' => ['nullable', 'array'],
            'unidad_ids.*' => [
                'integer',
                'distinct',
                'exists:unidades,id',
            ],
        ]);

        $user = Auth::user();

        $esUsuarioDieselCop = is_null(
            $user->empresa_id
        );

        $busquedaEmpresa = trim(
            (string) $request->input(
                'busqueda_empresa',
                ''
            )
        );

        $busquedaPlaca = trim(
            (string) $request->input(
                'busqueda_placa',
                ''
            )
        );

        /*
         * Compatibilidad con ambas versiones de filtros:
         *
         * - empresa_ids[]
         * - empresa_id
         */
        $empresaIds = collect(
            $request->input(
                'empresa_ids',
                []
            )
        )
            ->when(
                filled(
                    $request->input('empresa_id')
                ),
                function ($collection) use ($request) {
                    return $collection->push(
                        $request->input('empresa_id')
                    );
                }
            )
            ->filter(
                fn ($id) => filled($id)
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->all();

        /*
         * Compatibilidad con:
         *
         * - unidad_ids[]
         * - búsqueda directa
         */
        $unidadIds = collect(
            $request->input(
                'unidad_ids',
                []
            )
        )
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $unidadId = $request->input(
            'unidad_id'
        );

        /*
         * Un usuario perteneciente a una empresa solo puede consultar
         * información histórica de su propia empresa.
         */
        if (! $esUsuarioDieselCop) {
            $empresaIds = [
                (int) $user->empresa_id,
            ];
        }

        $empresaId = $empresaIds[0] ?? null;
        $unidadId = filled($unidadId)
            ? (int) $unidadId
            : ($unidadIds[0] ?? null);

        $consultaEjecutada = $request->boolean(
            'consultar'
        );

        /*
         * La empresa obligatoria del usuario empresarial limita el alcance,
         * pero no ejecuta automáticamente la consulta.
         */
        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaPlaca)
            || count($unidadIds) > 0
            || filled($unidadId)
            || (
                $esUsuarioDieselCop
                && count($empresaIds) > 0
            );

        /*
         * En Consulta se muestran empresas activas e inactivas.
         * El estado de la empresa no elimina su historial.
         */
        $empresas = Empresa::query()
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'id',
                        $user->empresa_id
                    );
                }
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        /*
         * Una unidad es consultable cuando posee puntos de seguridad,
         * marchamos actuales o marchamos históricos.
         *
         * No se exige que la empresa, unidad o licencia estén activas.
         */
        $baseUnidadesQuery = Unidad::query()
            ->with([
                'empresa',
                'licencia',
            ])
            ->withCount([
                'puntosSeguridad as total_puntos' =>
                    function ($query) {
                        $query->where(
                            'estado',
                            'activo'
                        );
                    },

                'puntosSeguridad as puntos_asignados' =>
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->whereNotNull(
                                'marchamo_actual_id'
                            );
                    },

                'marchamos as marchamos_activos' =>
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->where(
                                'activo_actual',
                                true
                            );
                    },

                'marchamos as marchamos_historicos' =>
                    function ($query) {
                        $query->whereIn(
                            'estado',
                            [
                                'reemplazado',
                                'anulado',
                            ]
                        );
                    },
            ])
            ->where(
                function ($query) {
                    $query
                        ->whereHas(
                            'puntosSeguridad'
                        )
                        ->orWhereHas(
                            'marchamos'
                        );
                }
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'empresa_id',
                        $user->empresa_id
                    );
                }
            );

        /*
         * El selector de unidades contiene todas las unidades consultables,
         * incluyendo las pertenecientes a empresas o licencias inactivas.
         */
        $unidadesSelector = (clone $baseUnidadesQuery)
            ->with('empresa')
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();

        /*
         * Se conserva esta colección para compatibilidad con vistas
         * existentes y filtros anteriores.
         */
        $unidades = (clone $baseUnidadesQuery)
            ->orderBy('placa')
            ->get();

        $unidadesConCobertura = (clone $baseUnidadesQuery)
            ->when(
                $hayFiltros
                && filled($busquedaEmpresa),
                function ($query) use (
                    $busquedaEmpresa
                ) {
                    $query->whereHas(
                        'empresa',
                        function (
                            $empresaQuery
                        ) use (
                            $busquedaEmpresa
                        ) {
                            $empresaQuery
                                ->where(
                                    'nombre_legal',
                                    'like',
                                    '%' . $busquedaEmpresa . '%'
                                )
                                ->orWhere(
                                    'nombre_comercial',
                                    'like',
                                    '%' . $busquedaEmpresa . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                $hayFiltros
                && count($empresaIds) > 0,
                function ($query) use (
                    $empresaIds
                ) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    );
                }
            )
            ->when(
                $hayFiltros
                && filled($busquedaPlaca),
                function ($query) use (
                    $busquedaPlaca
                ) {
                    $query->where(
                        'placa',
                        'like',
                        '%' . $busquedaPlaca . '%'
                    );
                }
            )
            ->when(
                $hayFiltros
                && count($unidadIds) > 0,
                function ($query) use ($unidadIds) {
                    $query->whereIn(
                        'id',
                        $unidadIds
                    );
                }
            )
            ->when(
                $hayFiltros
                && filled($unidadId),
                function ($query) use (
                    $unidadId
                ) {
                    $query->where(
                        'id',
                        $unidadId
                    );
                }
            )
            ->when(
                ! $hayFiltros,
                function ($query) {
                    $query->whereRaw(
                        '1 = 0'
                    );
                }
            )
            ->orderBy('placa')
            ->get();

        return [
            'unidadesConCobertura' =>
                $unidadesConCobertura,

            'empresas' =>
                $empresas,

            'unidades' =>
                $unidades,

            'unidadesSelector' =>
                $unidadesSelector,

            'busquedaEmpresa' =>
                $busquedaEmpresa,

            'busquedaPlaca' =>
                $busquedaPlaca,

            'empresaIds' =>
                $empresaIds,

            'unidadIds' =>
                $unidadIds,

            /*
             * Variables simples conservadas para compatibilidad.
             */
            'empresaId' =>
                $empresaId,

            'unidadId' =>
                $unidadId,

            'hayFiltros' =>
                $hayFiltros,

            'consultaEjecutada' =>
                $consultaEjecutada,

            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,
        ];
    }

    /**
     * Prepara el historial completo de marchamos de una unidad.
     *
     * El detalle sigue disponible aunque la empresa, la unidad o la licencia
     * estén inactivas, pendientes o vencidas.
     */
    private function obtenerDatosDetalleUnidad(
        Unidad $unidad
    ): array {
        $this->autorizarAccesoUnidad(
            $unidad
        );

        $unidad->load([
            'empresa',
            'licencia',
        ]);

        $unidad->loadCount([
            'puntosSeguridad as total_puntos' =>
                function ($query) {
                    $query->where(
                        'estado',
                        'activo'
                    );
                },

            'puntosSeguridad as puntos_asignados' =>
                function ($query) {
                    $query
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->whereNotNull(
                            'marchamo_actual_id'
                        );
                },

            'marchamos as marchamos_activos' =>
                function ($query) {
                    $query
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'activo_actual',
                            true
                        );
                },

            'marchamos as marchamos_historicos' =>
                function ($query) {
                    $query->whereIn(
                        'estado',
                        [
                            'reemplazado',
                            'anulado',
                        ]
                    );
                },
        ]);

        $marchamos = Marchamo::query()
            ->with([
                'empresa',
                'unidad',
                'puntoSeguridad',
            ])
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->when(
                ! is_null(
                    Auth::user()->empresa_id
                ),
                function ($query) {
                    $query->where(
                        'empresa_id',
                        Auth::user()->empresa_id
                    );
                }
            )
            ->orderByRaw(
                "
                CASE
                    WHEN estado = 'activo' THEN 1
                    WHEN estado = 'reemplazado' THEN 2
                    WHEN estado = 'anulado' THEN 3
                    ELSE 4
                END
                "
            )
            ->orderByDesc(
                'fecha_activacion'
            )
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $totalMarchamos = Marchamo::query()
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->when(
                ! is_null(
                    Auth::user()->empresa_id
                ),
                function ($query) {
                    $query->where(
                        'empresa_id',
                        Auth::user()->empresa_id
                    );
                }
            )
            ->count();

        return [
            'unidad' =>
                $unidad,

            'marchamos' =>
                $marchamos,

            'totalMarchamos' =>
                $totalMarchamos,
        ];
    }

    /**
     * Control de acceso multiempresa.
     *
     * La consulta histórica no se bloquea por estados operativos, pero un
     * usuario de empresa nunca puede consultar unidades de otra empresa.
     */
    private function autorizarAccesoUnidad(
        Unidad $unidad
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $unidad->empresa_id
                !== (int) $user->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para consultar esta unidad.'
            );
        }
    }
}
