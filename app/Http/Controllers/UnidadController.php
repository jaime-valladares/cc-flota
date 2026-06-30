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
        $totalRegistradas = (clone $baseQuery)->where('estado', 'registrada')->count();
        $totalActivas = (clone $baseQuery)->where('estado', 'activa')->count();
        $totalInactivas = (clone $baseQuery)->where('estado', 'inactiva')->count();

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
            'totalRegistradas' => $totalRegistradas,
            'totalActivas' => $totalActivas,
            'totalInactivas' => $totalInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
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

    /**
     * Formulario de creación de unidad en ventana independiente.
     */
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

        /*
         * La unidad nace como registrada.
         * Solo pasará a activa cuando tenga licencia, puntos de seguridad
         * y asignación inicial de marchamos completa.
         */
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

    /**
     * Ficha administrativa de la unidad.
     */
    public function show(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);

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

    /**
     * Ficha administrativa de la unidad en ventana independiente.
     */
    public function showVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);

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
            ->where('estado', 'activa')
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        return view('unidades.edit', [
            'unidad' => $unidad,
            'empresas' => $empresas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
        ]);
    }

    /**
     * Formulario de edición de unidad en ventana independiente.
     */
    public function editVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);

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

        return view('unidades.edit-ventana', [
            'unidad' => $unidad,
            'empresas' => $empresas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'modelosMedicion' => $this->modelosMedicion(),
            'estadosUnidad' => $this->estadosUnidad(),
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

        /*
         * El estado no se actualiza desde el formulario general de edición.
         * Los cambios de estado pasan por inactivar/reactivar o por finalizar
         * asignación inicial de marchamos.
         */
        unset($validated['estado']);

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

    /**
     * Reactiva una unidad previamente inactiva.
     *
     * La unidad vuelve a registrada, no directamente a activa,
     * porque puede requerir validación de licencia, puntos y marchamos.
     */
    public function reactivar(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

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

    /**
     * Reglas de validación compartidas por creación y edición.
     */
    private function reglasValidacionUnidad(Request $request, ?Unidad $unidad, bool $esUsuarioDieselCop): array
    {
        return [
            'empresa_id' => [
                $esUsuarioDieselCop ? 'required' : 'nullable',
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
     * Catálogo fijo de estados de unidad.
     */
    private function estadosUnidad(): array
    {
        return [
            'registrada' => 'Registrada',
            'activa' => 'Activa',
            'inactiva' => 'Inactiva',
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