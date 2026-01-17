<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSupplier extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingSupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'website',
        'notes',
    ];

    // Relationships

    public function courses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class);
    }
}
