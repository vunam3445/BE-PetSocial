<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Pet extends Model
{
    use HasUuids;

    protected $primaryKey = 'pet_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['user_id', 'name', 'type', 'breed', 'gender', 'birthday', 'avatar_url'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
