<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrito extends Model
{
    use Prunable;

    protected $table = 'carritos';

    protected $fillable = [
        'user_id',
        'session_id',
        'expira_en',
    ];

    protected $casts = [
        'expira_en' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemCarrito::class);
    }

    /**
     * Carritos sin actividad hace 30+ días: no se relacionan con Pedido (el
     * checkout copia sus datos a `pedidos`/`items_pedido`, nunca referencia
     * el carrito de origen), así que son solo basura transitoria. El borrado
     * en cascada de `items_carrito` ya está declarado en su migración.
     */
    public function prunable(): Builder
    {
        return static::where('updated_at', '<', now()->subDays(30));
    }
}
