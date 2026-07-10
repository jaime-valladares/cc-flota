<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Unidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnidadController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request, false);

        return view('unidades.index', $data);
    }

    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request, false);

        return view('unidades.index-ventana', $data);
    }

    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request, true);

        return view('unidades.administrar', $data);
    }

    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request, true);

        return view('unidades.administrar-ventana', $data);
    }

    private function prepararConsultaUnidades(Request $request, bool $soloEmpresasActivas): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        // Compatibilidad temporal con filtro simple anterior.
        $empresaId = $request->input('empresa_id');
        $estado = $request->input('estado');

        // Nuevos filtros de consulta.
        $busqueda = trim((string) $request->input('busqueda', ''));

        $empresaIds = collect($request->input('empresa_ids', []))
            ->filter()
            ->values()
            ->all();

        $placas = collect($request->input('placas', []))
            ->filter()
            ->values()
            ->all();

        $modelosMedicionSeleccionados = collect($request->input('modelos_medicion', []))
            ->filter()
            ->values()
            ->all();

        // Compatibilidad temporal con filtros antiguos.
        $modeloMedicion = $request->input('modelo_medicion');
        $placa = $request->input('placa');

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
            $empresaIds = [$user->empresa_id];
        }

        $hayFiltros = $request->boolean('consultar')
            || filled($empresaId)
            || filled($estado)
            || filled($busqueda)
            || filled($placa)
            || filled($modeloMedicion)
            || count($empresaIds) > 0
            || count($placas) > 0
            || count($modelosMedicionSeleccionados) > 0;

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->where('estado', 'activa');
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseQuery = Unidad::query()
            ->with(['empresa'])
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        $placasSelector = (clone $baseQuery)
            ->select('placa')
            ->whereNotNull('placa')
            ->orderBy('placa')
            ->pluck('placa')
            ->unique()
            ->values();

        $totalUnidades = (clone $baseQuery)->count();
        $totalRegistradas = (clone $baseQuery)->where('estado', 'registrada')->count();
        $totalActivas = (clone $baseQuery)->where('estado', 'activa')->count();
        $totalInactivas = (clone $baseQuery)->where('estado', 'inactiva')->count();

        $unidadesQuery = Unidad::query()
            ->with(['empresa'])
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            })
            ->when($hayFiltros && count($empresaIds) > 0, function ($query) use ($empresaIds) {
                $query->whereIn('empresa_id', $empresaIds);
            })
            ->when($hayFiltros && count($empresaIds) === 0 && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($hayFiltros && filled($estado), function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when($hayFiltros && filled($busqueda), function ($query) use ($busqueda) {
                $query->where(function ($subQuery) use ($busqueda) {
                    $subQuery
                        ->where('placa', 'like', '%' . $busqueda . '%')
                        ->orWhereHas('empresa', function ($empresaQuery) use ($busqueda) {
                            $empresaQuery
                                ->where('nombre_legal', 'like', '%' . $busqueda . '%')
                                ->orWhere('nombre_comercial', 'like', '%' . $busqueda . '%');
                        });
                });
            })
            ->when($hayFiltros && count($placas) > 0, function ($query) use ($placas) {
                $query->whereIn('placa', $placas);
            })
            ->when($hayFiltros && count($modelosMedicionSeleccionados) > 0, function ($query) use ($modelosMedicionSeleccionados) {
                $query->whereIn('modelo_medicion', $modelosMedicionSeleccionados);
            })
            ->when($hayFiltros && filled($modeloMedicion), function ($query) use ($modeloMedicion) {
                $query->where('modelo_medicion', $modeloMedicion);
            })
            ->when($hayFiltros && filled($placa), function ($query) use ($placa) {
                $query->where('placa', 'like', '%' . $placa . '%');
            })
            ->when(! $hayFiltros, function ($query) {
                $query->whereRaw('1 = 0');
            });

        $resumenUnidades = [
            'total' => $hayFiltros ? (clone $unidadesQuery)->count() : $totalUnidades,
            'registradas' => $hayFiltros ? (clone $unidadesQuery)->where('estado', 'registrada')->count() : $totalRegistradas,
            'activas' => $hayFiltros ? (clone $unidadesQuery)->where('estado', 'activa')->count() : $totalActivas,
            'inactivas' => $hayFiltros ? (clone $unidadesQuery)->where('estado', 'inactiva')->count() : $totalInactivas,
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
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ];
    }

    public function create(): View
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->where('estado', 'activa')
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        return view('unidades.create', [
            'empresas' => $empresas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    public function createVentana(): View
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->where('estado', 'activa')
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        return view('unidades.create-ventana', [
            'empresas' => $empresas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate($this->reglasValidacionUnidad($request, null, $esUsuarioDieselCop));

        if (! $esUsuarioDieselCop) {
            $validated['empresa_id'] = $user->empresa_id;
        }

        $validated['placa'] = mb_strtoupper(trim($validated['placa']));
        $validated['creado_por'] = $user->id;
        $validated['actualizado_por'] = $user->id;
        $validated['estado'] = 'registrada';

        Unidad::create($validated);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('unidades.administrar.ventana', ['consultar' => 1])
                ->with('success', 'Unidad creada correctamente.');
        }

        return redirect()
            ->route('unidades.index', ['consultar' => 1])
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

        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $unidad->loadMissing('empresa');

        return view('unidades.edit', [
            'unidad' => $unidad,
            'empresas' => collect([$unidad->empresa])->filter(),
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    public function editVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $unidad->loadMissing('empresa');

        return view('unidades.edit-ventana', [
            'unidad' => $unidad,
            'empresas' => collect([$unidad->empresa])->filter(),
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    public function update(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate($this->reglasValidacionUnidad($request, $unidad, $esUsuarioDieselCop));

        $validated['placa'] = mb_strtoupper(trim($validated['placa']));
        $validated['actualizado_por'] = $user->id;

        unset($validated['estado']);
        unset($validated['empresa_id']);

        $unidad->update($validated);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('unidades.show.ventana', $unidad)
                ->with('success', 'Unidad actualizada correctamente.');
        }

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function inactivar(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:150',
                Rule::in($this->motivosInactivacion()),
            ],
        ]);

        $unidad->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('unidades.show.ventana', $unidad)
                ->with('success', 'Unidad inactivada correctamente.');
        }

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Unidad inactivada correctamente.');
    }

    public function reactivar(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $unidad->update([
            'estado' => 'registrada',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('unidades.show.ventana', $unidad)
                ->with('success', 'Unidad reactivada correctamente. Queda en estado registrada para validación operativa.');
        }

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Unidad reactivada correctamente. Queda en estado registrada para validación operativa.');
    }

    private function reglasValidacionUnidad(Request $request, ?Unidad $unidad, bool $esUsuarioDieselCop): array
    {
        return [
            'empresa_id' => [
                is_null($unidad) && $esUsuarioDieselCop ? 'required' : 'nullable',
                'integer',
                Rule::exists('empresas', 'id')->where('estado', 'activa'),
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

        if (! is_null($user->empresa_id) && (int) $unidad->empresa_id !== (int) $user->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta unidad.');
        }
    }

    private function validarEmpresaActivaUnidad(Unidad $unidad): void
    {
        $unidad->loadMissing('empresa');

        if (! $unidad->empresa || $unidad->empresa->estado !== 'activa') {
            abort(403, 'No se puede operar sobre esta unidad porque la empresa está inactiva.');
        }
    }
}