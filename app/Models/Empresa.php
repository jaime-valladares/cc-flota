<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}