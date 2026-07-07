<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PuntoRuta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PuntoRutaController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->prepararConsultaPuntosRuta($request);

        return view('puntos-ruta.index', $data);
    }

    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaPuntosRuta($request);

        return view('puntos-ruta.index-ventana', $data);
    }

    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaPuntosRuta($request);

        return view('puntos-ruta.administrar', $data);
    }

    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaPuntosRuta($request);

        return view('puntos-ruta.administrar-ventana', $data);
    }

    private function prepararConsultaPuntosRuta(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'nombre' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $empresaId = $validated['empresa_id'] ?? null;
        $nombre = trim((string) ($validated['nombre'] ?? ''));
        $estado = $validated['estado'] ?? null;

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny(['empresa_id', 'nombre', 'estado', 'consultar']);

        $query = PuntoRuta::query()
            ->with('empresa');

        if ($hayFiltros) {
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            if ($nombre !== '') {
                $query->where('nombre', 'like', '%' . $nombre . '%');
            }

            if (in_array($estado, ['activo', 'inactivo'], true)) {
                $query->where('estado', $estado);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $puntosRuta = $query
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = PuntoRuta::query();

        if (! $esUsuarioDieselCop) {
            $baseResumen->where('empresa_id', $user->empresa_id);
        }

        $totalPuntosRuta = (clone $baseResumen)->count();
        $puntosRutaActivos = (clone $baseResumen)->where('estado', 'activo')->count();
        $puntosRutaInactivos = (clone $baseResumen)->where('estado', 'inactivo')->count();

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        return [
            'puntosRuta' => $puntosRuta,
            'empresasSelector' => $empresasSelector,
            'empresaId' => $empresaId,
            'nombre' => $nombre,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalPuntosRuta' => $totalPuntosRuta,
            'puntosRutaActivos' => $puntosRutaActivos,
            'puntosRutaInactivos' => $puntosRutaInactivos,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    public function create()
    {
        $data = $this->prepararFormularioPuntoRuta();

        return view('puntos-ruta.create', $data);
    }

    public function createVentana()
    {
        $data = $this->prepararFormularioPuntoRuta();

        return view('puntos-ruta.create-ventana', $data);
    }

    private function prepararFormularioPuntoRuta(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

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

    public function store(Request $request)
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $baseRules = [
            'nombre' => ['required', 'string', 'max:150'],
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
            'nombre.required' => 'Debe ingresar el nombre del punto de ruta.',
            'nombre.max' => 'El nombre del punto de ruta no debe exceder 150 caracteres.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique('puntos_ruta', 'nombre')
                    ->where('empresa_id', $empresaId),
            ],
        ], [
            'nombre.unique' => 'Ya existe un punto de ruta con ese nombre para la empresa seleccionada.',
        ]);

        PuntoRuta::create([
            'empresa_id' => $empresaId,
            'nombre' => $validated['nombre'],
            'estado' => 'activo',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('puntos-ruta.create.ventana')
                ->with('success', 'Punto de ruta guardado correctamente.');
        }

        return redirect()
            ->route('puntos-ruta.create')
            ->with('success', 'Punto de ruta guardado correctamente.');
    }

    public function show(PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

        $puntoRuta->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('puntos-ruta.show', compact('puntoRuta'));
    }

    public function showVentana(PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

        $puntoRuta->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('puntos-ruta.show-ventana', compact('puntoRuta'));
    }

    public function edit(PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

        $data = $this->prepararFormularioPuntoRuta();
        $data['puntoRuta'] = $puntoRuta;

        return view('puntos-ruta.edit', $data);
    }

    public function editVentana(PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

        $data = $this->prepararFormularioPuntoRuta();
        $data['puntoRuta'] = $puntoRuta;

        return view('puntos-ruta.edit-ventana', $data);
    }

    public function update(Request $request, PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
        ], [
            'nombre.required' => 'Debe ingresar el nombre del punto de ruta.',
            'nombre.max' => 'El nombre del punto de ruta no debe exceder 150 caracteres.',
        ]);

        $empresaId = (int) $puntoRuta->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique('puntos_ruta', 'nombre')
                    ->where('empresa_id', $empresaId)
                    ->ignore($puntoRuta->id),
            ],
        ], [
            'nombre.unique' => 'Ya existe un punto de ruta con ese nombre para la empresa actual.',
        ]);

        $puntoRuta->update([
            'nombre' => $validated['nombre'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('puntos-ruta.show.ventana', $puntoRuta)
                ->with('success', 'Punto de ruta actualizado correctamente.');
        }

        return redirect()
            ->route('puntos-ruta.show', $puntoRuta)
            ->with('success', 'Punto de ruta actualizado correctamente.');
    }

    public function inactivar(Request $request, PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

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

        $puntoRuta->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('puntos-ruta.show.ventana', $puntoRuta)
                ->with('success', 'Punto de ruta inactivado correctamente.');
        }

        return redirect()
            ->route('puntos-ruta.show', $puntoRuta)
            ->with('success', 'Punto de ruta inactivado correctamente.');
    }

    public function reactivar(Request $request, PuntoRuta $puntoRuta)
    {
        $this->autorizarAccesoPuntoRuta($puntoRuta);

        $puntoRuta->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('puntos-ruta.show.ventana', $puntoRuta)
                ->with('success', 'Punto de ruta reactivado correctamente.');
        }

        return redirect()
            ->route('puntos-ruta.show', $puntoRuta)
            ->with('success', 'Punto de ruta reactivado correctamente.');
    }

    private function autorizarAccesoPuntoRuta(PuntoRuta $puntoRuta): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $puntoRuta->empresa_id) {
            abort(403, 'No tiene autorización para acceder a este punto de ruta.');
        }
    }
}