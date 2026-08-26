<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

#[Fillable([
    'empresa_id',
    'rol_id',
    'tipo_usuario',
    'name',
    'apellido',
    'email',
    'telefono',
    'cargo',
    'estado',
    'password',
    'ultimo_acceso',
    'creado_por',
    'actualizado_por',
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
    'es_cuenta_recuperacion',
])]

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $usuario): void {
            $eraCuentaMaestra = $usuario->exists
                && (bool) $usuario->getRawOriginal(
                    'es_cuenta_recuperacion'
                );

            if (
                ($eraCuentaMaestra || $usuario->esCuentaMaestra())
                && ! $usuario->cumpleInvariantesCuentaMaestra()
            ) {
                throw new LogicException(
                    'La Cuenta Maestra debe permanecer activa, global y con el rol DIESEL_SUPER_ADMIN.'
                );
            }
        });

        static::deleting(function (User $usuario): void {
            if (
                $usuario->esCuentaMaestra()
                || (bool) $usuario->getRawOriginal(
                    'es_cuenta_recuperacion'
                )
            ) {
                throw new LogicException(
                    'La Cuenta Maestra no puede eliminarse.'
                );
            }
        });
    }

    public const TIPO_DIESEL_COP = 'diesel_cop';
    public const TIPO_EMPRESA = 'empresa';

    public const ROL_DIESEL_SUPER_ADMIN = 'DIESEL_SUPER_ADMIN';
    public const ROL_DIESEL_ADMIN = 'DIESEL_ADMIN';
    public const ROL_DIESEL_TECNICO = 'DIESEL_TECNICO';
    public const ROL_DIESEL_AUDITOR = 'DIESEL_AUDITOR';

    public const ROL_EMPRESA_ADMIN = 'EMPRESA_ADMIN';
    public const ROL_EMPRESA_SUPERVISOR = 'EMPRESA_SUPERVISOR';
    public const ROL_EMPRESA_OPERADOR = 'EMPRESA_OPERADOR';
    public const ROL_EMPRESA_AUDITOR = 'EMPRESA_AUDITOR';

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivado_por');
    }

    /**
     * @param string|array<int, string> $roles
     */
    public function tieneRol(string|array $roles): bool
    {
        $rolesPermitidos = is_array($roles)
            ? $roles
            : [$roles];

        return in_array(
            $this->role?->codigo,
            $rolesPermitidos,
            true
        );
    }

    public function esCuentaRecuperacion(): bool
    {
        return (bool) $this->es_cuenta_recuperacion;
    }

    /**
     * Nombre semántico de la identidad extraordinaria del sistema.
     *
     * El campo histórico sigue siendo la fuente de identidad para no
     * introducir acoplamientos runtime a un ID o correo concretos.
     */
    public function esCuentaMaestra(): bool
    {
        return $this->esCuentaRecuperacion();
    }

    public function cumpleInvariantesCuentaMaestra(): bool
    {
        return $this->esCuentaMaestra()
            && $this->tipo_usuario === self::TIPO_DIESEL_COP
            && is_null($this->empresa_id)
            && $this->estado === 'activo'
            && Role::query()
                ->whereKey($this->rol_id)
                ->where('codigo', self::ROL_DIESEL_SUPER_ADMIN)
                ->exists();
    }

    public function esDieselCop(): bool
    {
        return $this->tipo_usuario === self::TIPO_DIESEL_COP;
    }

    public function esUsuarioEmpresa(): bool
    {
        return $this->tipo_usuario === self::TIPO_EMPRESA;
    }

    public function esAuditor(): bool
    {
        return $this->tieneRol([
            self::ROL_DIESEL_AUDITOR,
            self::ROL_EMPRESA_AUDITOR,
        ]);
    }

    public function esDieselAuditor(): bool
    {
        return $this->tieneRol(self::ROL_DIESEL_AUDITOR);
    }

    public function esEmpresaAuditor(): bool
    {
        return $this->tieneRol(self::ROL_EMPRESA_AUDITOR);
    }

    public function puedeVerTodasLasEmpresas(): bool
    {
        return $this->esDieselCop();
    }

    public function empresaIdOperativa(): ?int
    {
        if (! $this->esUsuarioEmpresa()) {
            return null;
        }

        return is_null($this->empresa_id)
            ? null
            : (int) $this->empresa_id;
    }

    public function tieneContextoEmpresaValido(): bool
    {
        if ($this->esDieselCop()) {
            return is_null($this->empresa_id);
        }

        if ($this->esUsuarioEmpresa()) {
            return ! is_null($this->empresa_id);
        }

        return false;
    }

    /**
     * Determina si el usuario tiene un permiso activo.
     *
     * El superadministrador conserva acceso total.
     */
    public function tienePermiso(string $codigoPermiso): bool
    {
        if ($this->tieneRol(self::ROL_DIESEL_SUPER_ADMIN)) {
            return true;
        }

        if (
            ! $this->role
            || $this->role->estado !== 'activo'
        ) {
            return false;
        }

        return $this->role
            ->permisos()
            ->where('permisos.codigo', $codigoPermiso)
            ->where('permisos.estado', 'activo')
            ->exists();
    }

    /**
     * Determina si el usuario tiene al menos uno
     * de los permisos indicados.
     *
     * @param array<int, string> $codigosPermiso
     */
    public function tieneAlgunPermiso(
        array $codigosPermiso
    ): bool {
        foreach ($codigosPermiso as $codigoPermiso) {
            if ($this->tienePermiso($codigoPermiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si el usuario tiene todos
     * los permisos indicados.
     *
     * @param array<int, string> $codigosPermiso
     */
    public function tieneTodosLosPermisos(
        array $codigosPermiso
    ): bool {
        foreach ($codigosPermiso as $codigoPermiso) {
            if (! $this->tienePermiso($codigoPermiso)) {
                return false;
            }
        }

        return true;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'fecha_inactivacion' => 'datetime',
            'es_cuenta_recuperacion' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
