<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnidadController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.index', $data);
    }

    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.index-ventana', $data);
    }

    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.administrar', $data);
    }

    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.administrar-ventana', $data);
    }

    /**
     * Prepara los datos comunes de Consulta y Administración.
     *
     * Una unidad solo es visible en los módulos operativos cuando
     * pertenece a una empresa activa.
     */
    private function prepararConsultaUnidades(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'consultar' => ['nullable', 'boolean'],

            /*
             * Búsqueda libre por placa o nombre de empresa.
             */
            'busqueda' => [
                'nullable',
                'string',
                'max:150',
            ],

            /*
             * Selección múltiple estándar.
             */
            'empresa_ids' => [
                'nullable',
                'array',
            ],

            'empresa_ids.*' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            'placas' => [
                'nullable',
                'array',
            ],

            'placas.*' => [
                'nullable',
                'string',
                'max:30',
            ],

            'modelos_medicion' => [
                'nullable',
                'array',
            ],

            'modelos_medicion.*' => [
                'nullable',
                Rule::in(array_keys($this->modelosMedicion())),
            ],

            /*
             * Filtros anteriores conservados temporalmente.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            'placa' => [
                'nullable',
                'string',
                'max:30',
            ],

            'modelo_medicion' => [
                'nullable',
                Rule::in(array_keys($this->modelosMedicion())),
            ],

            'estado' => [
                'nullable',
                Rule::in(array_keys($this->estadosUnidad())),
            ],
        ], [
            'busqueda.max' => 'La búsqueda no debe exceder 150 caracteres.',

            'empresa_ids.array' => 'La selección de empresas no es válida.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',

            'placas.array' => 'La selección de placas no es válida.',
            'placas.*.max' => 'Una de las placas seleccionadas no es válida.',

            'modelos_medicion.array' => 'La selección de modelos de medición no es válida.',
            'modelos_medicion.*.in' => 'Uno de los modelos de medición seleccionados no es válido.',
            'modelo_medicion.in' => 'El modelo de medición seleccionado no es válido.',

            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $busqueda = trim((string) ($validated['busqueda'] ?? ''));

        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id);

        /*
         * Compatibilidad temporal con empresa_id.
         */
        if (! empty($validated['empresa_id'])) {
            $empresaIds->push((int) $validated['empresa_id']);
        }

        $empresaIds = $empresaIds
            ->unique()
            ->values()
            ->all();

        $placas = collect($validated['placas'] ?? [])
            ->filter()
            ->map(fn ($placa) => mb_strtoupper(trim((string) $placa)));

        /*
         * Compatibilidad temporal con placa.
         */
        $placa = trim((string) ($validated['placa'] ?? ''));

        if ($placa !== '') {
            $placas->push(mb_strtoupper($placa));
        }

        $placas = $placas
            ->unique()
            ->values()
            ->all();

        $modelosMedicionSeleccionados = collect(
            $validated['modelos_medicion'] ?? []
        )
            ->filter();

        /*
         * Compatibilidad temporal con modelo_medicion.
         */
        $modeloMedicion = $validated['modelo_medicion'] ?? null;

        if ($modeloMedicion) {
            $modelosMedicionSeleccionados->push($modeloMedicion);
        }

        $modelosMedicionSeleccionados = $modelosMedicionSeleccionados
            ->unique()
            ->values()
            ->all();

        $estado = $validated['estado'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Alcance multiempresa
        |--------------------------------------------------------------------------
        |
        | Un usuario perteneciente a una empresa queda limitado siempre
        | a su propia empresa, sin importar los parámetros recibidos.
        |
        */

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $empresaId = $empresaIds[0] ?? null;

        $hayFiltros = $request->boolean('consultar')
            || $busqueda !== ''
            || count($empresaIds) > 0
            || count($placas) > 0
            || count($modelosMedicionSeleccionados) > 0
            || in_array($estado, array_keys($this->estadosUnidad()), true);

        /*
        |--------------------------------------------------------------------------
        | Empresas disponibles
        |--------------------------------------------------------------------------
        |
        | Solo las empresas activas pueden aparecer en los módulos operativos.
        |
        */

        $empresas = Empresa::query()
            ->where('estado', 'activa')
            ->when(! $esUsuarioDieselCop, function (Builder $query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Consulta base operativa
        |--------------------------------------------------------------------------
        |
        | Todas las consultas excluyen unidades de empresas inactivas,
        | pero conservan el estado propio de cada unidad.
        |
        */

        $baseQuery = Unidad::query()
            ->with('empresa')
            ->whereHas('empresa', function (Builder $empresaQuery) {
                $empresaQuery->where('estado', 'activa');
            })
            ->when(! $esUsuarioDieselCop, function (Builder $query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            });

        /*
        |--------------------------------------------------------------------------
        | Selector de placas
        |--------------------------------------------------------------------------
        |
        | Las placas disponibles se limitan a las empresas seleccionadas.
        |
        */

        $placasSelectorQuery = clone $baseQuery;

        if (count($empresaIds) > 0) {
            $placasSelectorQuery->whereIn('empresa_id', $empresaIds);
        }

        $placasSelector = $placasSelectorQuery
            ->whereNotNull('placa')
            ->orderBy('placa')
            ->pluck('placa')
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Resultados
        |--------------------------------------------------------------------------
        */

        $unidadesQuery = clone $baseQuery;

        if ($hayFiltros) {
            $this->aplicarFiltrosUnidad(
                $unidadesQuery,
                $busqueda,
                $empresaIds,
                $placas,
                $modelosMedicionSeleccionados,
                $estado
            );
        } else {
            $unidadesQuery->whereRaw('1 = 0');
        }

        /*
        |--------------------------------------------------------------------------
        | Resumen
        |--------------------------------------------------------------------------
        */

        $baseResumen = clone $baseQuery;

        if ($hayFiltros) {
            $this->aplicarFiltrosUnidad(
                $baseResumen,
                $busqueda,
                $empresaIds,
                $placas,
                $modelosMedicionSeleccionados,
                $estado
            );
        }

        $totalUnidades = (clone $baseQuery)->count();

        $totalRegistradas = (clone $baseQuery)
            ->where('estado', 'registrada')
            ->count();

        $totalActivas = (clone $baseQuery)
            ->where('estado', 'activa')
            ->count();

        $totalInactivas = (clone $baseQuery)
            ->where('estado', 'inactiva')
            ->count();

        $resumenUnidades = [
            'total' => $hayFiltros
                ? (clone $baseResumen)->count()
                : $totalUnidades,

            'registradas' => $hayFiltros
                ? (clone $baseResumen)->where('estado', 'registrada')->count()
                : $totalRegistradas,

            'activas' => $hayFiltros
                ? (clone $baseResumen)->where('estado', 'activa')->count()
                : $totalActivas,

            'inactivas' => $hayFiltros
                ? (clone $baseResumen)->where('estado', 'inactiva')->count()
                : $totalInactivas,
        ];

        $unidades = $unidadesQuery
            ->orderBy('placa')
            ->paginate(10)
            ->withQueryString();

        return [
            'unidades' => $unidades,
            'empresas' => $empresas,

            'empresaId' => $empresaId,
            'empresaIds' => $empresaIds,

            'estado' => $estado,

            /*
             * Variables individuales conservadas por compatibilidad.
             */
            'modeloMedicion' => $modeloMedicion,
            'placa' => $placa,

            'busqueda' => $busqueda,

            'placas' => $placas,
            'placasSelector' => $placasSelector,

            'modelosMedicionSeleccionados' => $modelosMedicionSeleccionados,

            'hayFiltros' => $hayFiltros,

            'totalUnidades' => $totalUnidades,
            'totalRegistradas' => $totalRegistradas,
            'totalActivas' => $totalActivas,
            'totalInactivas' => $totalInactivas,

            'resumenUnidades' => $resumenUnidades,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,

            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ];
    }

    /**
     * Aplica los filtros comunes a una consulta de unidades.
     */
    private function aplicarFiltrosUnidad(
        Builder $query,
        string $busqueda,
        array $empresaIds,
        array $placas,
        array $modelosMedicionSeleccionados,
        ?string $estado
    ): void {
        if (count($empresaIds) > 0) {
            $query->whereIn('empresa_id', $empresaIds);
        }

        if ($busqueda !== '') {
            $query->where(function (Builder $subquery) use ($busqueda) {
                $subquery
                    ->where('placa', 'like', '%' . $busqueda . '%')
                    ->orWhereHas('empresa', function (Builder $empresaQuery) use ($busqueda) {
                        $empresaQuery
                            ->where('nombre_legal', 'like', '%' . $busqueda . '%')
                            ->orWhere('nombre_comercial', 'like', '%' . $busqueda . '%');
                    });
            });
        }

        if (count($placas) > 0) {
            $query->whereIn('placa', $placas);
        }

        if (count($modelosMedicionSeleccionados) > 0) {
            $query->whereIn(
                'modelo_medicion',
                $modelosMedicionSeleccionados
            );
        }

        if (in_array($estado, array_keys($this->estadosUnidad()), true)) {
            $query->where('estado', $estado);
        }
    }

    public function create(): View
    {
        $data = $this->prepararFormularioUnidad();

        return view('unidades.create', $data);
    }

    public function createVentana(): View
    {
        $data = $this->prepararFormularioUnidad();

        return view('unidades.create-ventana', $data);
    }

    /**
     * Prepara el formulario de registro de una unidad.
     */
    private function prepararFormularioUnidad(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        if (
            ! $esUsuarioDieselCop
            && (
                ! $empresaUsuario
                || $empresaUsuario->estado !== 'activa'
            )
        ) {
            abort(
                403,
                'No se puede registrar una unidad porque la empresa está inactiva.'
            );
        }

        $empresas = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])
                ->filter()
                ->values();

        return [
            'empresas' => $empresas,
            'empresaUsuario' => $empresaUsuario,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate(
            $this->reglasValidacionUnidad(
                null,
                $esUsuarioDieselCop
            ),
            $this->mensajesValidacionUnidad()
        );

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        $unidad = Unidad::create([
            'empresa_id' => $empresaId,
            'placa' => mb_strtoupper(trim($validated['placa'])),
            'marca' => $validated['marca'] ?? null,
            'total_tanques' => $validated['total_tanques'],
            'cantidad_tanques_con_licencia' => $validated['cantidad_tanques_con_licencia'],
            'capacidad_total' => $validated['capacidad_total'],
            'capacidad_cubierta' => $validated['capacidad_cubierta'],
            'modelo_medicion' => $validated['modelo_medicion'],
            'estado' => 'registrada',
            'creado_por' => $user->id,
            'actualizado_por' => $user->id,
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        ['unidad' => $unidad]
                    )
                )
                ->with('success', 'Unidad creada correctamente.');
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    ['unidad' => $unidad]
                )
            )
            ->with('success', 'Unidad creada correctamente.');
    }

    public function show(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('unidades.show', compact('unidad'));
    }

    public function showVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('unidades.show-ventana', compact('unidad'));
    }

    public function edit(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadEditable($unidad);

        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $unidad->loadMissing('empresa');

        return view('unidades.edit', [
            'unidad' => $unidad,

            /*
             * La empresa actual se conserva como contexto bloqueado.
             */
            'empresas' => collect([$unidad->empresa])
                ->filter()
                ->values(),

            'empresaUsuario' => $unidad->empresa,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    public function editVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadEditable($unidad);

        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $unidad->loadMissing('empresa');

        return view('unidades.edit-ventana', [
            'unidad' => $unidad,

            /*
             * La empresa actual se conserva como contexto bloqueado.
             */
            'empresas' => collect([$unidad->empresa])
                ->filter()
                ->values(),

            'empresaUsuario' => $unidad->empresa,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    public function update(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadEditable($unidad);

        $user = Auth::user();

        $validated = $request->validate(
            $this->reglasValidacionUnidad(
                $unidad,
                is_null($user->empresa_id)
            ),
            $this->mensajesValidacionUnidad()
        );

        /*
         * La empresa y el estado no pueden cambiarse desde edición.
         */
        $unidad->update([
            'placa' => mb_strtoupper(trim($validated['placa'])),
            'marca' => $validated['marca'] ?? null,
            'total_tanques' => $validated['total_tanques'],
            'cantidad_tanques_con_licencia' => $validated['cantidad_tanques_con_licencia'],
            'capacidad_total' => $validated['capacidad_total'],
            'capacidad_cubierta' => $validated['capacidad_cubierta'],
            'modelo_medicion' => $validated['modelo_medicion'],
            'actualizado_por' => $user->id,
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        ['unidad' => $unidad]
                    )
                )
                ->with('success', 'Unidad actualizada correctamente.');
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    ['unidad' => $unidad]
                )
            )
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function inactivar(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadNoInactiva($unidad);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:150',
                Rule::in($this->motivosInactivacion()),
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 150 caracteres.',
        ]);

        $unidad->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        ['unidad' => $unidad]
                    )
                )
                ->with('success', 'Unidad inactivada correctamente.');
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    ['unidad' => $unidad]
                )
            )
            ->with('success', 'Unidad inactivada correctamente.');
    }

    public function reactivar(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadInactivaParaReactivacion($unidad);

        $unidad->update([
            /*
             * Una unidad reactivada no se vuelve operativa automáticamente.
             * Regresa a registrada para completar sus validaciones.
             */
            'estado' => 'registrada',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        $mensaje = 'Unidad reactivada correctamente. Queda en estado registrada para validación operativa.';

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        ['unidad' => $unidad]
                    )
                )
                ->with('success', $mensaje);
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    ['unidad' => $unidad]
                )
            )
            ->with('success', $mensaje);
    }

    private function reglasValidacionUnidad(
        ?Unidad $unidad,
        bool $esUsuarioDieselCop
    ): array {
        return [
            'empresa_id' => [
                is_null($unidad) && $esUsuarioDieselCop
                    ? 'required'
                    : 'nullable',

                'integer',

                Rule::exists('empresas', 'id')
                    ->where('estado', 'activa'),
            ],

            'placa' => [
                'required',
                'string',
                'max:30',

                Rule::unique('unidades', 'placa')
                    ->ignore($unidad?->id),
            ],

            'marca' => [
                'nullable',
                'string',
                'max:100',
            ],

            'total_tanques' => [
                'required',
                'integer',
                'min:1',
                'max:3',
            ],

            'cantidad_tanques_con_licencia' => [
                'required',
                'integer',
                'min:1',
                'max:3',
                'lte:total_tanques',
            ],

            'capacidad_total' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'capacidad_cubierta' => [
                'required',
                'numeric',
                'gt:0',
                'lte:capacidad_total',
                'max:99999999.99',
            ],

            'modelo_medicion' => [
                'required',
                Rule::in(array_keys($this->modelosMedicion())),
            ],
        ];
    }

    private function mensajesValidacionUnidad(): array
    {
        return [
            'empresa_id.required' => 'Debe seleccionar una empresa.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida o no está activa.',

            'placa.required' => 'Debe ingresar la placa de la unidad.',
            'placa.max' => 'La placa no debe exceder 30 caracteres.',
            'placa.unique' => 'Ya existe una unidad registrada con esta placa.',

            'marca.max' => 'La marca no debe exceder 100 caracteres.',

            'total_tanques.required' => 'Debe indicar el total de tanques de la unidad.',
            'total_tanques.integer' => 'El total de tanques debe ser un número entero.',
            'total_tanques.min' => 'La unidad debe tener al menos un tanque.',
            'total_tanques.max' => 'La unidad puede tener como máximo tres tanques.',

            'cantidad_tanques_con_licencia.required' => 'Debe indicar la cantidad de tanques cubiertos por la licencia.',
            'cantidad_tanques_con_licencia.integer' => 'La cantidad de tanques cubiertos debe ser un número entero.',
            'cantidad_tanques_con_licencia.min' => 'La licencia debe cubrir al menos un tanque.',
            'cantidad_tanques_con_licencia.max' => 'La licencia puede cubrir como máximo tres tanques.',
            'cantidad_tanques_con_licencia.lte' => 'La cantidad de tanques cubiertos no puede superar el total de tanques.',

            'capacidad_total.required' => 'Debe ingresar la capacidad total de la unidad.',
            'capacidad_total.numeric' => 'La capacidad total debe ser un valor numérico.',
            'capacidad_total.gt' => 'La capacidad total debe ser mayor que cero.',

            'capacidad_cubierta.required' => 'Debe ingresar la capacidad cubierta por la licencia.',
            'capacidad_cubierta.numeric' => 'La capacidad cubierta debe ser un valor numérico.',
            'capacidad_cubierta.gt' => 'La capacidad cubierta debe ser mayor que cero.',
            'capacidad_cubierta.lte' => 'La capacidad cubierta no puede superar la capacidad total.',

            'modelo_medicion.required' => 'Debe seleccionar un modelo de medición.',
            'modelo_medicion.in' => 'El modelo de medición seleccionado no es válido.',
        ];
    }

    private function modelosMedicion(): array
    {
        return [
            'galones_hora' => 'Galones por hora',
            'galones_kilometro' => 'Galones por kilómetro',
            'galones_viaje' => 'Galones por viaje',
        ];
    }

    private function estadosUnidad(): array
    {
        return [
            'registrada' => 'Registrada',
            'activa' => 'Activa',
            'inactiva' => 'Inactiva',
        ];
    }

    private function motivosInactivacion(): array
    {
        return [
            'Falta de uso',
            'Unidad vendida',
            'Unidad fuera de operación',
            'Unidad reemplazada',
            'Datos incorrectos en registro',
            'Solicitud administrativa',
            'Suspensión temporal',
            'Otro',
        ];
    }

    private function autorizarAccesoUnidad(Unidad $unidad): void
    {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $unidad->empresa_id !== (int) $user->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta unidad.'
            );
        }
    }

    /**
     * Bloquea cualquier operación administrativa cuando la empresa
     * propietaria está inactiva.
     */
    private function validarEmpresaActivaUnidad(Unidad $unidad): void
    {
        $unidad->loadMissing('empresa');

        if (
            ! $unidad->empresa
            || $unidad->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre esta unidad porque la empresa está inactiva.'
            );
        }
    }

    private function validarEmpresaActivaPorId(int $empresaId): void
    {
        $empresaActiva = Empresa::query()
            ->where('id', $empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(
                403,
                'No se puede registrar la unidad porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Una unidad inactiva debe reactivarse desde su ficha antes de editarse.
     */
    private function validarUnidadEditable(Unidad $unidad): void
    {
        if ($unidad->estado === 'inactiva') {
            abort(
                403,
                'No se puede modificar esta unidad porque está inactiva. Debe reactivarla desde la ficha antes de realizar cambios.'
            );
        }
    }

    /**
     * Evita repetir la inactivación de una unidad.
     */
    private function validarUnidadNoInactiva(Unidad $unidad): void
    {
        if ($unidad->estado === 'inactiva') {
            abort(
                403,
                'No se puede inactivar esta unidad porque ya se encuentra inactiva.'
            );
        }
    }

    /**
     * Evita reactivar una unidad que no se encuentre inactiva.
     */
    private function validarUnidadInactivaParaReactivacion(
        Unidad $unidad
    ): void {
        if ($unidad->estado !== 'inactiva') {
            abort(
                403,
                'No se puede reactivar esta unidad porque no se encuentra inactiva.'
            );
        }
    }
}