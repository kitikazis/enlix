<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrito extends Model
{
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
}
