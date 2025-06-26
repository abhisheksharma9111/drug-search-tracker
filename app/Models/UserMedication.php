<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMedication extends Model
{
    use HasFactory;

    protected $fillable = [
        'rxcui',
        'name',
        'base_names',
        'dosage_forms'
    ];

    protected $casts = [
        'base_names' => 'array',
        'dosage_forms' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
