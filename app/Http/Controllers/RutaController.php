<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PuntoRuta;
use App\Models\Ruta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RutaController extends Controller
{
    public function index(Request $request): View
    {
        return view(
            'rutas.index',
            $this->prepararConsultaRutas($request, false)
        );
    }

    public function consultaVentana(Request $request): View
    {
        return view(
            'rutas.index-ventana',
            $this->prepararConsultaRutas($request, false)
        );
    }

    public function administrar(Request $request): View
    {
        return view(
            'rutas.administrar',
            $this->prepararConsultaRutas($request, true)
        );
    }

    public function administrarVentana(Request $request): View
    {
        return view(
            'rutas.administrar-ventana',
            $this->prepararConsultaRutas($request, true)
        );
    }

    private function prepararConsultaRutas(
        Request $request,
        bool $soloEmpresasActivas
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate(
            [
                'empresa_ids' => ['nullable', 'array'],
                'empresa_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:empresas,id',
                ],
                'ruta_ids' => ['nullable', 'array'],
                'ruta_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:rutas,id',
                ],

                // Compatibilidad temporal con filtros anteriores.
                'empresa_id' => [
                    'nullable',
                    'integer',
                    'exists:empresas,id',
                ],
                'ruta_id' => [
                    'nullable',
                    'integer',
                    'exists:rutas,id',
                ],

                'estado' => [
                    'nullable',
                    Rule::in(['activo', 'inactivo']),
                ],
            ],
            [
                'empresa_ids.array' =>
                    'La selección de empresas no es válida.',
                'empresa_ids.*.integer' =>
                    'Una de las empresas seleccionadas no es válida.',
                'empresa_ids.*.distinct' =>
                    'No debe seleccionar la misma empresa más de una vez.',
                'empresa_ids.*.exists' =>
                    'Una de las empresas seleccionadas no existe.',

                'ruta_ids.array' =>
                    'La selección de rutas no es válida.',
                'ruta_ids.*.integer' =>
                    'Una de las rutas seleccionadas no es válida.',
                'ruta_ids.*.distinct' =>
                    'No debe seleccionar la misma ruta más de una vez.',
                'ruta_ids.*.exists' =>
                    'Una de las rutas seleccionadas no existe.',

                'empresa_id.exists' =>
                    'La empresa seleccionada no es válida.',
                'ruta_id.exists' =>
                    'La ruta seleccionada no es válida.',
                'estado.in' =>
                    'El estado seleccionado no es válido.',
            ]
        );

        $empresaIds = $this->normalizarIdsSeleccionados(
            $validated['empresa_ids'] ?? [],
            $validated['empresa_id'] ?? null
        );

        $rutaIds = $this->normalizarIdsSeleccionados(
            $validated['ruta_ids'] ?? [],
            $validated['ruta_id'] ?? null
        );

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $estado = $validated['estado'] ?? null;

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny([
                'empresa_ids',
                'empresa_id',
                'ruta_ids',
                'ruta_id',
                'estado',
                'consultar',
            ]);

        $query = Ruta::query()
            ->with([
                'empresa',
                'puntoOrigen',
                'puntoDestino',
            ]);

        if ($soloEmpresasActivas) {
            $query->whereHas(
                'empresa',
                fn (Builder $empresaQuery) =>
                    $empresaQuery->where('estado', 'activa')
            );
        }

        if ($hayFiltros) {
            if (! empty($empresaIds)) {
                $query->whereIn('empresa_id', $empresaIds);
            }

            if (! empty($rutaIds)) {
                $query->whereIn('id', $rutaIds);
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

        $baseResumen = Ruta::query();

        if ($soloEmpresasActivas) {
            $baseResumen->whereHas(
                'empresa',
                fn (Builder $empresaQuery) =>
                    $empresaQuery->where('estado', 'activa')
            );
        }

        if (! $esUsuarioDieselCop) {
            $baseResumen->where(
                'empresa_id',
                $user->empresa_id
            );
        }

        if (! empty($empresaIds)) {
            $baseResumen->whereIn(
                'empresa_id',
                $empresaIds
            );
        }

        $totalRutas = (clone $baseResumen)->count();

        $rutasActivas = (clone $baseResumen)
            ->where('estado', 'activo')
            ->count();

        $rutasInactivas = (clone $baseResumen)
            ->where('estado', 'inactivo')
            ->count();

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $empresaUsuario,
            $soloEmpresasActivas
        );

        $rutasSelector = Ruta::query()
            ->with('empresa')
            ->when(
                $soloEmpresasActivas,
                fn (Builder $query) =>
                    $query->whereHas(
                        'empresa',
                        fn (Builder $empresaQuery) =>
                            $empresaQuery->where('estado', 'activa')
                    )
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) =>
                    $query->where(
                        'empresa_id',
                        $user->empresa_id
                    )
            )
            ->when(
                ! empty($empresaIds),
                fn (Builder $query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
            )
            ->orderBy('empresa_id')
            ->orderBy('ruta')
            ->get();

        $rutaIds = $this->obtenerRutasSeleccionadasValidas(
            $rutaIds,
            $rutasSelector
        );

        return [
            'rutas' => $rutas,
            'rutasSelector' => $rutasSelector,
            'empresasSelector' => $empresasSelector,
            'empresaIds' => $empresaIds,
            'rutaIds' => $rutaIds,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalRutas' => $totalRutas,
            'rutasActivas' => $rutasActivas,
            'rutasInactivas' => $rutasInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    public function create(): View
    {
        return view(
            'rutas.create',
            $this->prepararFormularioRuta()
        );
    }

    public function createVentana(): View
    {
        return view(
            'rutas.create-ventana',
            $this->prepararFormularioRuta()
        );
    }

    private function prepararFormularioRuta(
        ?Ruta $ruta = null
    ): array {
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
                'No se puede operar sobre rutas porque la empresa está inactiva.'
            );
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter()->values();

        $puntosRutaSelector = PuntoRuta::query()
            ->with('empresa')
            ->where('estado', 'activo')
            ->whereHas(
                'empresa',
                fn (Builder $query) =>
                    $query->where('estado', 'activa')
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) =>
                    $query->where(
                        'empresa_id',
                        $user->empresa_id
                    )
            )
            ->when(
                $ruta,
                fn (Builder $query) =>
                    $query->where(
                        'empresa_id',
                        $ruta->empresa_id
                    )
            )
            ->orderBy('empresa_id')
            ->orderBy('nombre')
            ->get();

        return [
            'empresasSelector' => $empresasSelector,
            'puntosRutaSelector' => $puntosRutaSelector,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
            'ruta' => $ruta,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $rules = $this->reglasFormularioRuta();

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

        $validated = $request->validate(
            $rules,
            $this->mensajesFormularioRuta()
        );

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        [$origen, $destino] = $this->obtenerPuntosValidos(
            $empresaId,
            (int) $validated['punto_origen_id'],
            (int) $validated['punto_destino_id']
        );

        if (! $origen || ! $destino) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_origen_id' =>
                        'Los puntos seleccionados deben pertenecer a la empresa seleccionada y estar activos.',
                ]);
        }

        if (
            $this->existeCombinacionRuta(
                $empresaId,
                $origen->id,
                $destino->id
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_destino_id' =>
                        'Ya existe una ruta entre los puntos seleccionados, sin importar su dirección.',
                ]);
        }

        Ruta::create([
            'empresa_id' => $empresaId,
            'punto_origen_id' => $origen->id,
            'punto_destino_id' => $destino->id,
            'ruta' => $this->generarNombreRuta(
                $origen,
                $destino
            ),
            'kilometros_estimados' =>
                $validated['kilometros_estimados'],
            'galones_estimados' =>
                $validated['galones_estimados'],
            'estado' => 'activo',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        $routeName = $request->input('return_to') === 'ventana'
            ? 'rutas.create.ventana'
            : 'rutas.create';

        return redirect()
            ->route(
                $routeName,
                $this->obtenerParametrosRetorno($request)
            )
            ->with(
                'success',
                'Ruta guardada correctamente.'
            );
    }

    public function show(
        Request $request,
        Ruta $ruta
    ): View {
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

        return view(
            'rutas.show',
            [
                'ruta' => $ruta,
                'parametrosRetorno' =>
                    $this->obtenerParametrosRetorno($request),
            ]
        );
    }

    public function showVentana(
        Request $request,
        Ruta $ruta
    ): View {
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

        return view(
            'rutas.show-ventana',
            [
                'ruta' => $ruta,
                'parametrosRetorno' =>
                    $this->obtenerParametrosRetorno($request),
            ]
        );
    }

    public function edit(
        Request $request,
        Ruta $ruta
    ): View {
        $this->autorizarAccesoRuta($ruta);
        $this->validarRutaEditable($ruta);

        $data = $this->prepararFormularioRuta($ruta);

        $data['parametrosRetorno'] =
            $this->obtenerParametrosRetorno($request);

        return view('rutas.edit', $data);
    }

    public function editVentana(
        Request $request,
        Ruta $ruta
    ): View {
        $this->autorizarAccesoRuta($ruta);
        $this->validarRutaEditable($ruta);

        $data = $this->prepararFormularioRuta($ruta);

        $data['parametrosRetorno'] =
            $this->obtenerParametrosRetorno($request);

        return view('rutas.edit-ventana', $data);
    }

    public function update(
        Request $request,
        Ruta $ruta
    ): RedirectResponse {
        $this->autorizarAccesoRuta($ruta);
        $this->validarRutaEditable($ruta);

        $validated = $request->validate(
            $this->reglasFormularioRuta(),
            $this->mensajesFormularioRuta()
        );

        $empresaId = (int) $ruta->empresa_id;

        [$origen, $destino] = $this->obtenerPuntosValidos(
            $empresaId,
            (int) $validated['punto_origen_id'],
            (int) $validated['punto_destino_id']
        );

        if (! $origen || ! $destino) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_origen_id' =>
                        'Los puntos seleccionados deben pertenecer a la empresa actual y estar activos.',
                ]);
        }

        if (
            $this->existeCombinacionRuta(
                $empresaId,
                $origen->id,
                $destino->id,
                $ruta->id
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'punto_destino_id' =>
                        'Ya existe otra ruta entre los puntos seleccionados, sin importar su dirección.',
                ]);
        }

        $ruta->update([
            'punto_origen_id' => $origen->id,
            'punto_destino_id' => $destino->id,
            'ruta' => $this->generarNombreRuta(
                $origen,
                $destino
            ),
            'kilometros_estimados' =>
                $validated['kilometros_estimados'],
            'galones_estimados' =>
                $validated['galones_estimados'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $ruta,
            'Ruta actualizada correctamente.'
        );
    }

    public function inactivar(
        Request $request,
        Ruta $ruta
    ): RedirectResponse {
        $this->autorizarAccesoRuta($ruta);
        $this->validarRutaEditable($ruta);

        $validated = $request->validate(
            [
                'motivo_inactivacion' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::in([
                        'No continúa en uso',
                        'Cambio operativo',
                        'Datos incorrectos en registro',
                        'Solicitud del cliente',
                        'Suspensión administrativa',
                        'Otro',
                    ]),
                ],
            ],
            [
                'motivo_inactivacion.required' =>
                    'Debe seleccionar el motivo de inactivación.',
                'motivo_inactivacion.in' =>
                    'El motivo de inactivación seleccionado no es válido.',
                'motivo_inactivacion.max' =>
                    'El motivo de inactivación no debe exceder 255 caracteres.',
            ]
        );

        $ruta->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' =>
                $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $ruta,
            'Ruta inactivada correctamente.'
        );
    }

    public function reactivar(
        Request $request,
        Ruta $ruta
    ): RedirectResponse {
        $this->autorizarAccesoRuta($ruta);
        $this->validarEmpresaActivaRuta($ruta);

        if ($ruta->estado !== 'inactivo') {
            return $this->redirigirAFicha(
                $request,
                $ruta,
                'La ruta ya se encuentra activa.'
            );
        }

        $ruta->loadMissing([
            'puntoOrigen',
            'puntoDestino',
        ]);

        if (
            ! $ruta->puntoOrigen
            || $ruta->puntoOrigen->estado !== 'activo'
        ) {
            return back()->withErrors([
                'reactivacion' =>
                    'No se puede reactivar la ruta porque el punto de origen está inactivo o no existe.',
            ]);
        }

        if (
            ! $ruta->puntoDestino
            || $ruta->puntoDestino->estado !== 'activo'
        ) {
            return back()->withErrors([
                'reactivacion' =>
                    'No se puede reactivar la ruta porque el punto de destino está inactivo o no existe.',
            ]);
        }

        if (
            (int) $ruta->puntoOrigen->empresa_id
                !== (int) $ruta->empresa_id
            || (int) $ruta->puntoDestino->empresa_id
                !== (int) $ruta->empresa_id
        ) {
            return back()->withErrors([
                'reactivacion' =>
                    'No se puede reactivar la ruta porque sus puntos no pertenecen a la empresa de la ruta.',
            ]);
        }

        $ruta->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $ruta,
            'Ruta reactivada correctamente.'
        );
    }

    private function reglasFormularioRuta(): array
    {
        return [
            'punto_origen_id' => [
                'required',
                'integer',
                Rule::exists('puntos_ruta', 'id')
                    ->where('estado', 'activo'),
            ],
            'punto_destino_id' => [
                'required',
                'integer',
                Rule::exists('puntos_ruta', 'id')
                    ->where('estado', 'activo'),
                'different:punto_origen_id',
            ],
            'kilometros_estimados' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.9',
                'regex:/^\d{1,8}(\.\d)?$/',
            ],
            'galones_estimados' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.9',
                'regex:/^\d{1,8}(\.\d)?$/',
            ],
        ];
    }

    private function mensajesFormularioRuta(): array
    {
        return [
            'empresa_id.required' =>
                'Debe seleccionar una empresa.',
            'empresa_id.exists' =>
                'La empresa seleccionada no es válida o no está activa.',

            'punto_origen_id.required' =>
                'Debe seleccionar el punto de origen.',
            'punto_origen_id.exists' =>
                'El punto de origen seleccionado no es válido o no está activo.',

            'punto_destino_id.required' =>
                'Debe seleccionar el punto de destino.',
            'punto_destino_id.exists' =>
                'El punto de destino seleccionado no es válido o no está activo.',
            'punto_destino_id.different' =>
                'El punto de origen y el punto de destino no pueden ser iguales.',

            'kilometros_estimados.required' =>
                'Debe ingresar los kilómetros estimados.',
            'kilometros_estimados.numeric' =>
                'Los kilómetros estimados deben ser un valor numérico.',
            'kilometros_estimados.gt' =>
                'Los kilómetros estimados deben ser mayores que cero.',
            'kilometros_estimados.max' =>
                'Los kilómetros estimados no deben exceder 99,999,999.9.',
            'kilometros_estimados.regex' =>
                'Los kilómetros estimados deben contener como máximo un decimal.',

            'galones_estimados.required' =>
                'Debe ingresar los galones estimados.',
            'galones_estimados.numeric' =>
                'Los galones estimados deben ser un valor numérico.',
            'galones_estimados.gt' =>
                'Los galones estimados deben ser mayores que cero.',
            'galones_estimados.max' =>
                'Los galones estimados no deben exceder 99,999,999.9.',
            'galones_estimados.regex' =>
                'Los galones estimados deben contener como máximo un decimal.',
        ];
    }

    private function obtenerPuntosValidos(
        int $empresaId,
        int $origenId,
        int $destinoId
    ): array {
        $puntos = PuntoRuta::query()
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->whereIn('id', [
                $origenId,
                $destinoId,
            ])
            ->get()
            ->keyBy('id');

        return [
            $puntos->get($origenId),
            $puntos->get($destinoId),
        ];
    }

    private function existeCombinacionRuta(
        int $empresaId,
        int $origenId,
        int $destinoId,
        ?int $rutaExcluirId = null
    ): bool {
        return Ruta::query()
            ->where('empresa_id', $empresaId)
            ->when(
                $rutaExcluirId,
                fn (Builder $query) =>
                    $query->where(
                        'id',
                        '!=',
                        $rutaExcluirId
                    )
            )
            ->where(function (Builder $query) use (
                $origenId,
                $destinoId
            ) {
                $query
                    ->where(function (Builder $directa) use (
                        $origenId,
                        $destinoId
                    ) {
                        $directa
                            ->where(
                                'punto_origen_id',
                                $origenId
                            )
                            ->where(
                                'punto_destino_id',
                                $destinoId
                            );
                    })
                    ->orWhere(function (Builder $inversa) use (
                        $origenId,
                        $destinoId
                    ) {
                        $inversa
                            ->where(
                                'punto_origen_id',
                                $destinoId
                            )
                            ->where(
                                'punto_destino_id',
                                $origenId
                            );
                    });
            })
            ->exists();
    }

    private function generarNombreRuta(
        PuntoRuta $origen,
        PuntoRuta $destino
    ): string {
        return trim($origen->nombre)
            . ' - '
            . trim($destino->nombre);
    }

    private function normalizarIdsSeleccionados(
        array $ids,
        mixed $idIndividual = null
    ): array {
        if (
            empty($ids)
            && ! is_null($idIndividual)
            && $idIndividual !== ''
        ) {
            $ids = [$idIndividual];
        }

        return collect($ids)
            ->filter(
                fn ($id) =>
                    filter_var(
                        $id,
                        FILTER_VALIDATE_INT
                    ) !== false
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function obtenerRutasSeleccionadasValidas(
        array $rutaIds,
        Collection $rutasSelector
    ): array {
        $idsPermitidos = $rutasSelector
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return collect($rutaIds)
            ->filter(
                fn ($id) =>
                    in_array(
                        (int) $id,
                        $idsPermitidos,
                        true
                    )
            )
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        ?Empresa $empresaUsuario,
        bool $soloEmpresasActivas
    ): Collection {
        if ($esUsuarioDieselCop) {
            return Empresa::query()
                ->when(
                    $soloEmpresasActivas,
                    fn (Builder $query) =>
                        $query->where(
                            'estado',
                            'activa'
                        )
                )
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get();
        }

        return collect([$empresaUsuario])
            ->filter(function (?Empresa $empresa) use (
                $soloEmpresasActivas
            ) {
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

    private function autorizarAccesoRuta(
        Ruta $ruta
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $ruta->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta ruta.'
            );
        }
    }

    private function validarEmpresaActivaRuta(
        Ruta $ruta
    ): void {
        $ruta->loadMissing('empresa');

        if (
            ! $ruta->empresa
            || $ruta->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre esta ruta porque la empresa está inactiva.'
            );
        }
    }

    private function validarRutaEditable(
        Ruta $ruta
    ): void {
        $this->validarEmpresaActivaRuta($ruta);

        if ($ruta->estado !== 'activo') {
            abort(
                403,
                'La ruta está inactiva y no puede editarse ni operarse. Debe reactivarse desde su ficha.'
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
                'No se puede operar sobre rutas porque la empresa está inactiva.'
            );
        }
    }

    private function obtenerParametrosRetorno(
        Request $request
    ): array {
        $permitidos = [
            'empresa_ids',
            'ruta_ids',
            'estado',
            'consultar',
            'page',
        ];

        $parametros = collect($request->query())
            ->only($permitidos)
            ->all();

        $returnQuery = $request->input('return_query');

        if (is_string($returnQuery) && $returnQuery !== '') {
            parse_str(
                $returnQuery,
                $parametrosDecodificados
            );

            $parametros = collect($parametrosDecodificados)
                ->only($permitidos)
                ->all();
        }

        return $parametros;
    }

    private function redirigirAFicha(
        Request $request,
        Ruta $ruta,
        string $mensaje
    ): RedirectResponse {
        $routeName =
            $request->input('return_to') === 'ventana'
                ? 'rutas.show.ventana'
                : 'rutas.show';

        return redirect()
            ->route(
                $routeName,
                array_merge(
                    ['ruta' => $ruta],
                    $this->obtenerParametrosRetorno($request)
                )
            )
            ->with('success', $mensaje);
    }
}