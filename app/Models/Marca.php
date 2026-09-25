<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'slug',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
