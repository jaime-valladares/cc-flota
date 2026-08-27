<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FichaUnidadController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        $esDieselCop = $usuario->esDieselCop();

        $empresaId = $esDieselCop
            ? $request->integer('empresa_id')
            : (int) $usuario->empresa_id;

        if ($request->filled('unidad_id')) {
            $unidad = Unidad::query()->findOrFail(
                $request->integer('unidad_id')
            );

            $this->autorizarUnidad($usuario, $unidad);

            return redirect()->route(
                'reportes.unidades.show',
                $unidad
            );
        }

        if ($esDieselCop && $empresaId > 0) {
            abort_unless(Empresa::query()->whereKey($empresaId)->exists(), 404);
        }

        $empresas = $esDieselCop
            ? Empresa::query()
                ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
                ->get()
            : Empresa::query()->whereKey($usuario->empresa_id)->get();

        $unidades = collect();

        if ($empresaId > 0) {
            $unidades = Unidad::query()
                ->where('empresa_id', $empresaId)
                ->orderBy('placa')
                ->get();
        }

        return view('reportes.unidades.index', [
            'empresas' => $empresas,
            'unidades' => $unidades,
            'empresaId' => $empresaId > 0 ? $empresaId : null,
            'esDieselCop' => $esDieselCop,
        ]);
    }

    public function show(Unidad $unidad): View
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        $this->autorizarUnidad($usuario, $unidad);

        $unidad->load('empresa');

        return view('reportes.unidades.show', compact('unidad'));
    }

    private function autorizarUnidad(User $usuario, Unidad $unidad): void
    {
        if (! $usuario->esUsuarioEmpresa()) {
            return;
        }

        abort_unless(
            (int) $unidad->empresa_id === (int) $usuario->empresa_id,
            403,
            'No tiene autorización para consultar esta unidad.'
        );
    }
}
