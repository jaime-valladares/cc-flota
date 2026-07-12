<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\GasolineraExterna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GasolineraExternaController extends Controller
{
    /**
     * Display the informational external gas station consultation panel.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request, false);

        return view('gasolineras-externas.index', $data);
    }

    /**
     * Display the standalone informational external gas station consultation panel.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request, false);

        return view('gasolineras-externas.index-ventana', $data);
    }

    /**
     * Display the administrative external gas station search panel.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request, true);

        return view('gasolineras-externas.administrar', $data);
    }

    /**
     * Display the standalone administrative external gas station search panel.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request, true);

        return view('gasolineras-externas.administrar-ventana', $data);
    }

    /**
     * Prepare external gas station query data for normal and standalone screens.
     */
    private function prepararConsultaGasolinerasExternas(Request $request, bool $soloEmpresasActivas): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'busqueda_empresa' => ['nullable', 'string', 'max:150'],
            'compania' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:activa,inactiva'],

            /*
             * Nuevo estándar: multiselección.
             */
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['nullable', 'integer', 'exists:empresas,id'],

            'gasolinera_externa_ids' => ['nullable', 'array'],
            'gasolinera_externa_ids.*' => ['nullable', 'integer', 'exists:gasolineras_externas,id'],

            /*
             * Compatibilidad temporal con filtros anteriores.
             */
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'gasolinera_externa_id' => ['nullable', 'integer', 'exists:gasolineras_externas,id'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',
            'gasolinera_externa_id.exists' => 'La gasolinera externa seleccionada no es válida.',
            'gasolinera_externa_ids.*.exists' => 'Una de las gasolineras externas seleccionadas no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $busquedaEmpresa = trim((string) ($validated['busqueda_empresa'] ?? ''));
        $compania = trim((string) ($validated['compania'] ?? ''));
        $estado = $validated['estado'] ?? null;

        /*
         * Compatibilidad:
         * - Nuevo estándar: empresa_ids[].
         * - Filtro anterior: empresa_id.
         */
        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->when(filled($validated['empresa_id'] ?? null), function ($collection) use ($validated) {
                return $collection->push($validated['empresa_id']);
            })
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        /*
         * Compatibilidad:
         * - Nuevo estándar: gasolinera_externa_ids[].
         * - Filtro anterior: gasolinera_externa_id.
         */
        $gasolineraExternaIds = collect($validated['gasolinera_externa_ids'] ?? [])
            ->when(filled($validated['gasolinera_externa_id'] ?? null), function ($collection) use ($validated) {
                return $collection->push($validated['gasolinera_externa_id']);
            })
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $empresaId = $empresaIds[0] ?? null;
        $gasolineraExternaId = $gasolineraExternaIds[0] ?? null;

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($compania)
            || count($empresaIds) > 0
            || count($gasolineraExternaIds) > 0
            || filled($estado);

        $empresasSelector = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->where('estado', 'activa');
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseGasolinerasExternasQuery = GasolineraExterna::query()
            ->with('empresa')
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        /*
         * Selector de gasolineras externas:
         * respeta la jerarquía de empresa para que el multiselect de compañía
         * no muestre opciones fuera de la empresa o empresas consultadas.
         */
        $gasolinerasExternasSelector = (clone $baseGasolinerasExternasQuery)
            ->when(filled($busquedaEmpresa), function ($query) use ($busquedaEmpresa) {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery
                        ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            })
            ->when(count($empresaIds) > 0, function ($query) use ($empresaIds) {
                $query->whereIn('empresa_id', $empresaIds);
            })
            ->orderBy('compania')
            ->orderBy('direccion')
            ->get();

        $gasolinerasExternas = (clone $baseGasolinerasExternasQuery)
            ->when($hayFiltros && filled($busquedaEmpresa), function ($query) use ($busquedaEmpresa) {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery
                        ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            })
            ->when($hayFiltros && count($empresaIds) > 0, function ($query) use ($empresaIds) {
                $query->whereIn('empresa_id', $empresaIds);
            })
            ->when($hayFiltros && filled($compania), function ($query) use ($compania) {
                $query->where('compania', 'like', '%' . $compania . '%');
            })
            ->when($hayFiltros && count($gasolineraExternaIds) > 0, function ($query) use ($gasolineraExternaIds) {
                $query->whereIn('id', $gasolineraExternaIds);
            })
            ->when($hayFiltros && in_array($estado, ['activa', 'inactiva'], true), function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when(! $hayFiltros, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('compania')
            ->orderBy('direccion')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = GasolineraExterna::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        $totalGasolinerasExternas = (clone $baseResumen)->count();
        $gasolinerasExternasActivas = (clone $baseResumen)->where('estado', 'activa')->count();
        $gasolinerasExternasInactivas = (clone $baseResumen)->where('estado', 'inactiva')->count();

        return [
            'gasolinerasExternas' => $gasolinerasExternas,
            'gasolinerasExternasSelector' => $gasolinerasExternasSelector,
            'empresasSelector' => $empresasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'compania' => $compania,
            'estado' => $estado,

            'empresaIds' => $empresaIds,
            'gasolineraExternaIds' => $gasolineraExternaIds,

            /*
             * Variables simples para compatibilidad temporal.
             */
            'empresaId' => $empresaId,
            'gasolineraExternaId' => $gasolineraExternaId,

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
     * Show the form for creating a new external gas station.
     */
    public function create()
    {
        $data = $this->prepararFormularioGasolineraExterna();

        return view('gasolineras-externas.create', $data);
    }

    /**
     * Show the standalone form for creating a new external gas station.
     */
    public function createVentana()
    {
        $data = $this->prepararFormularioGasolineraExterna();

        return view('gasolineras-externas.create-ventana', $data);
    }

    /**
     * Prepare common data for create/edit external gas station forms.
     */
    private function prepararFormularioGasolineraExterna(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        if (! $esUsuarioDieselCop && (! $empresaUsuario || $empresaUsuario->estado !== 'activa')) {
            abort(403, 'No se puede operar sobre gasolineras externas porque la empresa está inactiva.');
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
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
     * Store a newly created external gas station.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $baseRules = [
            'compania' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
        ];

        if ($esUsuarioDieselCop) {
            $baseRules['empresa_id'] = [
                'required',
                'integer',
                Rule::exists('empresas', 'id')->where('estado', 'activa'),
            ];
        } else {
            $baseRules['empresa_id'] = ['nullable'];
        }

        $validated = $request->validate($baseRules, [
            'empresa_id.required' => 'Debe seleccionar una empresa.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida o no está activa.',
            'compania.required' => 'Debe ingresar la compañía de la gasolinera externa.',
            'direccion.required' => 'Debe ingresar la dirección de la gasolinera externa.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        $request->validate([
            'direccion' => [
                Rule::unique('gasolineras_externas', 'direccion')
                    ->where('empresa_id', $empresaId)
                    ->where('compania', $validated['compania']),
            ],
        ], [
            'direccion.unique' => 'Ya existe una gasolinera externa con esa compañía y dirección para la empresa seleccionada.',
        ]);

        GasolineraExterna::create([
            'empresa_id' => $empresaId,
            'direccion' => $validated['direccion'],
            'compania' => $validated['compania'],
            'estado' => 'activa',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.create.ventana')
                ->with('success', 'Gasolinera externa guardada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.create')
            ->with('success', 'Gasolinera externa guardada correctamente.');
    }

    /**
     * Display the specified external gas station.
     */
    public function show(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('gasolineras-externas.show', compact('gasolineraExterna'));
    }

    /**
     * Display the specified external gas station in standalone window.
     */
    public function showVentana(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('gasolineras-externas.show-ventana', compact('gasolineraExterna'));
    }

    /**
     * Show the form for editing the specified external gas station.
     */
    public function edit(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $data = $this->prepararFormularioGasolineraExterna();
        $data['gasolineraExterna'] = $gasolineraExterna;

        return view('gasolineras-externas.edit', $data);
    }

    /**
     * Show the standalone form for editing the specified external gas station.
     */
    public function editVentana(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $data = $this->prepararFormularioGasolineraExterna();
        $data['gasolineraExterna'] = $gasolineraExterna;

        return view('gasolineras-externas.edit-ventana', $data);
    }

    /**
     * Update the specified external gas station.
     */
    public function update(Request $request, GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $validated = $request->validate([
            'compania' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
        ], [
            'compania.required' => 'Debe ingresar la compañía de la gasolinera externa.',
            'direccion.required' => 'Debe ingresar la dirección de la gasolinera externa.',
        ]);

        $empresaId = (int) $gasolineraExterna->empresa_id;

        $request->validate([
            'direccion' => [
                Rule::unique('gasolineras_externas', 'direccion')
                    ->where('empresa_id', $empresaId)
                    ->where('compania', $validated['compania'])
                    ->ignore($gasolineraExterna->id),
            ],
        ], [
            'direccion.unique' => 'Ya existe una gasolinera externa con esa compañía y dirección para la empresa actual.',
        ]);

        $gasolineraExterna->update([
            'direccion' => $validated['direccion'],
            'compania' => $validated['compania'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.show.ventana', $gasolineraExterna)
                ->with('success', 'Gasolinera externa actualizada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.show', $gasolineraExterna)
            ->with('success', 'Gasolinera externa actualizada correctamente.');
    }

    /**
     * Deactivate an external gas station.
     */
    public function inactivar(Request $request, GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:No continúa en uso,Cambio de proveedor,Cierre de estación,Datos incorrectos en registro,Solicitud del cliente,Suspensión administrativa,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
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

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.show.ventana', $gasolineraExterna)
                ->with('success', 'Gasolinera externa inactivada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.show', $gasolineraExterna)
            ->with('success', 'Gasolinera externa inactivada correctamente.');
    }

    /**
     * Reactivate an external gas station.
     */
    public function reactivar(Request $request, GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);
        $this->validarEmpresaActivaGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.show.ventana', $gasolineraExterna)
                ->with('success', 'Gasolinera externa reactivada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.show', $gasolineraExterna)
            ->with('success', 'Gasolinera externa reactivada correctamente.');
    }

    /**
     * Prevent company users from accessing other companies' external gas stations.
     */
    private function autorizarAccesoGasolineraExterna(GasolineraExterna $gasolineraExterna): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $gasolineraExterna->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta gasolinera externa.');
        }
    }

    /**
     * Ensure the external gas station belongs to an active company before administrative actions.
     */
    private function validarEmpresaActivaGasolineraExterna(GasolineraExterna $gasolineraExterna): void
    {
        $gasolineraExterna->loadMissing('empresa');

        if (! $gasolineraExterna->empresa || $gasolineraExterna->empresa->estado !== 'activa') {
            abort(403, 'No se puede operar sobre esta gasolinera externa porque la empresa está inactiva.');
        }
    }

    /**
     * Ensure the selected company is active before creating operational records.
     */
    private function validarEmpresaActivaPorId(int $empresaId): void
    {
        $empresaActiva = Empresa::query()
            ->where('id', $empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(403, 'No se puede operar sobre gasolineras externas porque la empresa está inactiva.');
        }
    }
}