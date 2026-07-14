<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\GasolineraExterna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GasolineraExternaController extends Controller
{
    /**
     * Muestra la consulta informativa de gasolineras externas.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas(
            request: $request,
            modoAdministracion: false
        );

        return view('gasolineras-externas.index', $data);
    }

    /**
     * Muestra la consulta informativa en una ventana independiente.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas(
            request: $request,
            modoAdministracion: false
        );

        return view('gasolineras-externas.index-ventana', $data);
    }

    /**
     * Muestra la administración de gasolineras externas.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas(
            request: $request,
            modoAdministracion: true
        );

        return view('gasolineras-externas.administrar', $data);
    }

    /**
     * Muestra la administración en una ventana independiente.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas(
            request: $request,
            modoAdministracion: true
        );

        return view('gasolineras-externas.administrar-ventana', $data);
    }

    /**
     * Prepara los datos compartidos por Consulta y Administrar.
     */
    private function prepararConsultaGasolinerasExternas(
        Request $request,
        bool $modoAdministracion
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_ids' => [
                'nullable',
                'array',
            ],
            'empresa_ids.*' => [
                'integer',
                'distinct',
                'exists:empresas,id',
            ],

            'gasolinera_externa_ids' => [
                'nullable',
                'array',
            ],
            'gasolinera_externa_ids.*' => [
                'integer',
                'distinct',
                'exists:gasolineras_externas,id',
            ],

            'estado_ids' => [
                'nullable',
                'array',
            ],
            'estado_ids.*' => [
                'string',
                'distinct',
                Rule::in(['activa', 'inactiva']),
            ],

            /*
             * Compatibilidad temporal con enlaces anteriores.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],
            'gasolinera_externa_id' => [
                'nullable',
                'integer',
                'exists:gasolineras_externas,id',
            ],
            'estado' => [
                'nullable',
                Rule::in(['activa', 'inactiva']),
            ],
        ], [
            'empresa_ids.array' => 'La selección de empresas no es válida.',
            'empresa_ids.*.integer' => 'Una de las empresas seleccionadas no es válida.',
            'empresa_ids.*.distinct' => 'No debe seleccionar una empresa más de una vez.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no existe.',

            'gasolinera_externa_ids.array' => 'La selección de gasolineras externas no es válida.',
            'gasolinera_externa_ids.*.integer' => 'Una de las gasolineras externas seleccionadas no es válida.',
            'gasolinera_externa_ids.*.distinct' => 'No debe seleccionar una gasolinera externa más de una vez.',
            'gasolinera_externa_ids.*.exists' => 'Una de las gasolineras externas seleccionadas no existe.',

            'estado_ids.array' => 'La selección de estados no es válida.',
            'estado_ids.*.distinct' => 'No debe seleccionar un estado más de una vez.',
            'estado_ids.*.in' => 'Uno de los estados seleccionados no es válido.',

            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'gasolinera_externa_id.exists' => 'La gasolinera externa seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->when(
                filled($validated['empresa_id'] ?? null),
                fn ($collection) => $collection->push(
                    (int) $validated['empresa_id']
                )
            )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $gasolineraExternaIds = collect(
            $validated['gasolinera_externa_ids'] ?? []
        )
            ->when(
                filled($validated['gasolinera_externa_id'] ?? null),
                fn ($collection) => $collection->push(
                    (int) $validated['gasolinera_externa_id']
                )
            )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $estadoIds = collect($validated['estado_ids'] ?? [])
            ->when(
                filled($validated['estado'] ?? null),
                fn ($collection) => $collection->push(
                    $validated['estado']
                )
            )
            ->filter(
                fn ($estado) => in_array(
                    $estado,
                    ['activa', 'inactiva'],
                    true
                )
            )
            ->unique()
            ->values()
            ->all();

        /*
         * Un usuario perteneciente a una empresa solamente puede consultar
         * registros de su propia empresa.
         */
        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || count($empresaIds) > 0
            || count($gasolineraExternaIds) > 0
            || count($estadoIds) > 0;

        /*
         * En Administrar solamente deben aparecer empresas activas.
         * Consulta mantiene también empresas inactivas para fines históricos.
         */
        $empresasSelector = Empresa::query()
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where(
                    'id',
                    $user->empresa_id
                )
            )
            ->when(
                $modoAdministracion,
                fn (Builder $query) => $query->where(
                    'estado',
                    'activa'
                )
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseGasolinerasQuery = GasolineraExterna::query()
            ->with('empresa')
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where(
                    'empresa_id',
                    $user->empresa_id
                )
            )
            ->when(
                $modoAdministracion,
                fn (Builder $query) => $query->whereHas(
                    'empresa',
                    fn (Builder $empresaQuery) => $empresaQuery->where(
                        'estado',
                        'activa'
                    )
                )
            );

        /*
         * El selector de gasolineras respeta las empresas seleccionadas.
         */
        $gasolinerasExternasSelector = (clone $baseGasolinerasQuery)
            ->when(
                count($empresaIds) > 0,
                fn (Builder $query) => $query->whereIn(
                    'empresa_id',
                    $empresaIds
                )
            )
            ->orderBy('compania')
            ->orderBy('direccion')
            ->get();

        $gasolinerasExternas = (clone $baseGasolinerasQuery)
            ->when(
                $hayFiltros && count($empresaIds) > 0,
                fn (Builder $query) => $query->whereIn(
                    'empresa_id',
                    $empresaIds
                )
            )
            ->when(
                $hayFiltros && count($gasolineraExternaIds) > 0,
                fn (Builder $query) => $query->whereIn(
                    'id',
                    $gasolineraExternaIds
                )
            )
            ->when(
                $hayFiltros && count($estadoIds) > 0,
                fn (Builder $query) => $query->whereIn(
                    'estado',
                    $estadoIds
                )
            )
            ->when(
                ! $hayFiltros,
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('compania')
            ->orderBy('direccion')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = GasolineraExterna::query()
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where(
                    'empresa_id',
                    $user->empresa_id
                )
            )
            ->when(
                $modoAdministracion,
                fn (Builder $query) => $query->whereHas(
                    'empresa',
                    fn (Builder $empresaQuery) => $empresaQuery->where(
                        'estado',
                        'activa'
                    )
                )
            );

        $totalGasolinerasExternas = (clone $baseResumen)->count();

        $gasolinerasExternasActivas = (clone $baseResumen)
            ->where('estado', 'activa')
            ->count();

        $gasolinerasExternasInactivas = (clone $baseResumen)
            ->where('estado', 'inactiva')
            ->count();

        return [
            'gasolinerasExternas' => $gasolinerasExternas,
            'gasolinerasExternasSelector' => $gasolinerasExternasSelector,
            'empresasSelector' => $empresasSelector,

            'empresaIds' => $empresaIds,
            'gasolineraExternaIds' => $gasolineraExternaIds,
            'estadoIds' => $estadoIds,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,

            'totalGasolinerasExternas' => $totalGasolinerasExternas,
            'gasolinerasExternasActivas' => $gasolinerasExternasActivas,
            'gasolinerasExternasInactivas' => $gasolinerasExternasInactivas,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
        $data = $this->prepararFormularioGasolineraExterna();

        return view('gasolineras-externas.create', $data);
    }

    /**
     * Muestra el formulario de creación en ventana independiente.
     */
    public function createVentana()
    {
        $data = $this->prepararFormularioGasolineraExterna();

        return view('gasolineras-externas.create-ventana', $data);
    }

    /**
     * Prepara los datos compartidos por los formularios.
     */
    private function prepararFormularioGasolineraExterna(): array
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
                'No se puede operar sobre gasolineras externas porque la empresa está inactiva.'
            );
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        return [
            'empresasSelector' => $empresasSelector,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Guarda una nueva gasolinera externa.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $rules = [
            'compania' => [
                'required',
                'string',
                'max:150',
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
        ];

        if ($esUsuarioDieselCop) {
            $rules['empresa_id'] = [
                'required',
                'integer',
                Rule::exists('empresas', 'id')
                    ->where('estado', 'activa'),
            ];
        } else {
            $rules['empresa_id'] = [
                'nullable',
            ];
        }

        $validated = $request->validate($rules, [
            'empresa_id.required' => 'Debe seleccionar una empresa.',
            'empresa_id.integer' => 'La empresa seleccionada no es válida.',
            'empresa_id.exists' => 'La empresa seleccionada no existe o está inactiva.',

            'compania.required' => 'Debe ingresar la compañía de la gasolinera externa.',
            'compania.string' => 'La compañía debe ser un texto válido.',
            'compania.max' => 'La compañía no debe exceder 150 caracteres.',

            'direccion.required' => 'Debe ingresar la dirección de la gasolinera externa.',
            'direccion.string' => 'La dirección debe ser un texto válido.',
            'direccion.max' => 'La dirección no debe exceder 255 caracteres.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        $compania = trim($validated['compania']);
        $direccion = trim($validated['direccion']);

        $request->validate([
            'direccion' => [
                Rule::unique(
                    'gasolineras_externas',
                    'direccion'
                )
                    ->where(
                        fn ($query) => $query
                            ->where('empresa_id', $empresaId)
                            ->where('compania', $compania)
                    ),
            ],
        ], [
            'direccion.unique' => 'Ya existe una gasolinera externa con esa compañía y dirección para la empresa seleccionada.',
        ]);

        GasolineraExterna::create([
            'empresa_id' => $empresaId,
            'compania' => $compania,
            'direccion' => $direccion,
            'estado' => 'activa',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'gasolineras-externas.create.ventana',
                    $request->query()
                )
                ->with(
                    'success',
                    'Gasolinera externa guardada correctamente.'
                );
        }

        return redirect()
            ->route(
                'gasolineras-externas.create',
                $request->query()
            )
            ->with(
                'success',
                'Gasolinera externa guardada correctamente.'
            );
    }

    /**
     * Muestra la ficha administrativa.
     */
    public function show(
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view(
            'gasolineras-externas.show',
            compact('gasolineraExterna')
        );
    }

    /**
     * Muestra la ficha administrativa en ventana independiente.
     */
    public function showVentana(
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view(
            'gasolineras-externas.show-ventana',
            compact('gasolineraExterna')
        );
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarGasolineraExternaActiva(
            $gasolineraExterna
        );

        $data = $this->prepararFormularioGasolineraExterna();

        $data['gasolineraExterna'] = $gasolineraExterna;

        return view(
            'gasolineras-externas.edit',
            $data
        );
    }

    /**
     * Muestra el formulario de edición en ventana independiente.
     */
    public function editVentana(
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarGasolineraExternaActiva(
            $gasolineraExterna
        );

        $data = $this->prepararFormularioGasolineraExterna();

        $data['gasolineraExterna'] = $gasolineraExterna;

        return view(
            'gasolineras-externas.edit-ventana',
            $data
        );
    }

    /**
     * Actualiza una gasolinera externa.
     */
    public function update(
        Request $request,
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarGasolineraExternaActiva(
            $gasolineraExterna
        );

        $validated = $request->validate([
            'compania' => [
                'required',
                'string',
                'max:150',
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'compania.required' => 'Debe ingresar la compañía de la gasolinera externa.',
            'compania.string' => 'La compañía debe ser un texto válido.',
            'compania.max' => 'La compañía no debe exceder 150 caracteres.',

            'direccion.required' => 'Debe ingresar la dirección de la gasolinera externa.',
            'direccion.string' => 'La dirección debe ser un texto válido.',
            'direccion.max' => 'La dirección no debe exceder 255 caracteres.',
        ]);

        $empresaId = (int) $gasolineraExterna->empresa_id;

        $compania = trim($validated['compania']);
        $direccion = trim($validated['direccion']);

        $request->validate([
            'direccion' => [
                Rule::unique(
                    'gasolineras_externas',
                    'direccion'
                )
                    ->where(
                        fn ($query) => $query
                            ->where('empresa_id', $empresaId)
                            ->where('compania', $compania)
                    )
                    ->ignore($gasolineraExterna->id),
            ],
        ], [
            'direccion.unique' => 'Ya existe una gasolinera externa con esa compañía y dirección para la empresa actual.',
        ]);

        $gasolineraExterna->update([
            'compania' => $compania,
            'direccion' => $direccion,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $routeParameters = array_merge(
            ['gasolineraExterna' => $gasolineraExterna],
            $request->query()
        );

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'gasolineras-externas.show.ventana',
                    $routeParameters
                )
                ->with(
                    'success',
                    'Gasolinera externa actualizada correctamente.'
                );
        }

        return redirect()
            ->route(
                'gasolineras-externas.show',
                $routeParameters
            )
            ->with(
                'success',
                'Gasolinera externa actualizada correctamente.'
            );
    }

    /**
     * Inactiva una gasolinera externa.
     */
    public function inactivar(
        Request $request,
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarGasolineraExternaActiva(
            $gasolineraExterna
        );

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                Rule::in([
                    'No continúa en uso',
                    'Cambio de proveedor',
                    'Cierre de estación',
                    'Datos incorrectos en registro',
                    'Solicitud del cliente',
                    'Suspensión administrativa',
                    'Otro',
                ]),
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.string' => 'El motivo de inactivación no es válido.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $gasolineraExterna->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $routeParameters = array_merge(
            ['gasolineraExterna' => $gasolineraExterna],
            $request->query()
        );

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'gasolineras-externas.show.ventana',
                    $routeParameters
                )
                ->with(
                    'success',
                    'Gasolinera externa inactivada correctamente.'
                );
        }

        return redirect()
            ->route(
                'gasolineras-externas.show',
                $routeParameters
            )
            ->with(
                'success',
                'Gasolinera externa inactivada correctamente.'
            );
    }

    /**
     * Reactiva una gasolinera externa.
     */
    public function reactivar(
        Request $request,
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna(
            $gasolineraExterna
        );

        $this->validarEmpresaActivaGasolineraExterna(
            $gasolineraExterna
        );

        if ($gasolineraExterna->estado !== 'inactiva') {
            abort(
                409,
                'La gasolinera externa ya se encuentra activa.'
            );
        }

        $gasolineraExterna->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $routeParameters = array_merge(
            ['gasolineraExterna' => $gasolineraExterna],
            $request->query()
        );

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'gasolineras-externas.show.ventana',
                    $routeParameters
                )
                ->with(
                    'success',
                    'Gasolinera externa reactivada correctamente.'
                );
        }

        return redirect()
            ->route(
                'gasolineras-externas.show',
                $routeParameters
            )
            ->with(
                'success',
                'Gasolinera externa reactivada correctamente.'
            );
    }

    /**
     * Evita que un usuario empresarial acceda a registros de otra empresa.
     */
    private function autorizarAccesoGasolineraExterna(
        GasolineraExterna $gasolineraExterna
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $gasolineraExterna->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta gasolinera externa.'
            );
        }
    }

    /**
     * Impide operaciones administrativas cuando la empresa está inactiva.
     */
    private function validarEmpresaActivaGasolineraExterna(
        GasolineraExterna $gasolineraExterna
    ): void {
        $gasolineraExterna->loadMissing('empresa');

        if (
            ! $gasolineraExterna->empresa
            || $gasolineraExterna->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre esta gasolinera externa porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Verifica que la empresa seleccionada esté activa.
     */
    private function validarEmpresaActivaPorId(
        int $empresaId
    ): void {
        $empresaActiva = Empresa::query()
            ->where('id', $empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(
                403,
                'No se puede operar sobre gasolineras externas porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Impide editar o volver a inactivar una gasolinera ya inactiva.
     */
    private function validarGasolineraExternaActiva(
        GasolineraExterna $gasolineraExterna
    ): void {
        if ($gasolineraExterna->estado !== 'activa') {
            abort(
                409,
                'La gasolinera externa está inactiva y no puede modificarse.'
            );
        }
    }
}