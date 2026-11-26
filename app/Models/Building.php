<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = [
        'name',
        'address',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
