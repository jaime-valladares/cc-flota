<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Motorista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MotoristaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Consulta
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Administración
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Preparación de consultas
    |--------------------------------------------------------------------------
    */

    private function prepararConsultaMotoristas(
        Request $request,
        bool $soloEmpresasActivas
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            'motorista_ids' => ['nullable', 'array'],
            'motorista_ids.*' => [
                'nullable',
                'integer',
                'exists:motoristas,id',
            ],

            'estado_ids' => ['nullable', 'array'],
            'estado_ids.*' => [
                'nullable',
                'string',
                Rule::in(['activo', 'inactivo']),
            ],

            /*
             * Compatibilidad temporal con filtros anteriores.
             * Estos parámetros pueden eliminarse cuando todas las vistas
             * actualizadas hayan sido validadas.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            'motorista_id' => [
                'nullable',
                'integer',
                'exists:motoristas,id',
            ],

            'estado' => [
                'nullable',
                Rule::in(['activo', 'inactivo']),
            ],
        ], [
            'empresa_ids.array' => 'La selección de empresas no es válida.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',

            'motorista_ids.array' => 'La selección de motoristas no es válida.',
            'motorista_ids.*.exists' => 'Uno de los motoristas seleccionados no es válido.',

            'estado_ids.array' => 'La selección de estados no es válida.',
            'estado_ids.*.in' => 'Uno de los estados seleccionados no es válido.',

            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'motorista_id.exists' => 'El motorista seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

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

        $estadoIds = collect($validated['estado_ids'] ?? [])
            ->filter()
            ->map(fn ($estado) => (string) $estado);

        if (! empty($validated['estado'])) {
            $estadoIds->push((string) $validated['estado']);
        }

        $estadoIds = $estadoIds
            ->filter(fn ($estado) => in_array(
                $estado,
                ['activo', 'inactivo'],
                true
            ))
            ->unique()
            ->values()
            ->all();

        /*
         * Un usuario perteneciente a una empresa solo puede consultar
         * motoristas de su propia empresa.
         */
        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $consultaEjecutada = $request->boolean('consultar');

        /*
         * La empresa obligatoria limita el alcance del usuario empresarial,
         * pero no ejecuta automáticamente Consulta ni Administrar.
         */
        $hayFiltros = $consultaEjecutada
            || count($motoristaIds) > 0
            || count($estadoIds) > 0
            || (
                $esUsuarioDieselCop
                && count($empresaIds) > 0
            );

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $empresaUsuario,
            $soloEmpresasActivas
        );

        /*
         * El selector de motoristas depende de las empresas seleccionadas.
         */
        $motoristasSelector = Motorista::query()
            ->with('empresa')
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($soloEmpresasActivas, function ($query) {
                $query->whereHas('empresa', function ($empresaQuery) {
                    $empresaQuery->where('estado', 'activa');
                });
            })
            ->when(count($empresaIds) > 0, function ($query) use ($empresaIds) {
                $query->whereIn('empresa_id', $empresaIds);
            })
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
            if (count($empresaIds) > 0) {
                $query->whereIn('empresa_id', $empresaIds);
            }

            if (count($motoristaIds) > 0) {
                $query->whereIn('id', $motoristaIds);
            }

            if (count($estadoIds) > 0) {
                $query->whereIn('estado', $estadoIds);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $motoristas = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        /*
         * Los indicadores utilizan el mismo contexto de filtros.
         */
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
            if (count($empresaIds) > 0) {
                $baseResumen->whereIn('empresa_id', $empresaIds);
            }

            if (count($motoristaIds) > 0) {
                $baseResumen->whereIn('id', $motoristaIds);
            }

            if (count($estadoIds) > 0) {
                $baseResumen->whereIn('estado', $estadoIds);
            }
        }

        $totalMotoristas = (clone $baseResumen)->count();

        $motoristasActivos = (clone $baseResumen)
            ->where('estado', 'activo')
            ->count();

        $motoristasInactivos = (clone $baseResumen)
            ->where('estado', 'inactivo')
            ->count();

        return [
            'motoristas' => $motoristas,
            'empresasSelector' => $empresasSelector,
            'motoristasSelector' => $motoristasSelector,

            'empresaIds' => $empresaIds,
            'motoristaIds' => $motoristaIds,
            'estadoIds' => $estadoIds,

            /*
             * Compatibilidad temporal con vistas anteriores.
             */
            'empresaId' => $empresaIds[0] ?? null,
            'motoristaId' => $motoristaIds[0] ?? null,
            'estado' => $estadoIds[0] ?? null,

            'hayFiltros' => $hayFiltros,

            'totalMotoristas' => $totalMotoristas,
            'motoristasActivos' => $motoristasActivos,
            'motoristasInactivos' => $motoristasInactivos,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        ?Empresa $empresaUsuario,
        bool $soloEmpresasActivas
    ) {
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

                if (
                    $soloEmpresasActivas
                    && $empresa->estado !== 'activa'
                ) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Registro
    |--------------------------------------------------------------------------
    */

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

        if (
            ! $esUsuarioDieselCop
            && (
                ! $empresaUsuario
                || $empresaUsuario->estado !== 'activa'
            )
        ) {
            abort(
                403,
                'No se puede operar sobre motoristas porque la empresa está inactiva.'
            );
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])
                ->filter()
                ->values();

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

        $rules = [
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'licencia' => [
                'required',
                'string',
                'max:14',
                'regex:/^[0-9]+$/',
            ],

            'telefono' => [
                'required',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
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
            $rules['empresa_id'] = ['nullable'];
        }

        $validated = $request->validate($rules, [
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
            'telefono.max' => 'El teléfono no debe exceder 9 caracteres.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        /*
         * La licencia puede repetirse en empresas diferentes,
         * pero no dentro de la misma empresa.
         */
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
            'nombres' => trim($validated['nombres']),
            'apellidos' => trim($validated['apellidos']),
            'licencia' => $validated['licencia'],
            'telefono' => $validated['telefono'],
            'estado' => 'activo',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('motoristas.create.ventana')
                ->with(
                    'success',
                    'Motorista guardado correctamente.'
                );
        }

        return redirect()
            ->route('motoristas.create')
            ->with(
                'success',
                'Motorista guardado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Ficha administrativa
    |--------------------------------------------------------------------------
    */

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

        return view(
            'motoristas.show',
            compact('motorista')
        );
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

        return view(
            'motoristas.show-ventana',
            compact('motorista')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edición
    |--------------------------------------------------------------------------
    */

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

    public function update(
        Request $request,
        Motorista $motorista
    ) {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);
        $this->validarMotoristaActivoParaOperacion($motorista);

        /*
         * La empresa y la licencia no forman parte de la actualización.
         * Ambas quedan fijas después del registro.
         */
        $validated = $request->validate([
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'telefono' => [
                'required',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ], [
            'nombres.required' => 'Debe ingresar los nombres del motorista.',
            'nombres.max' => 'Los nombres no deben exceder 100 caracteres.',

            'apellidos.required' => 'Debe ingresar los apellidos del motorista.',
            'apellidos.max' => 'Los apellidos no deben exceder 100 caracteres.',

            'telefono.required' => 'Debe ingresar el teléfono del motorista.',
            'telefono.max' => 'El teléfono no debe exceder 9 caracteres.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
        ]);

        $motorista->update([
            'nombres' => trim($validated['nombres']),
            'apellidos' => trim($validated['apellidos']),
            'telefono' => $validated['telefono'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'motoristas.show.ventana',
                    array_merge(
                        $queryParams,
                        ['motorista' => $motorista]
                    )
                )
                ->with(
                    'success',
                    'Motorista actualizado correctamente.'
                );
        }

        return redirect()
            ->route(
                'motoristas.show',
                array_merge(
                    $queryParams,
                    ['motorista' => $motorista]
                )
            )
            ->with(
                'success',
                'Motorista actualizado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Inactivación y reactivación
    |--------------------------------------------------------------------------
    */

    public function inactivar(
        Request $request,
        Motorista $motorista
    ) {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);
        $this->validarMotoristaActivoParaOperacion($motorista);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                Rule::in([
                    'No continúa en servicio',
                    'Cambio operativo',
                    'Licencia vencida',
                    'Datos incorrectos en registro',
                    'Solicitud del cliente',
                    'Suspensión administrativa',
                    'Otro',
                ]),
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
                ->route(
                    'motoristas.show.ventana',
                    array_merge(
                        $queryParams,
                        ['motorista' => $motorista]
                    )
                )
                ->with(
                    'success',
                    'Motorista inactivado correctamente.'
                );
        }

        return redirect()
            ->route(
                'motoristas.show',
                array_merge(
                    $queryParams,
                    ['motorista' => $motorista]
                )
            )
            ->with(
                'success',
                'Motorista inactivado correctamente.'
            );
    }

    public function reactivar(
        Request $request,
        Motorista $motorista
    ) {
        $this->autorizarAccesoMotorista($motorista);
        $this->validarEmpresaActivaMotorista($motorista);

        if ($motorista->estado !== 'inactivo') {
            abort(
                403,
                'El motorista ya se encuentra activo.'
            );
        }

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
                ->route(
                    'motoristas.show.ventana',
                    array_merge(
                        $queryParams,
                        ['motorista' => $motorista]
                    )
                )
                ->with(
                    'success',
                    'Motorista reactivado correctamente.'
                );
        }

        return redirect()
            ->route(
                'motoristas.show',
                array_merge(
                    $queryParams,
                    ['motorista' => $motorista]
                )
            )
            ->with(
                'success',
                'Motorista reactivado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones de acceso y operación
    |--------------------------------------------------------------------------
    */

    private function autorizarAccesoMotorista(
        Motorista $motorista
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id !== (int) $motorista->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a este motorista.'
            );
        }
    }

    private function validarEmpresaActivaMotorista(
        Motorista $motorista
    ): void {
        $motorista->loadMissing('empresa');

        if (
            ! $motorista->empresa
            || $motorista->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre este motorista porque la empresa está inactiva.'
            );
        }
    }

    private function validarMotoristaActivoParaOperacion(
        Motorista $motorista
    ): void {
        if ($motorista->estado !== 'activo') {
            abort(
                403,
                'No se puede modificar este motorista porque está inactivo. Debe reactivarlo desde la ficha antes de realizar cambios.'
            );
        }
    }

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
                'No se puede operar sobre motoristas porque la empresa está inactiva.'
            );
        }
    }
}