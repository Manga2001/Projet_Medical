<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialty;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            [
                'name' => 'Médecine Générale',
                'description' => 'Consultation médicale générale, suivi de santé global',
                'is_active' => true
            ],
            [
                'name' => 'Cardiologie',
                'description' => 'Spécialiste du cœur et des vaisseaux sanguins',
                'is_active' => true
            ],
            [
                'name' => 'Dermatologie',
                'description' => 'Spécialiste de la peau, des cheveux et des ongles',
                'is_active' => true
            ],
            [
                'name' => 'Pédiatrie',
                'description' => 'Spécialiste de la santé des enfants et adolescents',
                'is_active' => true
            ],
            [
                'name' => 'Gynécologie',
                'description' => 'Spécialiste de la santé féminine et reproductive',
                'is_active' => true
            ],
            [
                'name' => 'Dentisterie',
                'description' => 'Soins dentaires et de la cavité buccale',
                'is_active' => true
            ],
            [
                'name' => 'Ophtalmologie',
                'description' => 'Spécialiste des yeux et de la vision',
                'is_active' => true
            ],
            [
                'name' => 'Orthopédie',
                'description' => 'Spécialiste des os, articulations et muscles',
                'is_active' => true
            ],
            [
                'name' => 'Psychiatrie',
                'description' => 'Spécialiste de la santé mentale',
                'is_active' => true
            ],
            [
                'name' => 'ORL',
                'description' => 'Spécialiste des oreilles, nez et gorge',
                'is_active' => true
            ]
        ];

        foreach ($specialties as $specialty) {
            Specialty::create($specialty);
        }
    }
}