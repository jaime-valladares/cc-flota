<x-app-layout>
<div class="cc-page-wrapper">
<div class="cc-content-container" style="max-width: 80rem;">
@php
    $queryParams = request()->query();
@endphp

<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact">
        <div>
            <h3 class="cc-title cc-title-compact">
                Registro de usuario
            </h3>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.create.ventana', $queryParams) }}" target="_blank" rel="noopener noreferrer" class="cc-btn-secondary cc-btn-wide">
                Abrir en nueva pestaña
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="cc-alert cc-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('usuarios.store', $queryParams) }}" novalidate>
        @csrf
        

        @include('usuarios._form', [
            'usuario' => null,
            'empresas' => $empresas,
            'roles' => $roles,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'puedeCambiarRol' => $puedeCambiarRol,
            'submitLabel' => 'Guardar usuario',
            'modoVentana' => false,
        ])
    </form>
</div>

</div>
</div>
</x-app-layout>