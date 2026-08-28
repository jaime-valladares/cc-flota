<x-app-layout>
<div class="cc-page-wrapper cc-va-scope">
<div class="cc-content-container cc-operational-container">
@php
    $queryParams = request()->query();
@endphp

<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact">
        <div>
            <h3 class="cc-title cc-title-compact">
                Editar usuario
            </h3>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.show', array_merge($queryParams, ['usuario' => $usuario])) }}" class="cc-btn-secondary cc-btn-wide">
                Volver a ficha
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="cc-alert cc-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('usuarios.update', array_merge($queryParams, ['usuario' => $usuario])) }}" novalidate>
        @csrf
            @method('PUT')
        

        @include('usuarios._form', [
            'usuario' => $usuario,
            'empresas' => $empresas,
            'roles' => $roles,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'puedeCambiarRol' => $puedeCambiarRol,
            'submitLabel' => 'Actualizar usuario',
            'modoVentana' => false,
        ])
    </form>
</div>

</div>
</div>
</x-app-layout>