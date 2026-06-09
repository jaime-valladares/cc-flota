<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolPermiso extends Model
{
    protected $table = 'rol_permisos';

    public $timestamps = false;

    protected $fillable = [
        'rol_id',
        'permiso_id',
        'fecha_creacion',
        'creado_por',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'permiso_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}