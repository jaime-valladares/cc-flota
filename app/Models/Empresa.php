<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresas';

    public $timestamps = false;

    protected $fillable = [
        'nombre_legal',
        'nombre_comercial',
        'nit',
        'direccion',
        'telefono_empresa',
        'correo_empresa',
        'poc_nombre',
        'poc_email',
        'poc_telefono',
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

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(Unidad::class, 'empresa_id');
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(Licencia::class, 'empresa_id');
    }

    public function marchamos(): HasMany
    {
        return $this->hasMany(Marchamo::class, 'empresa_id');
    }

    public function reemplazoMarchamosEventos(): HasMany
    {
        return $this->hasMany(ReemplazoMarchamoEvento::class, 'empresa_id');
    }

}