<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class HealthCategory extends Model
{
    use HasUuids;

    protected $primaryKey = 'category_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'pet_id',
        'name',
        'description',
        'applicable_species',
        'category_type',
        'interval_days',
        'unit',
    ];

    protected $casts = [
        'applicable_species' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->category_id)) {
                $model->category_id = (string) Str::uuid();
            }
        });
    }

    //Quan hệ: Một HealthCategory có thể được tạo bởi 1 user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //Quan hệ: Một category có thể có nhiều bản ghi log
    public function healthLogs()
    {
        return $this->hasMany(PetHealthLog::class, 'category_id');
    }
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }
}
