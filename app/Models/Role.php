<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'alcance',
        'descripcion',
        'estado',
        'fecha_creacion',
        'creado_por',
        'fecha_actualizacion',
        'actualizado_por',
        'fecha_inactivacion',
        'inactivado_por',
        'motivo_inactivacion',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_inactivacion' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function rolPermisos(): HasMany
    {
        return $this->hasMany(RolPermiso::class, 'rol_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(
            Permiso::class,
            'rol_permisos',
            'rol_id',
            'permiso_id'
        );
    }
}