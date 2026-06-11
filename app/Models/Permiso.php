<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permiso extends Model
{
    protected $table = 'permisos';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'modulo',
        'accion',
        'nombre',
        'descripcion',
        'alcance',
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

    public function rolPermisos(): HasMany
    {
        return $this->hasMany(RolPermiso::class, 'permiso_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'rol_permisos',
            'permiso_id',
            'rol_id'
        );
    }
}