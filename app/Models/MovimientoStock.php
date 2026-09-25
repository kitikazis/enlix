<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'referencia',
        'nota',
        'user_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
