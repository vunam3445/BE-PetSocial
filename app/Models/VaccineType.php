<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class VaccineType extends Model
{
    use HasUuids;

    protected $primaryKey = 'vaccine_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'description', 'doses_required', 'interval_days'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->vaccine_id)) {
                $model->vaccine_id = (string) Str::uuid();
            }
        });
    }

    public function species()
    {
        return $this->belongsToMany(PetSpecies::class, 'species_vaccine_applicability', 'vaccine_id', 'species_id');
    }

    public function petVaccinations()
    {
        return $this->hasMany(PetVaccination::class, 'vaccine_id');
    }
}
