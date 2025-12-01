<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PetFollow extends Pivot
{
    protected $table = 'pet_follows';
    public $incrementing = false;
    public $timestamps = false; // vì migration có timestamps

    protected $fillable = ['user_id', 'pet_id'];

   
}
