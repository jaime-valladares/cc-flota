<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\ReemplazoMarchamoDetalle;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarchamoReemplazoController extends Controller
{
    private const MOTIVOS_REEMPLAZO = [
        'dano' => 'Daño',
        'desgaste' => 'Desgaste',
        'perdida' => 'Pérdida',
        'manipulacion_detectada' => 'Manipulación detectada',
        'correccion_instalacion' => 'Corrección de instalación',
    ];

    public function index(Request $request): View
    {
        return view(
            'marchamos.reemplazos.index',
            $this->datosIndex($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'marchamos.reemplazos.index-ventana',
            $this->datosIndex($request)
        );
    }

    public function show(Unidad $unidad): View
    {
        return view(
            'marchamos.reemplazos.show',
            $this->datosShow($unidad)
        );
    }

    public function showVentana(Unidad $unidad): View
    {
        return view(
            'marchamos.reemplazos.show-ventana',
            $this->datosShow($unidad)
        );
    }

    public function store(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->validarAccesoUnidad($unidad);
        $this->validarUnidadReemplazable($unidad);

        $validated = $request->validate(
            [
                'reemplazos' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'reemplazos.*.seleccionado' => [
                    'nullable',
                    'boolean',
                ],

                'reemplazos.*.punto_seguridad_id' => [
                    'required',
                    'integer',
                    'exists:puntos_seguridad_unidad,id',
                ],

                'reemplazos.*.nuevo_codigo_marchamo' => [
                    'nullable',
                    'string',
                    'regex:/^\d{7}$/',
                ],

                'reemplazos.*.motivo_reemplazo' => [
                    'nullable',
                    Rule::in(
                        array_keys(self::MOTIVOS_REEMPLAZO)
                    ),
                ],

                'return_to' => [
                    'nullable',
                    'string',
                    'in:ventana',
                ],
            ],
            [
                'reemplazos.required' =>
                    'Debe seleccionar al menos un marchamo para reemplazar.',

                'reemplazos.array' =>
                    'La información de reemplazos no tiene el formato esperado.',

                'reemplazos.*.punto_seguridad_id.required' =>
                    'Cada reemplazo debe estar asociado a un punto de seguridad.',

                'reemplazos.*.punto_seguridad_id.exists' =>
                    'Uno de los puntos de seguridad seleccionados no existe.',

                'reemplazos.*.nuevo_codigo_marchamo.regex' =>
                    'El nuevo código de marchamo debe contener exactamente 7 dígitos.',

                'reemplazos.*.motivo_reemplazo.in' =>
                    'Seleccione un motivo de reemplazo válido.',
            ]
        );

        $reemplazosSeleccionados = collect(
            $validated['reemplazos']
        )
            ->filter(
                fn (array $item): bool =>
                    isset($item['seleccionado'])
                    && (bool) $item['seleccionado']
            )
            ->map(function (array $item): array {
                $item['punto_seguridad_id'] =
                    (int) $item['punto_seguridad_id'];

                $item['nuevo_codigo_marchamo'] =
                    isset($item['nuevo_codigo_marchamo'])
                        ? trim(
                            (string) $item['nuevo_codigo_marchamo']
                        )
                        : null;

                return $item;
            })
            ->values();

        if ($reemplazosSeleccionados->isEmpty()) {
            return back()
                ->withErrors([
                    'reemplazos' =>
                        'Debe seleccionar al menos un marchamo para reemplazar.',
                ])
                ->withInput();
        }

        foreach (
            $reemplazosSeleccionados
            as $index => $reemplazo
        ) {
            if (
                blank(
                    $reemplazo['nuevo_codigo_marchamo']
                    ?? null
                )
            ) {
                return back()
                    ->withErrors([
                        "reemplazos.{$index}.nuevo_codigo_marchamo" =>
                            'Ingrese el nuevo código para cada punto seleccionado.',
                    ])
                    ->withInput();
            }

            if (
                blank(
                    $reemplazo['motivo_reemplazo']
                    ?? null
                )
            ) {
                return back()
                    ->withErrors([
                        "reemplazos.{$index}.motivo_reemplazo" =>
                            'Seleccione el motivo para cada punto marcado.',
                    ])
                    ->withInput();
            }
        }

        $puntoIdsSeleccionados = $reemplazosSeleccionados
            ->pluck('punto_seguridad_id')
            ->map(
                fn ($puntoId): int => (int) $puntoId
            )
            ->values();

        if (
            $puntoIdsSeleccionados->count()
            !== $puntoIdsSeleccionados->unique()->count()
        ) {
            return back()
                ->withErrors([
                    'reemplazos' =>
                        'No puede seleccionar el mismo punto de seguridad más de una vez.',
                ])
                ->withInput();
        }

        $codigosNuevos = $reemplazosSeleccionados
            ->pluck('nuevo_codigo_marchamo')
            ->filter()
            ->values();

        if (
            $codigosNuevos->count()
            !== $codigosNuevos->unique()->count()
        ) {
            return back()
                ->withErrors([
                    'reemplazos' =>
                        'No puede repetir el mismo código dentro de la operación.',
                ])
                ->withInput();
        }

        $codigosExistentes = Marchamo::query()
            ->whereIn(
                'codigo_marchamo',
                $codigosNuevos
            )
            ->pluck('codigo_marchamo')
            ->unique()
            ->values();

        if ($codigosExistentes->isNotEmpty()) {
            return back()
                ->withErrors([
                    'reemplazos' =>
                        'Los siguientes códigos ya existen en el sistema: '
                        . $codigosExistentes->implode(', '),
                ])
                ->withInput();
        }

        DB::transaction(
            function () use (
                $unidad,
                $reemplazosSeleccionados,
                $puntoIdsSeleccionados,
                $codigosNuevos
            ): void {
                $unidadBloqueada = Unidad::query()
                    ->with([
                        'empresa',
                        'licencia',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($unidad->id);

                $this->validarAccesoUnidad(
                    $unidadBloqueada
                );

                $this->validarUnidadReemplazable(
                    $unidadBloqueada
                );

                /*
                 * Se verifica nuevamente la unicidad dentro de la
                 * transacción para reducir condiciones de carrera.
                 */
                $codigosNoDisponibles = Marchamo::query()
                    ->whereIn(
                        'codigo_marchamo',
                        $codigosNuevos
                    )
                    ->lockForUpdate()
                    ->pluck('codigo_marchamo')
                    ->unique()
                    ->values();

                if ($codigosNoDisponibles->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'reemplazos' =>
                            'Los siguientes códigos ya existen en el sistema: '
                            . $codigosNoDisponibles->implode(', '),
                    ]);
                }

                $puntos = PuntoSeguridadUnidad::query()
                    ->where(
                        'unidad_id',
                        $unidadBloqueada->id
                    )
                    ->whereIn(
                        'id',
                        $puntoIdsSeleccionados
                    )
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
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if (
                    $puntos->count()
                    !== $puntoIdsSeleccionados->count()
                ) {
                    throw ValidationException::withMessages([
                        'reemplazos' =>
                            'Uno o más puntos no pertenecen a la unidad o no están disponibles.',
                    ]);
                }

                $marchamosAnteriores = collect();

                foreach (
                    $reemplazosSeleccionados
                    as $reemplazo
                ) {
                    $punto = $puntos->get(
                        (int) $reemplazo[
                            'punto_seguridad_id'
                        ]
                    );

                    if (! $punto) {
                        throw ValidationException::withMessages([
                            'reemplazos' =>
                                'Uno de los puntos seleccionados no está disponible.',
                        ]);
                    }

                    $marchamoAnterior = Marchamo::query()
                        ->where(
                            'id',
                            $punto->marchamo_actual_id
                        )
                        ->where(
                            'empresa_id',
                            $unidadBloqueada->empresa_id
                        )
                        ->where(
                            'unidad_id',
                            $unidadBloqueada->id
                        )
                        ->where(
                            'punto_seguridad_id',
                            $punto->id
                        )
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'activo_actual',
                            true
                        )
                        ->lockForUpdate()
                        ->first();

                    if (! $marchamoAnterior) {
                        throw ValidationException::withMessages([
                            'reemplazos' =>
                                "El punto {$punto->nombre_punto} no posee un marchamo activo válido.",
                        ]);
                    }

                    $marchamosAnteriores->put(
                        $punto->id,
                        $marchamoAnterior
                    );
                }

                $motivoPrincipal = $reemplazosSeleccionados
                    ->first()['motivo_reemplazo'];

                $evento = ReemplazoMarchamoEvento::create([
                    'empresa_id' =>
                        $unidadBloqueada->empresa_id,

                    'unidad_id' =>
                        $unidadBloqueada->id,

                    'motivo_reemplazo' =>
                        $motivoPrincipal,

                    'cantidad_reemplazos' =>
                        $reemplazosSeleccionados->count(),

                    'origen_evento' =>
                        'reemplazo_general',

                    'estado' =>
                        'registrado',

                    'fecha_registro' =>
                        now(),

                    'registrado_por' =>
                        Auth::id(),
                ]);

                foreach (
                    $reemplazosSeleccionados
                    as $reemplazo
                ) {
                    $punto = $puntos->get(
                        (int) $reemplazo[
                            'punto_seguridad_id'
                        ]
                    );

                    $marchamoAnterior =
                        $marchamosAnteriores->get(
                            $punto->id
                        );

                    $motivoTexto = $this->motivoTexto(
                        $reemplazo[
                            'motivo_reemplazo'
                        ]
                    );

                    /*
                     * El marchamo retirado queda histórico y no
                     * puede volver a ser utilizado como actual.
                     */
                    $marchamoAnterior->update([
                        'estado' =>
                            'reemplazado',

                        'activo_actual' =>
                            false,

                        'fecha_desactivacion' =>
                            now(),

                        'motivo_desactivacion' =>
                            $motivoTexto,

                        'actualizado_por' =>
                            Auth::id(),
                    ]);

                    $marchamoNuevo = Marchamo::create([
                        'empresa_id' =>
                            $unidadBloqueada->empresa_id,

                        'unidad_id' =>
                            $unidadBloqueada->id,

                        'punto_seguridad_id' =>
                            $punto->id,

                        'codigo_marchamo' =>
                            $reemplazo[
                                'nuevo_codigo_marchamo'
                            ],

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
                            'reemplazo_dano_desgaste',

                        'creado_por' =>
                            Auth::id(),

                        'actualizado_por' =>
                            Auth::id(),
                    ]);

                    $punto->update([
                        'marchamo_actual_id' =>
                            $marchamoNuevo->id,

                        'estado_asignacion' =>
                            'asignado',

                        'actualizado_por' =>
                            Auth::id(),
                    ]);

                    ReemplazoMarchamoDetalle::create([
                        'reemplazo_evento_id' =>
                            $evento->id,

                        'punto_seguridad_id' =>
                            $punto->id,

                        'marchamo_anterior_id' =>
                            $marchamoAnterior->id,

                        'marchamo_nuevo_id' =>
                            $marchamoNuevo->id,

                        'fecha_registro' =>
                            now(),
                    ]);
                }
            }
        );

        $ruta = ($validated['return_to'] ?? null)
            === 'ventana'
                ? 'marchamos.reemplazos.show.ventana'
                : 'marchamos.reemplazos.show';

        return redirect()
            ->route(
                $ruta,
                $unidad
            )
            ->with(
                'success',
                'Reemplazos de marchamos registrados correctamente.'
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
         * Se conserva compatibilidad con empresa_id.
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
                fn ($id): bool => filled($id)
            )
            ->map(
                fn ($id): int => (int) $id
            )
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
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        /*
         * Compatibilidad temporal con unidad_id.
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
         * El usuario empresarial queda restringido automáticamente
         * a su propia empresa.
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
         * Base de unidades elegibles para reemplazo.
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
                'estado',
                'activa'
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
            ->whereDoesntHave(
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
                        )
                        ->where(
                            function ($puntoQuery) {
                                $puntoQuery
                                    ->whereNull(
                                        'marchamo_actual_id'
                                    )
                                    ->orWhereDoesntHave(
                                        'marchamoActual',
                                        function ($marchamoQuery) {
                                            $marchamoQuery
                                                ->where(
                                                    'estado',
                                                    'activo'
                                                )
                                                ->where(
                                                    'activo_actual',
                                                    true
                                                );
                                        }
                                    );
                            }
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
         * El selector de unidades respeta las empresas seleccionadas.
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
         * Se conserva para compatibilidad temporal con las vistas
         * que todavía esperan una colección de unidades.
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
                $hayFiltros
                && filled($busquedaEmpresa),
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
                $hayFiltros
                && $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->when(
                $hayFiltros
                && filled($busquedaPlaca),
                function ($query) use ($busquedaPlaca) {
                    $query->where(
                        'placa',
                        'like',
                        '%' . $busquedaPlaca . '%'
                    );
                }
            )
            ->when(
                $hayFiltros
                && $unidadIds->isNotEmpty(),
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
            ->paginate(10)
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
             * Compatibilidad con los selectores simples anteriores.
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
        $this->validarAccesoUnidad($unidad);
        $this->validarUnidadReemplazable($unidad);

        $unidad->load([
            'empresa',
            'licencia',
        ]);

        $puntos = PuntoSeguridadUnidad::query()
            ->with('marchamoActual')
            ->where(
                'unidad_id',
                $unidad->id
            )
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
            )
            ->orderBy('orden')
            ->get();

        return [
            'unidad' =>
                $unidad,

            'puntos' =>
                $puntos,

            'motivosReemplazo' =>
                self::MOTIVOS_REEMPLAZO,
        ];
    }

    private function validarAccesoUnidad(
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

    private function validarUnidadReemplazable(
        Unidad $unidad
    ): void {
        $unidad->loadMissing([
            'empresa',
            'licencia',
        ]);

        if (
            ! $unidad->empresa
            || $unidad->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede reemplazar marchamos porque la empresa está inactiva.'
            );
        }

        if ($unidad->estado !== 'activa') {
            abort(
                403,
                'La unidad debe estar activa para reemplazar marchamos.'
            );
        }

        $this->validarLicenciaOperativa(
            $unidad
        );

        $unidad->loadCount([
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
        ]);

        if ((int) $unidad->total_puntos === 0) {
            abort(
                403,
                'La unidad no tiene puntos de seguridad disponibles para reemplazo.'
            );
        }

        if (
            (int) $unidad->total_puntos
            !== (int) $unidad->puntos_asignados
        ) {
            abort(
                403,
                'La unidad debe tener cobertura completa para reemplazar marchamos.'
            );
        }

        $marchamosActualesValidos =
            PuntoSeguridadUnidad::query()
                ->where(
                    'unidad_id',
                    $unidad->id
                )
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
                )
                ->whereHas(
                    'marchamoActual',
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
                    }
                )
                ->count();

        if (
            $marchamosActualesValidos
            !== (int) $unidad->total_puntos
        ) {
            abort(
                403,
                'La unidad posee uno o más puntos sin un marchamo activo válido.'
            );
        }
    }

    private function validarLicenciaOperativa(
        Unidad $unidad
    ): void {
        $licencia = $unidad->licencia;

        if (! $licencia) {
            abort(
                403,
                'La unidad debe tener una licencia registrada para reemplazar marchamos.'
            );
        }

        if ($licencia->estado !== 'activa') {
            abort(
                403,
                'No se puede reemplazar marchamos porque la licencia está inactiva.'
            );
        }

        if (
            ! $licencia->fecha_activacion
            || ! $licencia->fecha_vencimiento
        ) {
            abort(
                403,
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
                'No se puede reemplazar marchamos porque la licencia está pendiente de activación.'
            );
        }

        if ($fechaVencimiento->lt($hoy)) {
            abort(
                403,
                'No se puede reemplazar marchamos porque la licencia está vencida.'
            );
        }
    }

    private function motivoTexto(
        string $motivo
    ): string {
        return self::MOTIVOS_REEMPLAZO[
            $motivo
        ] ?? 'No definido';
    }
}
