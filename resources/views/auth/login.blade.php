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
                <div class="cc-login-brand-mark cc-login-brand-mark-image">
                    <img
                        src="{{ asset('images/cc-flota/favicon.png') }}"
                        alt="CC-Flota"
                        class="cc-login-brand-mark-img"
                    >
                </div>

                <div class="cc-login-brand-content">
                    <div class="cc-login-kicker">
                        Diesel Cop - Sistema de Operaciones
                    </div>

                    <h1 class="cc-login-title">
                        CC-Flota
                    </h1>

                    <p class="cc-login-lead">
                        Control operativo, trazabilidad y administración segura para flotas protegidas por Diesel Cop.
                    </p>
                </div>

                <div class="cc-login-minimal-footer">
                    <span>Control</span>
                    <span>.</span>
                    <span>Trazabilidad</span>
                    <span>.</span>
                    <span>Seguridad</span>
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

                        <div
                            x-data="{ passwordVisible: false }"
                            style="position: relative;"
                        >
                            <input
                                id="password"
                                :type="passwordVisible ? 'text' : 'password'"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="cc-login-input"
                                style="padding-right: 3rem;"
                                placeholder="Ingrese su contraseña"
                            >

                            <button
                                type="button"
                                @click="passwordVisible = ! passwordVisible"
                                :aria-label="passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                :aria-pressed="passwordVisible.toString()"
                                style="position: absolute; right: 0.85rem; top: 50%; display: inline-flex; transform: translateY(-50%); align-items: center; justify-content: center; color: #64748b;"
                            >
                                <svg
                                    x-show="! passwordVisible"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>

                                <svg
                                    x-show="passwordVisible"
                                    x-cloak
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m2 2 20 20" />
                                    <path d="M6.71 6.71A10.7 10.7 0 0 0 2.06 11.65a1 1 0 0 0 0 .7A10.75 10.75 0 0 0 17.29 17.29" />
                                    <path d="M10.73 5.08A10.8 10.8 0 0 1 12 5c4.6 0 8.4 2.9 9.94 6.65a1 1 0 0 1 0 .7 10.7 10.7 0 0 1-1.38 2.42" />
                                    <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88" />
                                </svg>
                            </button>
                        </div>

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
