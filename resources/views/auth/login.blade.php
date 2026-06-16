<x-guest-layout>
    <section class="cc-login-stage">

        <div class="cc-login-orbit" aria-hidden="true">
            <span class="cc-login-orbit-ring cc-login-orbit-ring-one"></span>
            <span class="cc-login-orbit-ring cc-login-orbit-ring-two"></span>
            <span class="cc-login-orbit-dot cc-login-orbit-dot-one"></span>
            <span class="cc-login-orbit-dot cc-login-orbit-dot-two"></span>
            <span class="cc-login-orbit-dot cc-login-orbit-dot-three"></span>
        </div>

        <div class="cc-login-grid">

            <section class="cc-login-brand-panel cc-login-brand-panel-minimal">
                <div class="cc-login-brand-mark">
                    CC
                </div>

                <div class="cc-login-brand-content">
                    <div class="cc-login-kicker">
                        Diesel Cop Operations
                    </div>

                    <h1 class="cc-login-title">
                        CC-Flota
                    </h1>

                    <p class="cc-login-lead">
                        Control operativo, trazabilidad y administración segura para flotas protegidas.
                    </p>
                </div>

                <div class="cc-login-minimal-footer">
                    <span>Control</span>
                    <span>Trazabilidad</span>
                    <span>Operación</span>
                </div>
            </section>

            <section class="cc-login-card" aria-label="Formulario de inicio de sesión">
                <div class="cc-login-card-header">
                    <div>
                        <div class="cc-login-card-kicker">
                            Acceso seguro
                        </div>

                        <h2>
                            Iniciar sesión
                        </h2>

                        <p>
                            Ingrese sus credenciales para acceder a la consola administrativa.
                        </p>
                    </div>
                </div>

                <x-auth-session-status class="cc-login-status" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="cc-login-form">
                    @csrf

                    <div class="cc-login-field">
                        <label for="email">
                            Correo electrónico
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="cc-login-input"
                            placeholder="usuario@empresa.com"
                        >

                        <x-input-error :messages="$errors->get('email')" class="cc-login-error" />
                    </div>

                    <div class="cc-login-field">
                        <label for="password">
                            Contraseña
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="cc-login-input"
                            placeholder="Ingrese su contraseña"
                        >

                        <x-input-error :messages="$errors->get('password')" class="cc-login-error" />
                    </div>

                    <div class="cc-login-options">
                        <label for="remember_me" class="cc-login-checkbox-label">
                            <input
                                id="remember_me"
                                type="checkbox"
                                name="remember"
                                class="cc-login-checkbox"
                            >

                            <span>Recordarme</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="cc-login-link">
                                ¿Olvidó su contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="cc-login-button">
                        Acceder a CC-Flota
                    </button>
                </form>

                <div class="cc-login-footnote">
                    Acceso restringido a usuarios autorizados.
                </div>
            </section>

        </div>
    </section>
</x-guest-layout>