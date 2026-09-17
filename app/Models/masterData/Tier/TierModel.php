<?php

namespace App\Models\masterData\Tier;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TierModel extends Model
{
    use HasFactory;

    protected $table = 'tiers';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'warehouse_id',
        'shelf_id',
        'max_locations',
        'position',
        'status_id',
        'active',
        'created_by',
        'updated_by',
    ];

    public function shelf()
    {
        return $this->belongsTo(\App\Models\masterData\Shelf\ShelfModel::class, 'shelf_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\masterData\Warehouse\WarehouseModel::class, 'warehouse_id');
    }

    public function locations()
    {
        return $this->hasMany(\App\Models\masterData\Location\LocationModel::class, 'tier_id');
    }
}
