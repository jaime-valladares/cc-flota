<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PuntoRuta;
use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->prepararConsultaRutas($request, false);

        return view('rutas.index', $data);
    }

    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaRutas($request, false);

        return view('rutas.index-ventana', $data);
    }

    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaRutas($request, true);

        return view('rutas.administrar', $data);
    }

    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaRutas($request, true);

        return view('rutas.administrar-ventana', $data);
    }

    private function prepararConsultaRutas(Request $request, bool $soloEmpresasActivas): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'ruta_id' => ['nullable', 'integer', 'exists:rutas,id'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'ruta_id.exists' => 'La ruta seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $empresaId = $validated['empresa_id'] ?? null;
        $rutaId = $validated['ruta_id'] ?? null;
        $estado = $validated['estado'] ?? null;

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny(['empresa_id', 'ruta_id', 'estado', 'consultar']);

        $query = Ruta::query()
            ->with([
                'empresa',
                'puntoOrigen',
                'puntoDestino',
            ])
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        if ($hayFiltros) {
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            if ($rutaId) {
                $query->where('id', $rutaId);
            }

            if (in_array($estado, ['activo', 'inactivo'], true)) {
                $query->where('estado', $estado);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $rutas = $query
            ->orderBy('empresa_id')
            ->orderBy('ruta')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = Ruta::query()
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        if (! $esUsuarioDieselCop) {
            $baseResumen->where('empresa_id', $user->empresa_id);
        }

        $totalRutas = (clone $baseResumen)->count();
        $rutasActivas = (clone $baseResumen)->where('estado', 'activo')->count();
        $rutasInactivas = (clone $baseResumen)->where('estado', 'inactivo')->count();

        if ($esUsuarioDieselCop) {
            $empresasSelector = Empresa::query()
                ->when($soloEmpresasActivas, function ($query) {
                    $query->where('estado', 'activa');
                })
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get();
        } else {
            $empresasSelector = collect([$empresaUsuario])
                ->filter(function ($empresa) use ($soloEmpresasActivas) {
                    if (! $empresa) {
                        return false;
                    }

                    if ($soloEmpresasActivas && $empresa->estado !== 'activa') {
                        return false;
                    }

                    return true;
                })
                ->values();
        }

        $rutasSelector = Ruta::query()
            ->with('empresa')
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            })
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('empresa_id')
            ->orderBy('ruta')
            ->get();

        return [
            'rutas' => $rutas,
            'rutasSelector' => $rutasSelector,
            'empresasSelector' => $empresasSelector,
            'empresaId' => $empresaId,
            'rutaId' => $rutaId,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalRutas' => $totalRutas,
            'rutasActivas' => $rutasActivas,
            'rutasInactivas' => $rutasInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    public function create()
    {
        $data = $this->prepararFormularioRuta();

        return view('rutas.create', $data);
    }

    public function createVentana()
    {
        $data = $this->prepararFormularioRuta();

        return view('rutas.create-ventana', $data);
    }

    private function prepararFormularioRuta(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        if (! $esUsuarioDieselCop && (! $empresaUsuario || $empresaUsuario->estado !== 'activa')) {
            abort(403, 'No se puede operar sobre rutas porque la empresa está inactiva.');
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        $puntosRutaSelector = PuntoRuta::query()
            ->with('empresa')
            ->where('estado', 'activo')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->orderBy('empresa_id')
            ->orderBy('nombre')
            ->get();

        return [
            'empresasSelector' => $empresasSelector,
            'puntosRutaSelector' => $puntosRutaSelector,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $baseRules = [
            'punto_origen_id' => [
                'required',
                'integer',
                Rule::exists('puntos_ruta', 'id')->where('estado', 'activo'),
            ],
            'punto_destino_id' => [
                'required',
                'integer',
                Rule::exists('puntos_ruta', 'id')->where('estado', 'activo'),
                'different:punto_origen_id',
            ],
            'kilometros_estimados' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'galones_estimados' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
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
            'punto_origen_id.required' => 'Debe seleccionar el punto de origen.',
            'punto_origen_id.exists' => 'El punto de origen seleccionado no es válido o no está activo.',
            'punto_destino_id.required' => 'Debe seleccionar el punto de destino.',
            'punto_destino_id.exists' => 'El punto de destino seleccionado no es válido o no está activo.',
            'punto_destino_id.different' => 'El punto de origen y el punto de destino no pueden ser iguales.',
            'kilometros_estimados.required' => 'Debe ingresar los kilómetros estimados.',
            'kilometros_estimados.numeric' => 'Los kilómetros estimados deben ser un valor numérico.',
            'kilometros_estimados.gt' => 'Los kilómetros estimados deben ser mayores que cero.',
            'kilometros_estimados.max' => 'Los kilómetros estimados no deben exceder 99,999,999.99.',
            'galones_estimados.required' => 'Debe ingresar los galones estimados.',
            'galones_estimados.numeric' => 'Los galones estimados deben ser un valor numérico.',
            'galones_estimados.gt' => 'Los galones estimados deben ser mayores que cero.',
            'galones_estimados.max' => 'Los galones estimados no deben exceder 99,999,999.99.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $origen = PuntoRuta::with('empresa')
            ->where('id', $validated['punto_origen_id'])
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->first();

        $destino = PuntoRuta::with('empresa')
            ->where('id', $validated['punto_destino_id'])
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->first();

        if (! $origen || ! $destino) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_origen_id' => 'Los puntos seleccionados deben pertenecer a la empresa seleccionada y estar activos.',
                ]);
        }

        $this->validarEmpresaActivaPorId($empresaId);

        $rutaDuplicada = Ruta::query()
            ->where('empresa_id', $empresaId)
            ->where('punto_origen_id', $origen->id)
            ->where('punto_destino_id', $destino->id)
            ->exists();

        if ($rutaDuplicada) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_destino_id' => 'Ya existe una ruta con ese origen y destino para la empresa seleccionada.',
                ]);
        }

        $nombreRuta = $this->generarNombreRuta($origen, $destino);

        Ruta::create([
            'empresa_id' => $empresaId,
            'punto_origen_id' => $origen->id,
            'punto_destino_id' => $destino->id,
            'ruta' => $nombreRuta,
            'kilometros_estimados' => $validated['kilometros_estimados'],
            'galones_estimados' => $validated['galones_estimados'],
            'estado' => 'activo',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('rutas.create.ventana')
                ->with('success', 'Ruta guardada correctamente.');
        }

        return redirect()
            ->route('rutas.create')
            ->with('success', 'Ruta guardada correctamente.');
    }

    public function show(Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $ruta->load([
            'empresa',
            'puntoOrigen',
            'puntoDestino',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('rutas.show', compact('ruta'));
    }

    public function showVentana(Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $ruta->load([
            'empresa',
            'puntoOrigen',
            'puntoDestino',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('rutas.show-ventana', compact('ruta'));
    }

    public function edit(Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $data = $this->prepararFormularioRuta();
        $data['ruta'] = $ruta;

        return view('rutas.edit', $data);
    }

    public function editVentana(Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $data = $this->prepararFormularioRuta();
        $data['ruta'] = $ruta;

        return view('rutas.edit-ventana', $data);
    }

    public function update(Request $request, Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $validated = $request->validate([
            'punto_origen_id' => [
                'required',
                'integer',
                Rule::exists('puntos_ruta', 'id')->where('estado', 'activo'),
            ],
            'punto_destino_id' => [
                'required',
                'integer',
                Rule::exists('puntos_ruta', 'id')->where('estado', 'activo'),
                'different:punto_origen_id',
            ],
            'kilometros_estimados' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'galones_estimados' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ], [
            'punto_origen_id.required' => 'Debe seleccionar el punto de origen.',
            'punto_origen_id.exists' => 'El punto de origen seleccionado no es válido o no está activo.',
            'punto_destino_id.required' => 'Debe seleccionar el punto de destino.',
            'punto_destino_id.exists' => 'El punto de destino seleccionado no es válido o no está activo.',
            'punto_destino_id.different' => 'El punto de origen y el punto de destino no pueden ser iguales.',
            'kilometros_estimados.required' => 'Debe ingresar los kilómetros estimados.',
            'kilometros_estimados.numeric' => 'Los kilómetros estimados deben ser un valor numérico.',
            'kilometros_estimados.gt' => 'Los kilómetros estimados deben ser mayores que cero.',
            'kilometros_estimados.max' => 'Los kilómetros estimados no deben exceder 99,999,999.99.',
            'galones_estimados.required' => 'Debe ingresar los galones estimados.',
            'galones_estimados.numeric' => 'Los galones estimados deben ser un valor numérico.',
            'galones_estimados.gt' => 'Los galones estimados deben ser mayores que cero.',
            'galones_estimados.max' => 'Los galones estimados no deben exceder 99,999,999.99.',
        ]);

        $empresaId = (int) $ruta->empresa_id;

        $origen = PuntoRuta::query()
            ->where('id', $validated['punto_origen_id'])
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->first();

        $destino = PuntoRuta::query()
            ->where('id', $validated['punto_destino_id'])
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->first();

        if (! $origen || ! $destino) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_origen_id' => 'Los puntos seleccionados deben pertenecer a la empresa actual y estar activos.',
                ]);
        }

        $rutaDuplicada = Ruta::query()
            ->where('empresa_id', $empresaId)
            ->where('punto_origen_id', $origen->id)
            ->where('punto_destino_id', $destino->id)
            ->where('id', '!=', $ruta->id)
            ->exists();

        if ($rutaDuplicada) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_destino_id' => 'Ya existe una ruta con ese origen y destino para la empresa actual.',
                ]);
        }

        $ruta->update([
            'punto_origen_id' => $origen->id,
            'punto_destino_id' => $destino->id,
            'ruta' => $this->generarNombreRuta($origen, $destino),
            'kilometros_estimados' => $validated['kilometros_estimados'],
            'galones_estimados' => $validated['galones_estimados'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('rutas.show.ventana', $ruta)
                ->with('success', 'Ruta actualizada correctamente.');
        }

        return redirect()
            ->route('rutas.show', $ruta)
            ->with('success', 'Ruta actualizada correctamente.');
    }

    public function inactivar(Request $request, Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:No continúa en uso,Cambio operativo,Datos incorrectos en registro,Solicitud del cliente,Suspensión administrativa,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $ruta->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('rutas.show.ventana', $ruta)
                ->with('success', 'Ruta inactivada correctamente.');
        }

        return redirect()
            ->route('rutas.show', $ruta)
            ->with('success', 'Ruta inactivada correctamente.');
    }

    public function reactivar(Request $request, Ruta $ruta)
    {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        $ruta->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('rutas.show.ventana', $ruta)
                ->with('success', 'Ruta reactivada correctamente.');
        }

        return redirect()
            ->route('rutas.show', $ruta)
            ->with('success', 'Ruta reactivada correctamente.');
    }

    private function generarNombreRuta(PuntoRuta $origen, PuntoRuta $destino): string
    {
        return trim($origen->nombre) . ' - ' . trim($destino->nombre);
    }

    private function autorizarAccesoRuta(Ruta $ruta): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $ruta->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta ruta.');
        }
    }

    private function validarEmpresaActivaRuta(Ruta $ruta): void
    {
        $ruta->loadMissing('empresa');

        if (! $ruta->empresa || $ruta->empresa->estado !== 'activa') {
            abort(403, 'No se puede operar sobre esta ruta porque la empresa está inactiva.');
        }
    }

    private function validarEmpresaActivaPorId(int $empresaId): void
    {
        $empresaActiva = Empresa::query()
            ->where('id', $empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(403, 'No se puede operar sobre rutas porque la empresa está inactiva.');
        }
    }
}