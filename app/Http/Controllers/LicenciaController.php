<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Unidad;
use App\Support\PlantillasPuntosSeguridad;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenciaController extends Controller
{
    /**
     * Consulta informativa de licencias dentro del sistema.
     */
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaLicencias($request);

        return view('licencias.index', $data);
    }

    /**
     * Consulta informativa de licencias en ventana independiente.
     */
    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaLicencias($request);

        return view('licencias.index-ventana', $data);
    }

    /**
     * Búsqueda administrativa de licencias.
     */
    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaLicencias($request);

        return view('licencias.administrar', $data);
    }

    /**
     * Búsqueda administrativa de licencias en ventana independiente.
     */
    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaLicencias($request);

        return view('licencias.administrar-ventana', $data);
    }

    /**
     * Prepara filtros, catálogos y resultados para consulta/administración.
     */
    private function prepararConsultaLicencias(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaId = $request->input('empresa_id');
        $estado = $request->input('estado');
        $periodoVigencia = $request->input('periodo_vigencia_meses');
        $placa = $request->input('placa');

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = $request->boolean('consultar')
            || filled($empresaId)
            || filled($estado)
            || filled($periodoVigencia)
            || filled($placa);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseQuery = Licencia::query()
            ->with(['empresa', 'unidad'])
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            });

        $totalLicencias = (clone $baseQuery)->count();
        $totalActivas = (clone $baseQuery)->where('estado', 'activa')->count();
        $totalInactivas = (clone $baseQuery)->where('estado', 'inactiva')->count();

        $licencias = Licencia::query()
            ->with(['empresa', 'unidad'])
            ->join('unidades', 'licencias.unidad_id', '=', 'unidades.id')
            ->select('licencias.*')
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('licencias.empresa_id', $user->empresa_id);
            })
            ->when($hayFiltros && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('licencias.empresa_id', $empresaId);
            })
            ->when($hayFiltros && filled($estado), function ($query) use ($estado) {
                $query->where('licencias.estado', $estado);
            })
            ->when($hayFiltros && filled($periodoVigencia), function ($query) use ($periodoVigencia) {
                $query->where('licencias.periodo_vigencia_meses', $periodoVigencia);
            })
            ->when($hayFiltros && filled($placa), function ($query) use ($placa) {
                $query->where('unidades.placa', 'like', '%' . $placa . '%');
            })
            ->when(! $hayFiltros, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('unidades.placa')
            ->paginate(10)
            ->withQueryString();

        return [
            'licencias' => $licencias,
            'empresas' => $empresas,
            'empresaId' => $empresaId,
            'estado' => $estado,
            'periodoVigencia' => $periodoVigencia,
            'placa' => $placa,
            'hayFiltros' => $hayFiltros,
            'totalLicencias' => $totalLicencias,
            'totalActivas' => $totalActivas,
            'totalInactivas' => $totalInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'periodosVigencia' => $this->periodosVigencia(),
        ];
    }

    /**
     * Formulario de creación de licencia.
     */
    public function create(Request $request): View
    {
        $data = $this->prepararFormularioLicencia($request);

        return view('licencias.create', $data);
    }

    /**
     * Formulario de creación de licencia en ventana independiente.
     */
    public function createVentana(Request $request): View
    {
        $data = $this->prepararFormularioLicencia($request);

        return view('licencias.create-ventana', $data);
    }

    /**
     * Prepara catálogos para el formulario de licencia.
     */
    private function prepararFormularioLicencia(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaSeleccionadaId = $esUsuarioDieselCop
            ? $request->input('empresa_id')
            : $user->empresa_id;

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->where('estado', 'activa')
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $empresaSeleccionadaValida = filled($empresaSeleccionadaId)
            && $empresas->contains('id', (int) $empresaSeleccionadaId);

        if (! $empresaSeleccionadaValida) {
            $empresaSeleccionadaId = null;
        }

        $unidades = Unidad::query()
            ->with(['empresa', 'licencia'])
            ->where('estado', 'registrada')
            ->whereDoesntHave('licencia')
            ->when(filled($empresaSeleccionadaId), function ($query) use ($empresaSeleccionadaId) {
                $query->where('empresa_id', $empresaSeleccionadaId);
            })
            ->when(! filled($empresaSeleccionadaId), function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->orderBy('placa')
            ->get();

        return [
            'empresas' => $empresas,
            'unidades' => $unidades,
            'empresaSeleccionadaId' => $empresaSeleccionadaId,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'periodosVigencia' => $this->periodosVigencia(),
        ];
    }

    /**
     * Guarda una nueva licencia y genera los puntos de seguridad de la unidad.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate($this->reglasValidacionCrearLicencia($request, $esUsuarioDieselCop));

        $unidad = Unidad::query()
            ->with(['empresa', 'licencia', 'puntosSeguridad'])
            ->findOrFail($validated['unidad_id']);

        $this->autorizarAccesoUnidad($unidad);

        if ($unidad->licencia) {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'Esta unidad ya tiene una licencia registrada.',
                ]);
        }

        if ($unidad->estado !== 'registrada') {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'Solo se puede crear licencia para unidades registradas pendientes de configuración.',
                ]);
        }

        if (! $unidad->empresa || $unidad->empresa->estado !== 'activa') {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'La empresa asociada a la unidad debe estar activa.',
                ]);
        }

        if ($unidad->puntosSeguridad()->exists()) {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'Esta unidad ya tiene puntos de seguridad generados.',
                ]);
        }

        $fechaActivacion = Carbon::parse($validated['fecha_activacion']);
        $periodoVigencia = (int) $validated['periodo_vigencia_meses'];
        $plantilla = $this->plantillaDesdeTanquesProtegidos((int) $unidad->cantidad_tanques_con_licencia);

        DB::transaction(function () use ($unidad, $user, $fechaActivacion, $periodoVigencia, $plantilla): void {
            Licencia::create([
                'empresa_id' => $unidad->empresa_id,
                'unidad_id' => $unidad->id,
                'periodo_vigencia_meses' => $periodoVigencia,
                'fecha_activacion' => $fechaActivacion->toDateString(),
                'fecha_vencimiento' => $fechaActivacion->copy()->addMonthsNoOverflow($periodoVigencia)->toDateString(),
                'estado' => 'activa',
                'plantilla_puntos_seguridad' => $plantilla,
                'creado_por' => $user->id,
                'actualizado_por' => $user->id,
            ]);

            foreach (PlantillasPuntosSeguridad::porPlantilla($plantilla) as $punto) {
                PuntoSeguridadUnidad::create([
                    'unidad_id' => $unidad->id,
                    'orden' => $punto['orden_visual'] ?? $punto['orden'] ?? null,
                    'codigo_punto' => $punto['codigo_punto'] ?? null,
                    'grupo' => $punto['grupo'] ?? null,
                    'subgrupo' => $punto['subgrupo'] ?? null,
                    'nombre_punto' => $punto['nombre_punto'] ?? $punto['nombre'] ?? 'Punto sin nombre',
                    'descripcion' => null,
                    'posicion_tanque' => $punto['posicion_tanque'] ?? null,
                    'tipo_punto' => $punto['tipo_punto'] ?? null,
                    'requiere_marchamo' => (bool) ($punto['requiere_marchamo'] ?? true),
                    'plantilla_origen' => $plantilla,
                    'criterio_origen' => $punto['criterio_origen'] ?? null,
                    'estado_asignacion' => 'pendiente',
                    'marchamo_actual_id' => null,
                    'estado' => 'activo',
                    'creado_por' => $user->id,
                    'actualizado_por' => $user->id,
                ]);
            }
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('licencias.consulta.ventana', ['consultar' => 1])
                ->with('success', 'Licencia creada correctamente. Los puntos de seguridad fueron generados.');
        }

        return redirect()
            ->route('licencias.index', ['consultar' => 1])
            ->with('success', 'Licencia creada correctamente. Los puntos de seguridad fueron generados.');
    }

    /**
     * Ficha administrativa de la licencia.
     */
    public function show(Licencia $licencia): View
    {
        $this->autorizarAccesoLicencia($licencia);

        $licencia->load([
            'empresa',
            'unidad.puntosSeguridad.marchamoActual',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('licencias.show', compact('licencia'));
    }

    /**
    * Ficha administrativa de la licencia en ventana independiente.
    */
    public function showVentana(Licencia $licencia): View
    {
        $this->autorizarAccesoLicencia($licencia);

        $licencia->load([
            'empresa',
            'unidad.puntosSeguridad.marchamoActual',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('licencias.show-ventana', compact('licencia'));
    }

    /**
     * Formulario de edición de licencia.
     */
    public function edit(Licencia $licencia): View
    {
        $this->autorizarAccesoLicencia($licencia);

        $licencia->load(['empresa', 'unidad']);

        return view('licencias.edit', [
            'licencia' => $licencia,
            'periodosVigencia' => $this->periodosVigencia(),
        ]);
    }

    /**
    * Formulario de edición de licencia en ventana independiente.
    */
    public function editVentana(Licencia $licencia): View
    {
        $this->autorizarAccesoLicencia($licencia);

        $licencia->load(['empresa', 'unidad']);

        return view('licencias.edit-ventana', [
            'licencia' => $licencia,
            'periodosVigencia' => $this->periodosVigencia(),
        ]);
    }

    /**
     * Actualiza una licencia existente.
     */
    public function update(Request $request, Licencia $licencia): RedirectResponse
    {
        $this->autorizarAccesoLicencia($licencia);

        $validated = $request->validate($this->reglasValidacionActualizarLicencia());

        $fechaActivacion = Carbon::parse($validated['fecha_activacion']);
        $periodoVigencia = (int) $validated['periodo_vigencia_meses'];

        $licencia->update([
            'periodo_vigencia_meses' => $periodoVigencia,
            'fecha_activacion' => $fechaActivacion->toDateString(),
            'fecha_vencimiento' => $fechaActivacion->copy()->addMonthsNoOverflow($periodoVigencia)->toDateString(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('licencias.show.ventana', $licencia)
                ->with('success', 'Licencia actualizada correctamente.');
        }

        return redirect()
            ->route('licencias.show', $licencia)
            ->with('success', 'Licencia actualizada correctamente.');
        }

    /**
     * Inactiva una licencia sin eliminarla físicamente.
     */
    public function inactivar(Request $request, Licencia $licencia): RedirectResponse
    {
        $this->autorizarAccesoLicencia($licencia);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:150',
                Rule::in($this->motivosInactivacion()),
            ],
        ]);

        $licencia->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('licencias.show.ventana', $licencia)
                ->with('success', 'Licencia inactivada correctamente.');
        }

        return redirect()
            ->route('licencias.show', $licencia)
            ->with('success', 'Licencia inactivada correctamente.');
    }

    /**
     * Reactiva una licencia previamente inactivada.
     */
    public function reactivar(Request $request, Licencia $licencia): RedirectResponse
    {
        $this->autorizarAccesoLicencia($licencia);

        $validated = $request->validate([
            'periodo_vigencia_meses' => [
                'required',
                'integer',
                Rule::in(array_keys($this->periodosVigencia())),
            ],
            'fecha_activacion' => [
                'required',
                'date',
            ],
        ]);

        $fechaActivacion = Carbon::parse($validated['fecha_activacion']);
        $periodoVigencia = (int) $validated['periodo_vigencia_meses'];

        $licencia->update([
            'periodo_vigencia_meses' => $periodoVigencia,
            'fecha_activacion' => $fechaActivacion->toDateString(),
            'fecha_vencimiento' => $fechaActivacion->copy()->addMonthsNoOverflow($periodoVigencia)->toDateString(),
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('licencias.show', $licencia)
            ->with('success', 'Licencia reactivada correctamente.');
    }

    /**
     * Reglas de validación para crear licencia.
     */
    private function reglasValidacionCrearLicencia(Request $request, bool $esUsuarioDieselCop): array
    {
        $empresaId = $esUsuarioDieselCop
            ? $request->input('empresa_id')
            : Auth::user()->empresa_id;

        return [
            'empresa_id' => [
                $esUsuarioDieselCop ? 'required' : 'nullable',
                'integer',
                Rule::exists('empresas', 'id')->where('estado', 'activa'),
            ],
            'unidad_id' => [
                'required',
                'integer',
                Rule::exists('unidades', 'id')
                    ->where('empresa_id', $empresaId)
                    ->where('estado', 'registrada'),
                Rule::unique('licencias', 'unidad_id'),
            ],
            'periodo_vigencia_meses' => [
                'required',
                'integer',
                Rule::in(array_keys($this->periodosVigencia())),
            ],
            'fecha_activacion' => [
                'required',
                'date',
            ],
        ];
    }

    /**
     * Reglas de validación para actualizar licencia.
     */
    private function reglasValidacionActualizarLicencia(): array
    {
        return [
            'periodo_vigencia_meses' => [
                'required',
                'integer',
                Rule::in(array_keys($this->periodosVigencia())),
            ],
            'fecha_activacion' => [
                'required',
                'date',
            ],
        ];
    }

    /**
     * Catálogo fijo de períodos de vigencia.
     */
    private function periodosVigencia(): array
    {
        return [
            3 => '3 meses',
            6 => '6 meses',
            12 => '12 meses',
        ];
    }

    /**
     * Catálogo fijo de motivos de inactivación.
     */
    private function motivosInactivacion(): array
    {
        return [
            'Fin de cobertura',
            'Falta de pago',
            'Solicitud administrativa',
            'Cambio operativo',
            'Unidad fuera de servicio',
            'Empresa inactiva',
            'Corrección de registro',
            'Otro',
        ];
    }

    /**
     * Determina la plantilla de puntos de seguridad según tanques protegidos.
     */
    private function plantillaDesdeTanquesProtegidos(int $cantidadTanquesProtegidos): string
    {
        return match ($cantidadTanquesProtegidos) {
            1 => 'plantilla_1_tanque',
            2 => 'plantilla_2_tanques',
            3 => 'plantilla_3_tanques',
            default => abort(422, 'La cantidad de tanques protegidos debe ser 1, 2 o 3.'),
        };
    }

    /**
     * Control básico de acceso multiempresa para licencia.
     */
    private function autorizarAccesoLicencia(Licencia $licencia): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $licencia->empresa_id !== (int) $user->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta licencia.');
        }
    }

    /**
     * Control básico de acceso multiempresa para unidad.
     */
    private function autorizarAccesoUnidad(Unidad $unidad): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $unidad->empresa_id !== (int) $user->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta unidad.');
        }
    }
}