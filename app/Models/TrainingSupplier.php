<?php

namespace App\Models;

use Database\Factories\TrainingSupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('name', 'contact_email', 'contact_phone', 'website', 'notes')]
class TrainingSupplier extends Model
{
    /** @use HasFactory<TrainingSupplierFactory> */
    use HasFactory;

    // Relationships

    public function courses(): HasMany
    {
        return $this->hasMany(TrainingCourse::class);
    }
}
