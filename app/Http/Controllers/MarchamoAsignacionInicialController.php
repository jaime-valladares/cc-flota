<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarchamoAsignacionInicialController extends Controller
{
    public function index(Request $request): View
    {
        return view(
            'marchamos.asignacion-inicial.index',
            $this->datosIndex($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'marchamos.asignacion-inicial.index-ventana',
            $this->datosIndex($request)
        );
    }

    public function show(Unidad $unidad): View|RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->loadMissing([
            'empresa',
            'licencia',
        ]);

        $this->validarEmpresaActivaUnidad($unidad);

        if ($unidad->estado === 'activa') {
            return redirect()
                ->route(
                    'marchamos.detalle-unidad',
                    $unidad
                )
                ->with(
                    'success',
                    'La asignación inicial de esta unidad ya fue completada. Use Consulta de marchamos o Administración de marchamos para continuar.'
                );
        }

        return view(
            'marchamos.asignacion-inicial.show',
            $this->datosShow($unidad)
        );
    }

    public function showVentana(Unidad $unidad): View|RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->loadMissing([
            'empresa',
            'licencia',
        ]);

        $this->validarEmpresaActivaUnidad($unidad);

        if ($unidad->estado === 'activa') {
            return redirect()
                ->route(
                    'marchamos.detalle-unidad.ventana',
                    $unidad
                )
                ->with(
                    'success',
                    'La asignación inicial de esta unidad ya fue completada. Use Consulta de marchamos o Administración de marchamos para continuar.'
                );
        }

        return view(
            'marchamos.asignacion-inicial.show-ventana',
            $this->datosShow($unidad)
        );
    }

    public function guardarAvance(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
        ]);

        $this->validarUnidadAsignable($unidad);

        $validated = $request->validate(
            [
                'marchamos' => [
                    'nullable',
                    'array',
                ],

                'marchamos.*' => [
                    'nullable',
                    'string',
                    'regex:/^\d{7}$/',
                ],

                'return_to' => [
                    'nullable',
                    'string',
                    'in:ventana',
                ],
            ],
            [
                'marchamos.*.regex' =>
                    'Cada código de marchamo debe contener exactamente 7 dígitos. Ejemplo: 0006387.',
            ]
        );

        $marchamosFormulario = collect(
            $validated['marchamos'] ?? []
        )->mapWithKeys(
            function ($codigo, $puntoId) {
                $codigoNormalizado = is_string($codigo)
                    ? trim($codigo)
                    : null;

                return [
                    (int) $puntoId => filled($codigoNormalizado)
                        ? $codigoNormalizado
                        : null,
                ];
            }
        );

        if ($marchamosFormulario->isEmpty()) {
            return back()->with(
                'success',
                'No se recibieron cambios de marchamos para guardar.'
            );
        }

        $codigosIngresados = $marchamosFormulario
            ->filter(fn ($codigo) => filled($codigo))
            ->values();

        $codigosDuplicadosEnFormulario = $codigosIngresados
            ->duplicates()
            ->values();

        if ($codigosDuplicadosEnFormulario->isNotEmpty()) {
            throw ValidationException::withMessages([
                'marchamos' =>
                    'Hay códigos de marchamo repetidos en el formulario: '
                    . $codigosDuplicadosEnFormulario->implode(', '),
            ]);
        }

        DB::transaction(
            function () use (
                $unidad,
                $marchamosFormulario,
                $codigosIngresados
            ): void {
                $unidadBloqueada = Unidad::query()
                    ->with([
                        'empresa',
                        'licencia',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($unidad->id);

                $this->validarUnidadAsignable(
                    $unidadBloqueada
                );

                $puntoIdsFormulario = $marchamosFormulario
                    ->keys()
                    ->map(fn ($puntoId) => (int) $puntoId)
                    ->unique()
                    ->values();

                $puntos = PuntoSeguridadUnidad::query()
                    ->where(
                        'unidad_id',
                        $unidadBloqueada->id
                    )
                    ->whereIn(
                        'id',
                        $puntoIdsFormulario
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if (
                    $puntos->count()
                    !== $puntoIdsFormulario->count()
                ) {
                    throw ValidationException::withMessages([
                        'marchamos' =>
                            'Uno o más puntos enviados no pertenecen a esta unidad.',
                    ]);
                }

                foreach ($puntos as $punto) {
                    if ($punto->estado !== 'activo') {
                        throw ValidationException::withMessages([
                            "marchamos.{$punto->id}" =>
                                'No se puede asignar marchamo a un punto inactivo.',
                        ]);
                    }
                }

                if ($codigosIngresados->isNotEmpty()) {
                    $codigosNoDisponibles = Marchamo::query()
                        ->whereIn(
                            'codigo_marchamo',
                            $codigosIngresados
                        )
                        ->where(
                            function ($query) use (
                                $unidadBloqueada,
                                $puntoIdsFormulario
                            ) {
                                $query
                                    ->whereNull('unidad_id')
                                    ->orWhere(
                                        'unidad_id',
                                        '!=',
                                        $unidadBloqueada->id
                                    )
                                    ->orWhere(
                                        'origen_creacion',
                                        '!=',
                                        'asignacion_inicial'
                                    )
                                    ->orWhere(
                                        function ($mismaUnidadQuery) use (
                                            $unidadBloqueada,
                                            $puntoIdsFormulario
                                        ) {
                                            $mismaUnidadQuery
                                                ->where(
                                                    'unidad_id',
                                                    $unidadBloqueada->id
                                                )
                                                ->whereNotIn(
                                                    'punto_seguridad_id',
                                                    $puntoIdsFormulario
                                                );
                                        }
                                    );
                            }
                        )
                        ->pluck('codigo_marchamo')
                        ->unique()
                        ->values();

                    if ($codigosNoDisponibles->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            'marchamos' =>
                                'Los siguientes marchamos no están disponibles para esta asignación inicial: '
                                . $codigosNoDisponibles->implode(', '),
                        ]);
                    }
                }

                $marchamosProvisionales = Marchamo::query()
                    ->where(
                        'unidad_id',
                        $unidadBloqueada->id
                    )
                    ->where(
                        'origen_creacion',
                        'asignacion_inicial'
                    )
                    ->where(
                        function ($query) use (
                            $puntoIdsFormulario,
                            $codigosIngresados
                        ) {
                            $query->whereIn(
                                'punto_seguridad_id',
                                $puntoIdsFormulario
                            );

                            if ($codigosIngresados->isNotEmpty()) {
                                $query->orWhereIn(
                                    'codigo_marchamo',
                                    $codigosIngresados
                                );
                            }
                        }
                    )
                    ->lockForUpdate()
                    ->get();

                $marchamosPorCodigo = $marchamosProvisionales
                    ->keyBy('codigo_marchamo');

                foreach ($puntos as $punto) {
                    $punto->update([
                        'marchamo_actual_id' => null,
                        'estado_asignacion' => 'pendiente',
                        'actualizado_por' => Auth::id(),
                    ]);
                }

                $codigosDeseados = $codigosIngresados
                    ->unique()
                    ->values();

                $marchamosAEliminar = $marchamosProvisionales
                    ->filter(
                        function ($marchamo) use (
                            $puntoIdsFormulario,
                            $codigosDeseados
                        ) {
                            return $puntoIdsFormulario->contains(
                                (int) $marchamo->punto_seguridad_id
                            )
                                && ! $codigosDeseados->contains(
                                    $marchamo->codigo_marchamo
                                );
                        }
                    );

                foreach ($marchamosAEliminar as $marchamo) {
                    $marchamo->delete();
                }

                foreach (
                    $marchamosFormulario
                    as $puntoId => $codigoMarchamo
                ) {
                    if (blank($codigoMarchamo)) {
                        continue;
                    }

                    $punto = $puntos->get(
                        (int) $puntoId
                    );

                    $marchamo = $marchamosPorCodigo->get(
                        $codigoMarchamo
                    );

                    if ($marchamo) {
                        $marchamo->update([
                            'empresa_id' =>
                                $unidadBloqueada->empresa_id,

                            'unidad_id' =>
                                $unidadBloqueada->id,

                            'punto_seguridad_id' =>
                                $punto->id,

                            'fecha_activacion' =>
                                $marchamo->fecha_activacion ?: now(),

                            'estado' =>
                                'activo',

                            'activo_actual' =>
                                true,

                            'fecha_desactivacion' =>
                                null,

                            'motivo_desactivacion' =>
                                null,

                            'actualizado_por' =>
                                Auth::id(),
                        ]);
                    } else {
                        $marchamo = Marchamo::create([
                            'empresa_id' =>
                                $unidadBloqueada->empresa_id,

                            'unidad_id' =>
                                $unidadBloqueada->id,

                            'punto_seguridad_id' =>
                                $punto->id,

                            'codigo_marchamo' =>
                                $codigoMarchamo,

                            'fecha_activacion' =>
                                now(),

                            'estado' =>
                                'activo',

                            'activo_actual' =>
                                true,

                            'fecha_desactivacion' =>
                                null,

                            'motivo_desactivacion' =>
                                null,

                            'origen_creacion' =>
                                'asignacion_inicial',

                            'creado_por' =>
                                Auth::id(),

                            'actualizado_por' =>
                                Auth::id(),
                        ]);
                    }

                    $punto->update([
                        'marchamo_actual_id' =>
                            $marchamo->id,

                        'estado_asignacion' =>
                            'asignado',

                        'actualizado_por' =>
                            Auth::id(),
                    ]);
                }
            }
        );

        return back()->with(
            'success',
            'Avance de asignación inicial guardado correctamente.'
        );
    }

    public function finalizar(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad',
        ]);

        $this->validarUnidadAsignable($unidad);

        $validated = $request->validate([
            'return_to' => [
                'nullable',
                'string',
                'in:ventana',
            ],
        ]);

        DB::transaction(
            function () use ($unidad): void {
                $unidadBloqueada = Unidad::query()
                    ->with([
                        'empresa',
                        'licencia',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($unidad->id);

                $this->validarUnidadAsignable(
                    $unidadBloqueada
                );

                $puntos = PuntoSeguridadUnidad::query()
                    ->where(
                        'unidad_id',
                        $unidadBloqueada->id
                    )
                    ->where('estado', 'activo')
                    ->where('requiere_marchamo', true)
                    ->lockForUpdate()
                    ->get();

                $totalPuntos = $puntos->count();

                $puntosPendientes = $puntos
                    ->whereNull('marchamo_actual_id')
                    ->count();

                if ($totalPuntos === 0) {
                    throw ValidationException::withMessages([
                        'finalizar' =>
                            'La unidad no tiene puntos de seguridad activos que requieran marchamo.',
                    ]);
                }

                if ($puntosPendientes > 0) {
                    throw ValidationException::withMessages([
                        'finalizar' =>
                            "La asignación no está completa. Aún hay {$puntosPendientes} puntos pendientes.",
                    ]);
                }

                $unidadBloqueada->update([
                    'estado' =>
                        'activa',

                    'fecha_inactivacion' =>
                        null,

                    'inactivado_por' =>
                        null,

                    'motivo_inactivacion' =>
                        null,

                    'actualizado_por' =>
                        Auth::id(),
                ]);
            }
        );

        $ruta = ($validated['return_to'] ?? null) === 'ventana'
            ? 'marchamos.asignacion-inicial.index.ventana'
            : 'marchamos.asignacion-inicial.index';

        return redirect()
            ->route(
                $ruta,
                [
                    'empresa_ids' => [
                        $unidad->empresa_id,
                    ],

                    'unidad_ids' => [
                        $unidad->id,
                    ],

                    'consultar' => 1,
                ]
            )
            ->with(
                'success',
                'Asignación inicial finalizada correctamente. La unidad ahora está activa.'
            );
    }

    private function datosIndex(Request $request): array
    {
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
         * Empresas seleccionadas.
         *
         * Se mantiene compatibilidad con el filtro anterior empresa_id.
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
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        /*
         * Unidades seleccionadas.
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
            ->values();

        /*
         * Compatibilidad con unidad_id durante la transición de vistas.
         */
        $unidadIdAnterior = $request->input(
            'unidad_id'
        );

        if (filled($unidadIdAnterior)) {
            $unidadAnteriorExiste = Unidad::query()
                ->when(
                    ! $esUsuarioDieselCop,
                    function ($query) use ($user) {
                        $query->where(
                            'empresa_id',
                            $user->empresa_id
                        );
                    }
                )
                ->where(
                    'id',
                    $unidadIdAnterior
                )
                ->exists();

            if ($unidadAnteriorExiste) {
                $unidadIds->push((int) $unidadIdAnterior);

                $unidadIds = $unidadIds
                    ->unique()
                    ->values();
            }
        }

        /*
         * Usuarios pertenecientes a una empresa no pueden seleccionar
         * empresas distintas.
         */
        if (! $esUsuarioDieselCop) {
            $empresaIds = collect([
                (int) $user->empresa_id,
            ]);
        }

        $consultaEjecutada = $request->boolean(
            'consultar'
        );

        /*
         * La empresa obligatoria del usuario empresarial limita el alcance,
         * pero no ejecuta automáticamente la búsqueda.
         */
        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaPlaca)
            || $unidadIds->isNotEmpty()
            || (
                $esUsuarioDieselCop
                && $empresaIds->isNotEmpty()
            );

        $hoy = Carbon::today()->toDateString();

        /*
         * Solo empresas activas pueden participar en el flujo operativo.
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
            ->where(
                'estado',
                'activa'
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        /*
         * Base de unidades elegibles para asignación inicial.
         */
        $baseUnidadesQuery = Unidad::query()
            ->with([
                'empresa',
                'licencia',
            ])
            ->withCount([
                'puntosSeguridad as total_puntos' =>
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->where(
                                'requiere_marchamo',
                                true
                            );
                    },

                'puntosSeguridad as puntos_asignados' =>
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->where(
                                'requiere_marchamo',
                                true
                            )
                            ->whereNotNull(
                                'marchamo_actual_id'
                            );
                    },
            ])
            ->where(
                'estado',
                'registrada'
            )
            ->whereHas(
                'empresa',
                function ($query) {
                    $query->where(
                        'estado',
                        'activa'
                    );
                }
            )
            ->whereHas(
                'licencia',
                function ($query) use ($hoy) {
                    $query
                        ->where(
                            'estado',
                            'activa'
                        )
                        ->whereDate(
                            'fecha_activacion',
                            '<=',
                            $hoy
                        )
                        ->whereDate(
                            'fecha_vencimiento',
                            '>=',
                            $hoy
                        );
                }
            )
            ->whereHas(
                'puntosSeguridad',
                function ($query) {
                    $query
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'requiere_marchamo',
                            true
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
         * Las unidades disponibles se limitan a las empresas seleccionadas.
         *
         * Para usuarios de empresa, empresaIds contiene automáticamente
         * su empresa.
         */
        $unidadesSelector = (clone $baseUnidadesQuery)
            ->with('empresa')
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();

        /*
         * Colección conservada para compatibilidad con vistas anteriores.
         */
        $unidades = (clone $baseUnidadesQuery)
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->orderBy('placa')
            ->get();

        $unidadesDisponibles = (clone $baseUnidadesQuery)
            ->when(
                $hayFiltros && filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
                    $query->whereHas(
                        'empresa',
                        function ($empresaQuery) use (
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
                $hayFiltros && $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->when(
                $hayFiltros && filled($busquedaPlaca),
                function ($query) use ($busquedaPlaca) {
                    $query->where(
                        'placa',
                        'like',
                        '%' . $busquedaPlaca . '%'
                    );
                }
            )
            ->when(
                $hayFiltros && $unidadIds->isNotEmpty(),
                function ($query) use ($unidadIds) {
                    $query->whereIn(
                        'id',
                        $unidadIds->all()
                    );
                }
            )
            ->when(
                ! $hayFiltros,
                function ($query) {
                    $query->whereRaw('1 = 0');
                }
            )
            ->orderBy('placa')
            ->paginate(15)
            ->withQueryString();

        return [
            'empresas' =>
                $empresas,

            'unidades' =>
                $unidades,

            'unidadesSelector' =>
                $unidadesSelector,

            'unidadesDisponibles' =>
                $unidadesDisponibles,

            'busquedaEmpresa' =>
                $busquedaEmpresa,

            'busquedaPlaca' =>
                $busquedaPlaca,

            'empresaIds' =>
                $empresaIds->all(),

            'unidadIds' =>
                $unidadIds->all(),

            /*
             * Variables temporales para compatibilidad con vistas simples.
             */
            'empresaId' =>
                $empresaIds->first(),

            'unidadId' =>
                $unidadIdAnterior,

            'hayFiltros' =>
                $hayFiltros,

            'consultaEjecutada' =>
                $consultaEjecutada,

            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,
        ];
    }

    private function datosShow(Unidad $unidad): array
    {
        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad' => function ($query) {
                $query
                    ->where(
                        'estado',
                        'activo'
                    )
                    ->where(
                        'requiere_marchamo',
                        true
                    )
                    ->orderBy('orden');
            },
            'puntosSeguridad.marchamoActual',
        ]);

        $this->validarUnidadAsignable($unidad);

        $totalPuntos = $unidad
            ->puntosSeguridad
            ->count();

        $puntosAsignados = $unidad
            ->puntosSeguridad
            ->whereNotNull(
                'marchamo_actual_id'
            )
            ->count();

        $puntosPendientes = max(
            $totalPuntos - $puntosAsignados,
            0
        );

        $porcentajeAvance = $totalPuntos > 0
            ? round(
                ($puntosAsignados / $totalPuntos)
                * 100
            )
            : 0;

        return [
            'unidad' =>
                $unidad,

            'totalPuntos' =>
                $totalPuntos,

            'puntosAsignados' =>
                $puntosAsignados,

            'puntosPendientes' =>
                $puntosPendientes,

            'porcentajeAvance' =>
                $porcentajeAvance,
        ];
    }

    private function validarUnidadAsignable(
        Unidad $unidad
    ): void {
        $unidad->loadMissing([
            'empresa',
            'licencia',
        ]);

        $this->validarEmpresaActivaUnidad(
            $unidad
        );

        if ($unidad->estado === 'inactiva') {
            abort(
                403,
                'No se puede operar sobre marchamos porque la unidad está inactiva.'
            );
        }

        if ($unidad->estado !== 'registrada') {
            abort(
                422,
                'La unidad debe estar registrada para completar la asignación inicial.'
            );
        }

        if (! $unidad->licencia) {
            abort(
                422,
                'La unidad debe tener una licencia registrada antes de asignar marchamos.'
            );
        }

        $this->validarLicenciaOperativa(
            $unidad
        );

        if (
            ! $unidad->puntosSeguridad()
                ->where(
                    'estado',
                    'activo'
                )
                ->where(
                    'requiere_marchamo',
                    true
                )
                ->exists()
        ) {
            abort(
                422,
                'La unidad no tiene puntos de seguridad activos que requieran marchamo.'
            );
        }
    }

    private function validarLicenciaOperativa(
        Unidad $unidad
    ): void {
        $licencia = $unidad->licencia;

        if (! $licencia) {
            abort(
                422,
                'La unidad no tiene una licencia registrada.'
            );
        }

        if ($licencia->estado !== 'activa') {
            abort(
                403,
                'No se puede operar sobre marchamos porque la licencia está inactiva.'
            );
        }

        if (
            ! $licencia->fecha_activacion
            || ! $licencia->fecha_vencimiento
        ) {
            abort(
                422,
                'La licencia no posee un rango de vigencia válido.'
            );
        }

        $hoy = Carbon::today();

        $fechaActivacion = $licencia
            ->fecha_activacion
            ->copy()
            ->startOfDay();

        $fechaVencimiento = $licencia
            ->fecha_vencimiento
            ->copy()
            ->startOfDay();

        if ($fechaActivacion->gt($hoy)) {
            abort(
                403,
                'No se puede operar sobre marchamos porque la licencia está pendiente de activación.'
            );
        }

        if ($fechaVencimiento->lt($hoy)) {
            abort(
                403,
                'No se puede operar sobre marchamos porque la licencia está vencida.'
            );
        }
    }

    private function validarEmpresaActivaUnidad(
        Unidad $unidad
    ): void {
        $unidad->loadMissing('empresa');

        if (
            ! $unidad->empresa
            || $unidad->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre marchamos porque la empresa está inactiva.'
            );
        }
    }

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
                'No tiene autorización para acceder a esta unidad.'
            );
        }
    }
}
