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
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Empresa asociada al usuario.
     *
     * Los usuarios de empresa tendrán empresa_id.
     * Los usuarios Diesel Cop pueden tener empresa_id NULL.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Rol asignado al usuario.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    /**
     * Usuario que inactivó este registro, si aplica.
     */
    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivado_por');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_inactivacion' => 'datetime',
            'password' => 'hashed',
        ];
    }
}