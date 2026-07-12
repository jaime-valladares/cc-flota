<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Motorista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MotoristaController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->prepararConsultaMotoristas($request, false);

        return view('motoristas.index', $data);
    }

    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaMotoristas($request, false);

        return view('motoristas.index-ventana', $data);
    }

    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaMotoristas($request, true);

        return view('motoristas.administrar', $data);
    }

    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaMotoristas($request, true);

        return view('motoristas.administrar-ventana', $data);
    }

    private function prepararConsultaMotoristas(Request $request, bool $soloEmpresasActivas): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'busqueda_empresa' => ['nullable', 'string', 'max:150'],
            'busqueda_motorista' => ['nullable', 'string', 'max:150'],

            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['nullable', 'integer', 'exists:empresas,id'],

            'motorista_ids' => ['nullable', 'array'],
            'motorista_ids.*' => ['nullable', 'integer', 'exists:motoristas,id'],

            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],

            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ], [
            'busqueda_empresa.max' => 'La búsqueda de empresa no debe exceder 150 caracteres.',
            'busqueda_motorista.max' => 'La búsqueda de motorista no debe exceder 150 caracteres.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',
            'motorista_ids.*.exists' => 'Uno de los motoristas seleccionados no es válido.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'motorista_id.exists' => 'El motorista seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $busquedaEmpresa = trim((string) ($validated['busqueda_empresa'] ?? ''));
        $busquedaMotorista = trim((string) ($validated['busqueda_motorista'] ?? ($validated['buscar'] ?? '')));
        $estado = $validated['estado'] ?? null;

        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (! empty($validated['empresa_id'])) {
            $empresaIds->push((int) $validated['empresa_id']);
        }

        $empresaIds = $empresaIds
            ->unique()
            ->values()
            ->all();

        $motoristaIds = collect($validated['motorista_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (! empty($validated['motorista_id'])) {
            $motoristaIds->push((int) $validated['motorista_id']);
        }

        $motoristaIds = $motoristaIds
            ->unique()
            ->values()
            ->all();

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $empresaId = $empresaIds[0] ?? null;
        $motoristaId = $motoristaIds[0] ?? null;

        $hayFiltros = $request->boolean('consultar')
            || $busquedaEmpresa !== ''
            || $busquedaMotorista !== ''
            || count($empresaIds) > 0
            || count($motoristaIds) > 0
            || in_array($estado, ['activo', 'inactivo'], true);

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $empresaUsuario,
            $soloEmpresasActivas
        );

        $baseMotoristasSelectorQuery = Motorista::query()
            ->with('empresa')
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            })
            ->when($busquedaEmpresa !== '', function ($query) use ($busquedaEmpresa) {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            })
            ->when(count($empresaIds) > 0, function ($query) use ($empresaIds) {
                $query->whereIn('empresa_id', $empresaIds);
            });

        $motoristasSelector = (clone $baseMotoristasSelectorQuery)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $query = Motorista::query()
            ->with('empresa')
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        if ($hayFiltros) {
            if ($busquedaEmpresa !== '') {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            }

            if (count($empresaIds) > 0) {
                $query->whereIn('empresa_id', $empresaIds);
            }

            if ($busquedaMotorista !== '') {
                $query->where(function ($subquery) use ($busquedaMotorista) {
                    $subquery->where('nombres', 'like', '%' . $busquedaMotorista . '%')
                        ->orWhere('apellidos', 'like', '%' . $busquedaMotorista . '%')
                        ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ['%' . $busquedaMotorista . '%'])
                        ->orWhereRaw("CONCAT(apellidos, ' ', nombres) LIKE ?", ['%' . $busquedaMotorista . '%']);
                });
            }

            if (count($motoristaIds) > 0) {
                $query->whereIn('id', $motoristaIds);
            }

            if (in_array($estado, ['activo', 'inactivo'], true)) {
                $query->where('estado', $estado);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $motoristas = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = Motorista::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            });

        if ($hayFiltros) {
            if ($busquedaEmpresa !== '') {
                $baseResumen->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            }

            if (count($empresaIds) > 0) {
                $baseResumen->whereIn('empresa_id', $empresaIds);
            }

            if ($busquedaMotorista !== '') {
                $baseResumen->where(function ($subquery) use ($busquedaMotorista) {
                    $subquery->where('nombres', 'like', '%' . $busquedaMotorista . '%')
                        ->orWhere('apellidos', 'like', '%' . $busquedaMotorista . '%')
                        ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ['%' . $busquedaMotorista . '%'])
                        ->orWhereRaw("CONCAT(apellidos, ' ', nombres) LIKE ?", ['%' . $busquedaMotorista . '%']);
                });
            }

            if (count($motoristaIds) > 0) {
                $baseResumen->whereIn('id', $motoristaIds);
            }

            if (in_array($estado, ['activo', 'inactivo'], true)) {
                $baseResumen->where('estado', $estado);
            }
        }

        $totalMotoristas = (clone $baseResumen)->count();
        $motoristasActivos = (clone $baseResumen)->where('estado', 'activo')->count();
        $motoristasInactivos = (clone $baseResumen)->where('estado', 'inactivo')->count();

        return [
            'motoristas' => $motoristas,
            'empresasSelector' => $empresasSelector,
            'motoristasSelector' => $motoristasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaMotorista' => $busquedaMotorista,
            'buscar' => $busquedaMotorista,

            'empresaIds' => $empresaIds,
            'motoristaIds' => $motoristaIds,

            'empresaId' => $empresaId,
            'motoristaId' => $motoristaId,

            'estado' => $estado,
            'hayFiltros' => $hayFiltros,

            'totalMotoristas' => $totalMotoristas,
            'motoristasActivos' => $motoristasActivos,
            'motoristasInactivos' => $motoristasInactivos,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    private function obtenerEmpresasSelector(bool $esUsuarioDieselCop, ?Empresa $empresaUsuario, bool $soloEmpresasActivas)
    {
        if ($esUsuarioDieselCop) {
            return Empresa::query()
                ->when($soloEmpresasActivas, function ($query) {
                    $query->where('estado', 'activa');
                })
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get();
        }

        return collect([$empresaUsuario])
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

    public function create()
    {
        $data = $this->prepararFormularioMotorista();

        return view('motoristas.create', $data);
    }

    public function createVentana()
    {
        $data = $this->prepararFormularioMotorista();

        return view('motoristas.create-ventana', $data);
    }

    private function prepararFormularioMotorista(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        if (! $esUsuarioDieselCop && (! $empresaUsuario || $empresaUsuario->estado !== 'activa')) {
            abort(403, 'No se puede operar sobre motoristas porque la empresa está inactiva.');
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter()->values();

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
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'licencia' => ['required', 'string', 'max:14', 'regex:/^[0-9]+$/'],
            'telefono' => ['required', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
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
            'nombres.required' => 'Debe ingresar los nombres del motorista.',
            'nombres.max' => 'Los nombres no deben exceder 100 caracteres.',
            'apellidos.required' => 'Debe ingresar los apellidos del motorista.',
            'apellidos.max' => 'Los apellidos no deben exceder 100 caracteres.',
            'licencia.required' => 'Debe ingresar la licencia del motorista.',
            'licencia.max' => 'La licencia no debe exceder 14 dígitos.',
            'licencia.regex' => 'La licencia debe contener solo números, sin guiones.',
            'telefono.required' => 'Debe ingresar el teléfono del motorista.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        $request->validate([
            'licencia' => [
                Rule::unique('motoristas', 'licencia')
                    ->where('empresa_id', $empresaId),
            ],
        ], [
            'licencia.unique' => 'Ya existe un motorista con esa licencia para la empresa seleccionada.',
        ]);

        Motorista::create([
            'empresa_id' => $empresaId,
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'licencia' => $validated['licencia'],
            'telefono' => $validated['telefono'],
            'estado' => 'activo',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('motoristas.create.ventana')
                ->with('success', 'Motorista guardado correctamente.');
        }

        return redirect()
            ->route('motoristas.create')
            ->with('success', 'Motorista guardado correctamente.');
    }

    public function show(Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);

        $motorista->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('motoristas.show', compact('motorista'));
    }

    public function showVentana(Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);

        $motorista->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('motoristas.show-ventana', compact('motorista'));
    }

    public function edit(Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);
        $this->validarMotoristaActivoParaOperacion($motorista);

        $data = $this->prepararFormularioMotorista();
        $data['motorista'] = $motorista;

        return view('motoristas.edit', $data);
    }

    public function editVentana(Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);
        $this->validarMotoristaActivoParaOperacion($motorista);

        $data = $this->prepararFormularioMotorista();
        $data['motorista'] = $motorista;

        return view('motoristas.edit-ventana', $data);
    }

    public function update(Request $request, Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);
        $this->validarMotoristaActivoParaOperacion($motorista);

        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'licencia' => ['required', 'string', 'max:14', 'regex:/^[0-9]+$/'],
            'telefono' => ['required', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ], [
            'nombres.required' => 'Debe ingresar los nombres del motorista.',
            'nombres.max' => 'Los nombres no deben exceder 100 caracteres.',
            'apellidos.required' => 'Debe ingresar los apellidos del motorista.',
            'apellidos.max' => 'Los apellidos no deben exceder 100 caracteres.',
            'licencia.required' => 'Debe ingresar la licencia del motorista.',
            'licencia.max' => 'La licencia no debe exceder 14 dígitos.',
            'licencia.regex' => 'La licencia debe contener solo números, sin guiones.',
            'telefono.required' => 'Debe ingresar el teléfono del motorista.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
        ]);

        $empresaId = (int) $motorista->empresa_id;

        $request->validate([
            'licencia' => [
                Rule::unique('motoristas', 'licencia')
                    ->where('empresa_id', $empresaId)
                    ->ignore($motorista->id),
            ],
        ], [
            'licencia.unique' => 'Ya existe un motorista con esa licencia para la empresa actual.',
        ]);

        $motorista->update([
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'licencia' => $validated['licencia'],
            'telefono' => $validated['telefono'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('motoristas.show.ventana', array_merge($queryParams, ['motorista' => $motorista]))
                ->with('success', 'Motorista actualizado correctamente.');
        }

        return redirect()
            ->route('motoristas.show', array_merge($queryParams, ['motorista' => $motorista]))
            ->with('success', 'Motorista actualizado correctamente.');
    }

    public function inactivar(Request $request, Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);
        $this->validarMotoristaActivoParaOperacion($motorista);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:No continúa en servicio,Cambio operativo,Licencia vencida,Datos incorrectos en registro,Solicitud del cliente,Suspensión administrativa,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $motorista->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('motoristas.show.ventana', array_merge($queryParams, ['motorista' => $motorista]))
                ->with('success', 'Motorista inactivado correctamente.');
        }

        return redirect()
            ->route('motoristas.show', array_merge($queryParams, ['motorista' => $motorista]))
            ->with('success', 'Motorista inactivado correctamente.');
    }

    public function reactivar(Request $request, Motorista $motorista)
    {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);

        $motorista->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('motoristas.show.ventana', array_merge($queryParams, ['motorista' => $motorista]))
                ->with('success', 'Motorista reactivado correctamente.');
        }

        return redirect()
            ->route('motoristas.show', array_merge($queryParams, ['motorista' => $motorista]))
            ->with('success', 'Motorista reactivado correctamente.');
    }

    private function autorizarAccesoMotorista(Motorista $motorista): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $motorista->empresa_id) {
            abort(403, 'No tiene autorización para acceder a este motorista.');
        }
    }

    private function validarEmpresaActivaMotorista(Motorista $motorista): void
    {
        $motorista->loadMissing('empresa');

        if (! $motorista->empresa || $motorista->empresa->estado !== 'activa') {
            abort(403, 'No se puede operar sobre este motorista porque la empresa está inactiva.');
        }
    }

    private function validarMotoristaActivoParaOperacion(Motorista $motorista): void
    {
        if ($motorista->estado !== 'activo') {
            abort(403, 'No se puede modificar este motorista porque está inactivo. Debe reactivarlo desde la ficha antes de realizar cambios.');
        }
    }

    private function validarEmpresaActivaPorId(int $empresaId): void
    {
        $empresaActiva = Empresa::query()
            ->where('id', $empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(403, 'No se puede operar sobre motoristas porque la empresa está inactiva.');
        }
    }
}