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
    public function index(Request $request)
    {
        return view(
            'gasolineras-externas.index',
            $this->prepararConsultaGasolinerasExternas($request, false)
        );
    }

    public function consultaVentana(Request $request)
    {
        return view(
            'gasolineras-externas.index-ventana',
            $this->prepararConsultaGasolinerasExternas($request, false)
        );
    }

    public function administrar(Request $request)
    {
        return view(
            'gasolineras-externas.administrar',
            $this->prepararConsultaGasolinerasExternas($request, true)
        );
    }

    public function administrarVentana(Request $request)
    {
        return view(
            'gasolineras-externas.administrar-ventana',
            $this->prepararConsultaGasolinerasExternas($request, true)
        );
    }

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
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
            'gasolinera_externa_ids' => ['nullable', 'array'],
            'gasolinera_externa_ids.*' => [
                'integer',
                'distinct',
                'exists:gasolineras_externas,id',
            ],
            'estado_ids' => ['nullable', 'array'],
            'estado_ids.*' => [
                'string',
                'distinct',
                Rule::in(['activa', 'inactiva']),
            ],
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'gasolinera_externa_id' => [
                'nullable',
                'integer',
                'exists:gasolineras_externas,id',
            ],
            'estado' => ['nullable', Rule::in(['activa', 'inactiva'])],
        ]);

        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->when(
                filled($validated['empresa_id'] ?? null),
                fn ($items) => $items->push((int) $validated['empresa_id'])
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
                fn ($items) => $items->push(
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
                fn ($items) => $items->push($validated['estado'])
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

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $consultaEjecutada = $request->boolean('consultar');

        /*
         * La empresa obligatoria del usuario empresarial limita el alcance,
         * pero no ejecuta automáticamente Consulta ni Administrar.
         */
        $hayFiltros = $consultaEjecutada
            || count($gasolineraExternaIds) > 0
            || count($estadoIds) > 0
            || (
                $esUsuarioDieselCop
                && count($empresaIds) > 0
            );

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
                fn (Builder $query) => $query->where('estado', 'activa')
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

        return [
            'gasolinerasExternas' => $gasolinerasExternas,
            'gasolinerasExternasSelector' => $gasolinerasExternasSelector,
            'empresasSelector' => $empresasSelector,
            'empresaIds' => $empresaIds,
            'gasolineraExternaIds' => $gasolineraExternaIds,
            'estadoIds' => $estadoIds,
            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,
            'totalGasolinerasExternas' => (clone $baseResumen)->count(),
            'gasolinerasExternasActivas' => (clone $baseResumen)
                ->where('estado', 'activa')
                ->count(),
            'gasolinerasExternasInactivas' => (clone $baseResumen)
                ->where('estado', 'inactiva')
                ->count(),
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    public function create()
    {
        return view(
            'gasolineras-externas.create',
            $this->prepararFormularioGasolineraExterna()
        );
    }

    public function createVentana()
    {
        return view(
            'gasolineras-externas.create-ventana',
            $this->prepararFormularioGasolineraExterna()
        );
    }

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

        return compact(
            'empresasSelector',
            'esUsuarioDieselCop',
            'empresaUsuario'
        );
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $rules = [
            'compania' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
        ];

        $rules['empresa_id'] = $esUsuarioDieselCop
            ? [
                'required',
                'integer',
                Rule::exists('empresas', 'id')->where('estado', 'activa'),
            ]
            : ['nullable'];

        $validated = $request->validate($rules);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        $compania = trim($validated['compania']);
        $direccion = trim($validated['direccion']);

        $request->validate([
            'direccion' => [
                Rule::unique('gasolineras_externas', 'direccion')
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

        $route = $request->input('return_to') === 'ventana'
            ? 'gasolineras-externas.create.ventana'
            : 'gasolineras-externas.create';

        return redirect()
            ->route($route, $request->query())
            ->with(
                'success',
                'Gasolinera externa guardada correctamente.'
            );
    }

    public function show(GasolineraExterna $gasolineraExterna)
    {
        return $this->mostrarFicha($gasolineraExterna, false);
    }

    public function showVentana(GasolineraExterna $gasolineraExterna)
    {
        return $this->mostrarFicha($gasolineraExterna, true);
    }

    private function mostrarFicha(
        GasolineraExterna $gasolineraExterna,
        bool $modoVentana
    ) {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view(
            $modoVentana
                ? 'gasolineras-externas.show-ventana'
                : 'gasolineras-externas.show',
            compact('gasolineraExterna')
        );
    }

    public function edit(GasolineraExterna $gasolineraExterna)
    {
        return $this->mostrarFormularioEdicion(
            $gasolineraExterna,
            false
        );
    }

    public function editVentana(GasolineraExterna $gasolineraExterna)
    {
        return $this->mostrarFormularioEdicion(
            $gasolineraExterna,
            true
        );
    }

    private function mostrarFormularioEdicion(
        GasolineraExterna $gasolineraExterna,
        bool $modoVentana
    ) {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);
        $this->validarGasolineraExternaActiva($gasolineraExterna);

        $data = $this->prepararFormularioGasolineraExterna();
        $data['gasolineraExterna'] = $gasolineraExterna;

        return view(
            $modoVentana
                ? 'gasolineras-externas.edit-ventana'
                : 'gasolineras-externas.edit',
            $data
        );
    }

    public function update(
        Request $request,
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);
        $this->validarGasolineraExternaActiva($gasolineraExterna);

        $validated = $request->validate([
            'compania' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
        ]);

        $compania = trim($validated['compania']);
        $direccion = trim($validated['direccion']);

        $request->validate([
            'direccion' => [
                Rule::unique('gasolineras_externas', 'direccion')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'empresa_id',
                                $gasolineraExterna->empresa_id
                            )
                            ->where('compania', $compania)
                    )
                    ->ignore($gasolineraExterna->id),
            ],
        ]);

        $gasolineraExterna->update([
            'compania' => $compania,
            'direccion' => $direccion,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $gasolineraExterna,
            'Gasolinera externa actualizada correctamente.'
        );
    }

    public function inactivar(
        Request $request,
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);
        $this->validarGasolineraExternaActiva($gasolineraExterna);

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
        ]);

        $gasolineraExterna->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $gasolineraExterna,
            'Gasolinera externa inactivada correctamente.'
        );
    }

    public function reactivar(
        Request $request,
        GasolineraExterna $gasolineraExterna
    ) {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

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

        return $this->redirigirAFicha(
            $request,
            $gasolineraExterna,
            'Gasolinera externa reactivada correctamente.'
        );
    }

    private function redirigirAFicha(
        Request $request,
        GasolineraExterna $gasolineraExterna,
        string $mensaje
    ) {
        $routeParameters = array_merge(
            ['gasolineraExterna' => $gasolineraExterna],
            $request->query()
        );

        $route = $request->input('return_to') === 'ventana'
            ? 'gasolineras-externas.show.ventana'
            : 'gasolineras-externas.show';

        return redirect()
            ->route($route, $routeParameters)
            ->with('success', $mensaje);
    }

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

    private function validarEmpresaActivaPorId(int $empresaId): void
    {
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