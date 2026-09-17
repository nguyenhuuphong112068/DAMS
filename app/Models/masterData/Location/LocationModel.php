<?php

namespace App\Models\masterData\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationModel extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'warehouse_id',
        'shelf_id',
        'tier_id',
        'room_id',
        'status_id',
        'active',
        'created_by',
    ];

    public function tier()
    {
        return $this->belongsTo(\App\Models\masterData\Tier\TierModel::class, 'tier_id');
    }

    public function shelf()
    {
        return $this->belongsTo(\App\Models\masterData\Shelf\ShelfModel::class, 'shelf_id');
    }

    public function documents()
    {
        return $this->hasMany(\App\Models\masterData\Document\DocumentModel::class, 'location_id');
    }
}
