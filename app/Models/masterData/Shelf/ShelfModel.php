<?php

namespace App\Models\masterData\Shelf;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShelfModel extends Model
{
    use HasFactory;

    protected $table = 'shelves';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'warehouse_id',
        'room_id',
        'status_id',
        'active',
        'created_by',
    ];

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\masterData\Warehouse\WarehouseModel::class, 'warehouse_id');
    }

    public function room()
    {
        return $this->belongsTo(\App\Models\masterData\Room\RoomModel::class, 'room_id');
    }

    public function tiers()
    {
        return $this->hasMany(\App\Models\masterData\Tier\TierModel::class, 'shelf_id');
    }

    public function locations()
    {
        return $this->hasMany(\App\Models\masterData\Location\LocationModel::class, 'shelf_id');
    }
}
