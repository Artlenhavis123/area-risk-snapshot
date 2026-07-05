<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Lookup extends Model
{
    protected $fillable = [
        'postcode',
        'area_name',
        'latitude',
        'longitude',
        'total_crimes',
        'top_category',
        'data_month',
    ];

    public function scopeRecent(Builder $query, int $limit = 5): Builder
    {
        return $query->latest()->limit($limit);
    }
}
