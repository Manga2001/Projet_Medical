<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation : Une spécialité a plusieurs médecins
     */
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    /**
     * Scope pour filtrer les spécialités actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accesseur pour compter les médecins actifs
     */
    public function getActiveDoctorsCountAttribute()
    {
        return $this->doctors()->active()->count();
    }
}