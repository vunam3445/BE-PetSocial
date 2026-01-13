<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Pet extends Model
{
    use HasUuids;

    protected $primaryKey = 'pet_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['user_id', 'name', 'type', 'breed', 'gender', 'birthday', 'avatar_url'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }



    public function healthLogs()
    {
        return $this->hasMany(PetHealthLog::class, 'pet_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'pet_follows', 'pet_id', 'user_id')
            ->withPivot('followed_at');
    }
    public function posts()
    {
        return $this->hasMany(Post::class, 'pet_id','pet_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->pet_id)) {
                $model->pet_id = (string) Str::uuid();
            }
        });
    }
}
