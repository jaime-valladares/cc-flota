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
    /**
     * Consulta informativa de unidades dentro del sistema.
     */
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.index', $data);
    }

    /**
     * Consulta informativa de unidades en ventana independiente.
     */
    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.index-ventana', $data);
    }

    /**
     * Búsqueda administrativa de unidades.
     */
    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.administrar', $data);
    }

    /**
     * Búsqueda administrativa de unidades en ventana independiente.
     */
    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.administrar-ventana', $data);
    }

    /**
     * Prepara filtros, catálogos y resultados para consulta/administración.
     */
    private function prepararConsultaUnidades(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaId = $request->input('empresa_id');
        $estado = $request->input('estado');
        $modeloMedicion = $request->input('modelo_medicion');
        $placa = $request->input('placa');

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = $request->boolean('consultar')
            || filled($empresaId)
            || filled($estado)
            || filled($modeloMedicion)
            || filled($placa);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseQuery = Unidad::query()
            ->with(['empresa'])
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            });

        $totalUnidades = (clone $baseQuery)->count();
        $totalActivas = (clone $baseQuery)->where('estado', 'activo')->count();
        $totalInactivas = (clone $baseQuery)->where('estado', 'inactivo')->count();

        $unidades = Unidad::query()
            ->with(['empresa'])
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($hayFiltros && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($hayFiltros && filled($estado), function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when($hayFiltros && filled($modeloMedicion), function ($query) use ($modeloMedicion) {
                $query->where('modelo_medicion', $modeloMedicion);
            })
            ->when($hayFiltros && filled($placa), function ($query) use ($placa) {
                $query->where('placa', 'like', '%' . $placa . '%');
            })
            ->when(! $hayFiltros, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('placa')
            ->paginate(10)
            ->withQueryString();

        return [
            'unidades' => $unidades,
            'empresas' => $empresas,
            'empresaId' => $empresaId,
            'estado' => $estado,
            'modeloMedicion' => $modeloMedicion,
            'placa' => $placa,
            'hayFiltros' => $hayFiltros,
            'totalUnidades' => $totalUnidades,
            'totalActivas' => $totalActivas,
            'totalInactivas' => $totalInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
        ];
    }

    /**
     * Formulario de creación de unidad.
     */
    public function create(): View
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        return view('unidades.create', [
            'empresas' => $empresas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
        ]);
    }

    /**
     * Guarda una nueva unidad.
     */
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
        $validated['estado'] = 'activo';

        Unidad::create($validated);

        return redirect()
            ->route('unidades.index', ['consultar' => 1])
            ->with('success', 'Unidad creada correctamente.');
    }

    /**
     * Ficha administrativa de la unidad.
     */
    public function show(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load(['empresa', 'creadoPor', 'actualizadoPor', 'inactivadoPor']);

        return view('unidades.show', compact('unidad'));
    }

    /**
     * Formulario de edición de unidad.
     */
    public function edit(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);

        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        return view('unidades.edit', [
            'unidad' => $unidad,
            'empresas' => $empresas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
        ]);
    }

    /**
     * Actualiza una unidad existente.
     */
    public function update(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate($this->reglasValidacionUnidad($request, $unidad, $esUsuarioDieselCop));

        if (! $esUsuarioDieselCop) {
            $validated['empresa_id'] = $user->empresa_id;
        }

        $validated['placa'] = mb_strtoupper(trim($validated['placa']));
        $validated['actualizado_por'] = $user->id;

        $unidad->update($validated);

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Unidad actualizada correctamente.');
    }

    /**
     * Inactiva una unidad sin eliminarla físicamente.
     */
    public function inactivar(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:150',
                Rule::in($this->motivosInactivacion()),
            ],
        ]);

        $unidad->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Unidad inactivada correctamente.');
    }

    /**
     * Reactiva una unidad previamente inactivada.
     */
    public function reactivar(Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Unidad reactivada correctamente.');
    }

    /**
     * Reglas de validación compartidas por creación y edición.
     */
    private function reglasValidacionUnidad(Request $request, ?Unidad $unidad, bool $esUsuarioDieselCop): array
    {
        $empresaId = $esUsuarioDieselCop
            ? $request->input('empresa_id')
            : Auth::user()->empresa_id;

        return [
            'empresa_id' => [
                $esUsuarioDieselCop ? 'required' : 'nullable',
                'integer',
                Rule::exists('empresas', 'id'),
            ],
            'placa' => [
                'required',
                'string',
                'max:30',
                Rule::unique('unidades', 'placa')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
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
                'max:10',
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

    /**
     * Catálogo fijo de modelos de medición.
     */
    private function modelosMedicion(): array
    {
        return [
            'galones_hora' => 'Galones por hora',
            'galones_kilometro' => 'Galones por kilómetro',
            'galones_viaje' => 'Galones por viaje',
        ];
    }

    /**
     * Catálogo fijo de motivos de inactivación.
     */
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

    /**
     * Control básico de acceso multiempresa.
     */
    private function autorizarAccesoUnidad(Unidad $unidad): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $unidad->empresa_id !== (int) $user->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta unidad.');
        }
    }
}