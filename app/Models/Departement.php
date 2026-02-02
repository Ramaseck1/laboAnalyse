<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;
    protected $fillable = ['nom'];

    public function analyses()
    {
        return $this->hasMany(Analyse::class, 'departement_id');    }
}
