<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacilityImage extends Model
{
    use HasFactory;

    protected $table = 'facility_images';
    protected $primaryKey = 'image_id';

    protected $fillable = [
        'facility_id',
        'image_url',
        'description',
        'sort_order',
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

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }
}


