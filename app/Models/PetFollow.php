<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PetFollow extends Pivot
{
    protected $table = 'pet_follows';
    
    // 1. KHÔNG để mảng ở đây, để null hoặc 'id' (dù không có id)
    protected $primaryKey = null; 
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_id', 'pet_id'];

    /**
     * Chỉ định cách tìm bản ghi để xóa/cập nhật
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query->where('user_id', $this->getAttribute('user_id'))
                     ->where('pet_id', $this->getAttribute('pet_id'));
    }
}