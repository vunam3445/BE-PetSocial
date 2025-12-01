<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class PetHealthLog extends Model
{
    use HasUuids;

    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'pet_id',
        'category_id',
        'title',
        'description',
        'value',
        'image_url',
        'recorded_at',
    ];

    protected $dates = ['recorded_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->log_id)) {
                $model->log_id = (string) Str::uuid();
            }
        });
    }

    // 🐾 Một bản ghi thuộc về 1 thú cưng
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    // 📋 Một bản ghi thuộc về 1 loại hạng mục
    public function category()
    {
        return $this->belongsTo(HealthCategory::class, 'category_id');
    }
}
