<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cuentas fundacionales y de recuperación
    |--------------------------------------------------------------------------
    |
    | Las contraseñas nunca deben quedar escritas en el repositorio. Solo se
    | utilizan al crear una cuenta que todavía no existe. Los seeders no
    | modifican cuentas existentes.
    |
    */

    'initial_admin' => [
        'email' => env(
            'CC_FLOTA_INITIAL_ADMIN_EMAIL',
            'admin@cc-flota.local'
        ),
        'password' => env('CC_FLOTA_INITIAL_ADMIN_PASSWORD'),
    ],

    'recovery_admin' => [
        'email' => env(
            'CC_FLOTA_RECOVERY_ADMIN_EMAIL',
            'jaime.ricardo.valladares@gmail.com'
        ),
        'password' => env('CC_FLOTA_RECOVERY_ADMIN_PASSWORD'),
        'name' => env('CC_FLOTA_RECOVERY_ADMIN_NAME', 'Jaime'),
        'apellido' => env(
            'CC_FLOTA_RECOVERY_ADMIN_LAST_NAME',
            'Valladares'
        ),
    ],
];