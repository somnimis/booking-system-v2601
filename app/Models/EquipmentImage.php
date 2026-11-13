<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentImage extends Model
{
    use HasFactory;

    protected $table = 'equipment_images';
    protected $primaryKey = 'image_id';

    protected $fillable = [
        'equipment_id',
        'description',
        'sort_order',
        'image_url',
        'image_type'
    ];

    public function getImageUrlAttribute($value)
{
    // If it's already a full URL (like an old Cloudinary link), return it as-is.
    if (str_starts_with($value, 'http')) {
        return $value;
    }
    
    // Otherwise, prepend the app URL to the relative path
    return asset($value);
}

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }
}