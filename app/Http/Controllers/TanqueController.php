<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\MovimientoInventarioCombustible;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TanqueController extends Controller
{
    /**
     * Mostrar la gestión de tanques dentro del sistema.
     */
    public function index(Request $request)
    {
        return view(
            'gasolineras.tanques.index',
            $this->prepararConsultaTanques($request)
        );
    }

    /**
     * Mostrar la gestión de tanques en ventana independiente.
     */
    public function indexVentana(Request $request)
    {
        return view(
            'gasolineras.tanques.index-ventana',
            $this->prepararConsultaTanques($request)
        );
    }

    /**
     * Preparar filtros, resultados y resúmenes de Gestión de tanques.
     */
    private function prepararConsultaTanques(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'busqueda_empresa' => [
                'nullable',
                'string',
                'max:150',
            ],
            'busqueda_gasolinera' => [
                'nullable',
                'string',
                'max:150',
            ],

            'empresa_ids' => [
                'nullable',
                'array',
            ],
            'empresa_ids.*' => [
                'integer',
                'distinct',
                'exists:empresas,id',
            ],

            'gasolinera_ids' => [
                'nullable',
                'array',
            ],
            'gasolinera_ids.*' => [
                'integer',
                'distinct',
                'exists:gasolineras,id',
            ],

            /*
             * Compatibilidad temporal con enlaces anteriores.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],
            'gasolinera_id' => [
                'nullable',
                'integer',
                'exists:gasolineras,id',
            ],
            'nombre' => [
                'nullable',
                'string',
                'max:100',
            ],
            'estado' => [
                'nullable',
                'in:activo,inactivo',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ], [
            'empresa_ids.array' =>
                'La selección de empresas no es válida.',
            'empresa_ids.*.integer' =>
                'Una de las empresas seleccionadas no es válida.',
            'empresa_ids.*.distinct' =>
                'No debe seleccionar una misma empresa más de una vez.',
            'empresa_ids.*.exists' =>
                'Una de las empresas seleccionadas no existe.',

            'gasolinera_ids.array' =>
                'La selección de gasolineras no es válida.',
            'gasolinera_ids.*.integer' =>
                'Una de las gasolineras seleccionadas no es válida.',
            'gasolinera_ids.*.distinct' =>
                'No debe seleccionar una misma gasolinera más de una vez.',
            'gasolinera_ids.*.exists' =>
                'Una de las gasolineras seleccionadas no existe.',

            'empresa_id.exists' =>
                'La empresa seleccionada no es válida.',
            'gasolinera_id.exists' =>
                'La gasolinera seleccionada no es válida.',
            'estado.in' =>
                'El estado seleccionado no es válido.',
        ]);

        $busquedaEmpresa = trim(
            (string) ($validated['busqueda_empresa'] ?? '')
        );

        $busquedaGasolinera = trim(
            (string) ($validated['busqueda_gasolinera'] ?? '')
        );

        $empresaIds = $this->normalizarIdsSeleccionados(
            $validated['empresa_ids'] ?? [],
            $validated['empresa_id'] ?? null
        );

        $gasolineraIds = $this->normalizarIdsSeleccionados(
            $validated['gasolinera_ids'] ?? [],
            $validated['gasolinera_id'] ?? null
        );

        $nombre = trim(
            (string) ($validated['nombre'] ?? '')
        );

        $estado = $validated['estado'] ?? null;

        if (! $esUsuarioDieselCop) {
            $empresaIds = collect([
                (int) $user->empresa_id,
            ]);
        }

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaGasolinera)
            || $empresaIds->isNotEmpty()
            || $gasolineraIds->isNotEmpty()
            || filled($nombre)
            || filled($estado);

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderByRaw(
                    'COALESCE(nombre_comercial, nombre_legal)'
                )
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])
                ->filter(
                    fn ($empresa) =>
                        $empresa
                        && $empresa->estado === 'activa'
                )
                ->values();

        $gasolinerasSelector = Gasolinera::query()
            ->with('empresa')
            ->where('estado', 'activa')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'empresa_id',
                        $user->empresa_id
                    );
                }
            )
            ->when(
                $esUsuarioDieselCop
                && filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
                    $query->whereHas(
                        'empresa',
                        function ($empresaQuery) use (
                            $busquedaEmpresa
                        ) {
                            $empresaQuery->where(
                                function ($subquery) use (
                                    $busquedaEmpresa
                                ) {
                                    $subquery
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
                    );
                }
            )
            ->when(
                $esUsuarioDieselCop
                && $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->orderBy('nombre')
            ->get();

        $gasolinerasSeleccionadasValidas =
            $this->obtenerGasolinerasSeleccionadasValidas(
                $gasolineraIds,
                $empresaIds,
                $esUsuarioDieselCop,
                $user->empresa_id
            );

        $combinacionEmpresaGasolineraInvalida =
            $gasolineraIds->isNotEmpty()
            && $gasolinerasSeleccionadasValidas->count()
                !== $gasolineraIds->count();

        $query = $this->consultaBaseTanquesAdministrables(
            $esUsuarioDieselCop,
            $user->empresa_id
        )->with('gasolinera.empresa');

        if ($hayFiltros) {
            if ($combinacionEmpresaGasolineraInvalida) {
                $query->whereRaw('1 = 0');
            } else {
                $this->aplicarFiltrosConsultaTanques(
                    $query,
                    $busquedaEmpresa,
                    $busquedaGasolinera,
                    $empresaIds,
                    $gasolineraIds,
                    $nombre,
                    $estado
                );
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $tanques = $query
            ->join(
                'gasolineras',
                'tanques.gasolinera_id',
                '=',
                'gasolineras.id'
            )
            ->select('tanques.*')
            ->orderBy('gasolineras.nombre')
            ->orderBy('tanques.nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = $this->consultaBaseTanquesAdministrables(
            $esUsuarioDieselCop,
            $user->empresa_id
        );

        if ($hayFiltros) {
            if ($combinacionEmpresaGasolineraInvalida) {
                $baseResumen->whereRaw('1 = 0');
            } else {
                $this->aplicarFiltrosConsultaTanques(
                    $baseResumen,
                    $busquedaEmpresa,
                    $busquedaGasolinera,
                    $empresaIds,
                    $gasolineraIds,
                    $nombre,
                    $estado
                );
            }
        } else {
            $baseResumen->whereRaw('1 = 0');
        }

        $totalTanques = (clone $baseResumen)->count();

        $tanquesActivos = (clone $baseResumen)
            ->where('tanques.estado', 'activo')
            ->count();

        $tanquesInactivos = (clone $baseResumen)
            ->where('tanques.estado', 'inactivo')
            ->count();

        return [
            'tanques' => $tanques,
            'empresasSelector' => $empresasSelector,
            'gasolinerasSelector' => $gasolinerasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaGasolinera' => $busquedaGasolinera,

            'empresaIds' => $empresaIds->all(),
            'gasolineraIds' => $gasolineraIds->all(),

            /*
             * Compatibilidad temporal con vistas anteriores.
             */
            'empresaId' => $empresaIds->first(),
            'gasolineraId' => $gasolineraIds->first(),

            'nombre' => $nombre,
            'estado' => $estado,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,

            'totalTanques' => $totalTanques,
            'tanquesActivos' => $tanquesActivos,
            'tanquesInactivos' => $tanquesInactivos,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Consulta base de tanques pertenecientes a gasolineras y empresas activas.
     */
    private function consultaBaseTanquesAdministrables(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ) {
        return Tanque::query()
            ->whereHas('gasolinera', function ($query) {
                $query->where(
                    'gasolineras.estado',
                    'activa'
                );
            })
            ->whereHas(
                'gasolinera.empresa',
                function ($query) {
                    $query->where('estado', 'activa');
                }
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($empresaUsuarioId) {
                    $query->whereHas(
                        'gasolinera',
                        function ($gasolineraQuery) use (
                            $empresaUsuarioId
                        ) {
                            $gasolineraQuery->where(
                                'gasolineras.empresa_id',
                                $empresaUsuarioId
                            );
                        }
                    );
                }
            );
    }

    /**
     * Aplicar los mismos filtros a resultados y resúmenes.
     */
    private function aplicarFiltrosConsultaTanques(
        $query,
        string $busquedaEmpresa,
        string $busquedaGasolinera,
        Collection $empresaIds,
        Collection $gasolineraIds,
        string $nombre,
        ?string $estado
    ): void {
        $query
            ->when(
                filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
                    $query->whereHas(
                        'gasolinera.empresa',
                        function ($empresaQuery) use (
                            $busquedaEmpresa
                        ) {
                            $empresaQuery->where(
                                function ($subquery) use (
                                    $busquedaEmpresa
                                ) {
                                    $subquery
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
                    );
                }
            )
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereHas(
                        'gasolinera',
                        function ($gasolineraQuery) use (
                            $empresaIds
                        ) {
                            $gasolineraQuery->whereIn(
                                'gasolineras.empresa_id',
                                $empresaIds->all()
                            );
                        }
                    );
                }
            )
            ->when(
                filled($busquedaGasolinera),
                function ($query) use ($busquedaGasolinera) {
                    $query->whereHas(
                        'gasolinera',
                        function ($gasolineraQuery) use (
                            $busquedaGasolinera
                        ) {
                            $gasolineraQuery->where(
                                'gasolineras.nombre',
                                'like',
                                '%' . $busquedaGasolinera . '%'
                            );
                        }
                    );
                }
            )
            ->when(
                $gasolineraIds->isNotEmpty(),
                function ($query) use ($gasolineraIds) {
                    $query->whereIn(
                        'tanques.gasolinera_id',
                        $gasolineraIds->all()
                    );
                }
            )
            ->when(
                filled($nombre),
                function ($query) use ($nombre) {
                    $query->where(
                        'tanques.nombre',
                        'like',
                        '%' . $nombre . '%'
                    );
                }
            )
            ->when(
                in_array(
                    $estado,
                    ['activo', 'inactivo'],
                    true
                ),
                function ($query) use ($estado) {
                    $query->where(
                        'tanques.estado',
                        $estado
                    );
                }
            );
    }

    /**
     * Normalizar parámetros múltiples y parámetros simples heredados.
     */
    private function normalizarIdsSeleccionados(
        array $ids,
        int|string|null $idAnterior = null
    ): Collection {
        $normalizados = collect($ids)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id);

        if (filled($idAnterior)) {
            $normalizados->push((int) $idAnterior);
        }

        return $normalizados
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    }

    /**
     * Verificar que las gasolineras seleccionadas sean accesibles y válidas.
     */
    private function obtenerGasolinerasSeleccionadasValidas(
        Collection $gasolineraIds,
        Collection $empresaIds,
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ): Collection {
        if ($gasolineraIds->isEmpty()) {
            return collect();
        }

        return Gasolinera::query()
            ->with('empresa')
            ->whereIn(
                'id',
                $gasolineraIds->all()
            )
            ->where('estado', 'activa')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($empresaUsuarioId) {
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    );
                }
            )
            ->when(
                $esUsuarioDieselCop
                && $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->get();
    }

    /**
     * Mostrar la ficha administrativa interna.
     */
    public function show(
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraActiva($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );

        return view(
            'gasolineras.tanques.show',
            $this->prepararFichaTanque(
                $gasolinera,
                $tanque
            )
        );
    }

    /**
     * Mostrar la ficha administrativa en ventana independiente.
     */
    public function showVentana(
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraActiva($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );

        return view(
            'gasolineras.tanques.show-ventana',
            $this->prepararFichaTanque(
                $gasolinera,
                $tanque
            )
        );
    }

    /**
     * Preparar los datos de la ficha del tanque.
     */
    private function prepararFichaTanque(
        Gasolinera $gasolinera,
        Tanque $tanque
    ): array {
        $gasolinera->loadMissing('empresa');

        $capacidadTotal =
            (float) $tanque->capacidad_total;

        $volumenActual =
            (float) $tanque->volumen_actual;

        $volumenMinimoAlerta =
            (float) $tanque->volumen_minimo_alerta;

        $porcentajeDisponible = $capacidadTotal > 0
            ? round(
                ($volumenActual / $capacidadTotal) * 100,
                2
            )
            : 0;

        $bajoAlerta = $tanque->estaBajoAlerta();

        $movimientosRecientes =
            MovimientoInventarioCombustible::query()
                ->where('tanque_id', $tanque->id)
                ->where('estado', 'registrado')
                ->latest('fecha_hora_movimiento')
                ->limit(10)
                ->get();

        return [
            'gasolinera' => $gasolinera,
            'tanque' => $tanque,
            'capacidadTotal' => $capacidadTotal,
            'volumenActual' => $volumenActual,
            'volumenMinimoAlerta' =>
                $volumenMinimoAlerta,
            'porcentajeDisponible' =>
                $porcentajeDisponible,
            'bajoAlerta' => $bajoAlerta,
            'movimientosRecientes' =>
                $movimientosRecientes,
        ];
    }

    /**
     * Actualizar los datos controlados del tanque.
     */
    public function update(
        Request $request,
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraActiva($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );
        $this->validarTanqueActivoParaEdicion($tanque);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tanques', 'nombre')
                    ->where(
                        'gasolinera_id',
                        $gasolinera->id
                    )
                    ->ignore($tanque->id),
            ],
            'capacidad_total' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'volumen_minimo_alerta' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'return_to' => [
                'nullable',
                'in:ventana',
            ],
            'return_query' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'nombre.required' =>
                'Debe ingresar el nombre del tanque.',
            'nombre.unique' =>
                'Ya existe un tanque con ese nombre para esta gasolinera.',

            'capacidad_total.required' =>
                'Debe ingresar la capacidad total del tanque.',
            'capacidad_total.gt' =>
                'La capacidad total del tanque debe ser mayor que cero.',

            'volumen_minimo_alerta.required' =>
                'Debe ingresar el volumen mínimo de alerta.',
            'volumen_minimo_alerta.gte' =>
                'El volumen mínimo de alerta no puede ser negativo.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
            'return_query.max' =>
                'Los parámetros de retorno no son válidos.',
        ]);

        $capacidadTotal =
            (float) $validated['capacidad_total'];

        $volumenActual =
            (float) $tanque->volumen_actual;

        $volumenMinimoAlerta =
            (float) $validated['volumen_minimo_alerta'];

        if ($capacidadTotal < $volumenActual) {
            return back()
                ->withErrors([
                    'capacidad_total' =>
                        'La capacidad total no puede ser menor que el volumen actual del tanque.',
                ])
                ->withInput();
        }

        if ($volumenMinimoAlerta >= $capacidadTotal) {
            return back()
                ->withErrors([
                    'volumen_minimo_alerta' =>
                        'El volumen mínimo de alerta debe ser menor que la capacidad total del tanque.',
                ])
                ->withInput();
        }

        $tanque->update([
            'nombre' => $validated['nombre'],
            'capacidad_total' =>
                $validated['capacidad_total'],
            'volumen_minimo_alerta' =>
                $validated['volumen_minimo_alerta'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirFichaTanque(
            $request,
            $gasolinera,
            $tanque,
            'Tanque actualizado correctamente.'
        );
    }

    /**
     * Inactivar un tanque activo.
     */
    public function inactivar(
        Request $request,
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraActiva($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );

        if ($tanque->estado !== 'activo') {
            return back()->withErrors([
                'tanque' => 'El tanque ya está inactivo.',
            ]);
        }

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                Rule::in([
                    'Mantenimiento',
                    'Daño operativo',
                    'Fuera de servicio',
                    'Datos incorrectos en registro',
                    'Solicitud del cliente',
                    'Otro',
                ]),
            ],
            'return_to' => [
                'nullable',
                'in:ventana',
            ],
            'return_query' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'motivo_inactivacion.required' =>
                'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' =>
                'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' =>
                'El motivo de inactivación no debe exceder 255 caracteres.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
            'return_query.max' =>
                'Los parámetros de retorno no son válidos.',
        ]);

        $fechaActualizacion = now();
        $usuarioId = Auth::id();

        $tanque->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' =>
                $fechaActualizacion,
            'inactivado_por' =>
                $usuarioId,
            'motivo_inactivacion' =>
                $validated['motivo_inactivacion'],
            'fecha_actualizacion' =>
                $fechaActualizacion,
            'actualizado_por' =>
                $usuarioId,
        ]);

        return $this->redirigirFichaTanque(
            $request,
            $gasolinera,
            $tanque,
            'Tanque inactivado correctamente.'
        );
    }

    /**
     * Reactivar un tanque inactivo.
     */
    public function reactivar(
        Request $request,
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraActiva($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );

        $request->validate([
            'return_to' => [
                'nullable',
                'in:ventana',
            ],
            'return_query' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'return_to.in' =>
                'El destino de retorno no es válido.',
            'return_query.max' =>
                'Los parámetros de retorno no son válidos.',
        ]);

        if ($tanque->estado !== 'inactivo') {
            return back()->withErrors([
                'tanque' => 'El tanque ya está activo.',
            ]);
        }

        $tanque->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirFichaTanque(
            $request,
            $gasolinera,
            $tanque,
            'Tanque reactivado correctamente.'
        );
    }

    /**
     * Redirigir a la ficha correspondiente conservando la consulta original.
     */
    private function redirigirFichaTanque(
        Request $request,
        Gasolinera $gasolinera,
        Tanque $tanque,
        string $mensaje
    ) {
        $parametrosRetorno =
            $this->obtenerParametrosRetorno($request);

        $parametrosRuta = array_merge(
            [
                'gasolinera' => $gasolinera,
                'tanque' => $tanque,
            ],
            $parametrosRetorno
        );

        $ruta = $request->input('return_to') === 'ventana'
            ? 'gasolineras.tanques.show.ventana'
            : 'gasolineras.tanques.show';

        return redirect()
            ->route($ruta, $parametrosRuta)
            ->with('success', $mensaje);
    }

    /**
     * Reconstruir únicamente los filtros permitidos del listado.
     */
    private function obtenerParametrosRetorno(
        Request $request
    ): array {
        $returnQuery = trim(
            (string) $request->input(
                'return_query',
                ''
            )
        );

        if ($returnQuery === '') {
            return [];
        }

        parse_str(
            $returnQuery,
            $parametrosOriginales
        );

        $parametrosRetorno = [];

        if (
            isset($parametrosOriginales['consultar'])
            && in_array(
                (string) $parametrosOriginales['consultar'],
                ['1', 'true', 'on'],
                true
            )
        ) {
            $parametrosRetorno['consultar'] = 1;
        }

        foreach (
            [
                'busqueda_empresa' => 150,
                'busqueda_gasolinera' => 150,
                'nombre' => 100,
            ]
            as $campo => $longitudMaxima
        ) {
            if (
                ! isset($parametrosOriginales[$campo])
                || is_array($parametrosOriginales[$campo])
            ) {
                continue;
            }

            $valor = trim(
                (string) $parametrosOriginales[$campo]
            );

            if ($valor !== '') {
                $parametrosRetorno[$campo] = mb_substr(
                    $valor,
                    0,
                    $longitudMaxima
                );
            }
        }

        if (
            isset($parametrosOriginales['estado'])
            && in_array(
                $parametrosOriginales['estado'],
                ['activo', 'inactivo'],
                true
            )
        ) {
            $parametrosRetorno['estado'] =
                $parametrosOriginales['estado'];
        }

        foreach (
            [
                'empresa_ids',
                'gasolinera_ids',
            ]
            as $campo
        ) {
            $valores = $parametrosOriginales[$campo] ?? [];

            if (! is_array($valores)) {
                $valores = [$valores];
            }

            $ids = collect($valores)
                ->filter(
                    fn ($id) =>
                        filter_var(
                            $id,
                            FILTER_VALIDATE_INT
                        ) !== false
                )
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($ids !== []) {
                $parametrosRetorno[$campo] = $ids;
            }
        }

        foreach (
            [
                'empresa_id',
                'gasolinera_id',
                'page',
            ]
            as $campo
        ) {
            $valor = $parametrosOriginales[$campo] ?? null;

            if (
                is_array($valor)
                || filter_var(
                    $valor,
                    FILTER_VALIDATE_INT
                ) === false
            ) {
                continue;
            }

            $valor = (int) $valor;

            if ($valor > 0) {
                $parametrosRetorno[$campo] = $valor;
            }
        }

        return $parametrosRetorno;
    }

    /**
     * Restringir usuarios empresariales a su propia empresa.
     */
    private function autorizarAccesoGasolinera(
        Gasolinera $gasolinera
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $gasolinera->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta gasolinera.'
            );
        }
    }

    /**
     * Exigir que la empresa propietaria permanezca activa.
     */
    private function validarEmpresaActivaGasolinera(
        Gasolinera $gasolinera
    ): void {
        $gasolinera->loadMissing('empresa');

        if (
            ! $gasolinera->empresa
            || $gasolinera->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre esta gasolinera porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Exigir una gasolinera activa para administrar sus tanques.
     */
    private function validarGasolineraActiva(
        Gasolinera $gasolinera
    ): void {
        if ($gasolinera->estado !== 'activa') {
            abort(
                403,
                'No se puede administrar tanques mientras la gasolinera esté inactiva.'
            );
        }
    }

    /**
     * Impedir edición mientras el tanque esté inactivo.
     */
    private function validarTanqueActivoParaEdicion(
        Tanque $tanque
    ): void {
        if ($tanque->estado !== 'activo') {
            abort(
                403,
                'No se puede editar un tanque inactivo. Debe reactivarlo desde su ficha.'
            );
        }
    }

    /**
     * Comprobar que el tanque pertenece a la gasolinera de la ruta.
     */
    private function validarTanquePerteneceGasolinera(
        Gasolinera $gasolinera,
        Tanque $tanque
    ): void {
        if (
            (int) $tanque->gasolinera_id
            !== (int) $gasolinera->id
        ) {
            abort(404);
        }
    }
}