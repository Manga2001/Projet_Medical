<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = [
            [
                'first_name' => 'Moussa',
                'last_name' => 'Diallo',
                'email' => 'dr.diallo@medical-app.com',
                'password' => Hash::make('doctor123'),
                'phone' => '77 111 22 33',
                'specialty_id' => 1, // Médecine Générale
                'license_number' => 'MED-001-SN',
                'bio' => '15 ans d\'expérience en médecine générale. Diplômé de l\'Université Cheikh Anta Diop.',
                'address' => 'Cabinet Médical Plateau, Dakar',
                'city' => 'Dakar',
                'consultation_fee' => 25000,
                'available_hours' => [
                    'monday' => ['08:00-12:00', '14:00-18:00'],
                    'tuesday' => ['08:00-12:00', '14:00-18:00'],
                    'wednesday' => ['08:00-12:00'],
                    'thursday' => ['08:00-12:00', '14:00-18:00'],
                    'friday' => ['08:00-12:00', '14:00-18:00'],
                    'saturday' => ['08:00-13:00']
                ],
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Aminata',
                'last_name' => 'Sow',
                'email' => 'dr.sow@medical-app.com',
                'password' => Hash::make('doctor123'),
                'phone' => '77 444 55 66',
                'specialty_id' => 2, // Cardiologie
                'license_number' => 'CARD-002-SN',
                'bio' => 'Cardiologue experte, spécialisée dans les maladies cardiovasculaires.',
                'address' => 'Clinique du Cœur, Almadies',
                'city' => 'Dakar',
                'consultation_fee' => 50000,
                'available_hours' => [
                    'monday' => ['09:00-13:00', '15:00-19:00'],
                    'tuesday' => ['09:00-13:00', '15:00-19:00'],
                    'wednesday' => ['09:00-13:00'],
                    'thursday' => ['09:00-13:00', '15:00-19:00'],
                    'friday' => ['09:00-13:00']
                ],
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Ibrahima',
                'last_name' => 'Fall',
                'email' => 'dr.fall@medical-app.com',
                'password' => Hash::make('doctor123'),
                'phone' => '77 777 88 99',
                'specialty_id' => 3, // Dermatologie
                'license_number' => 'DERM-003-SN',
                'bio' => 'Dermatologue spécialisé dans les maladies de la peau tropicales.',
                'address' => 'Centre Dermatologique, Mermoz',
                'city' => 'Dakar',
                'consultation_fee' => 35000,
                'available_hours' => [
                    'monday' => ['08:30-12:30', '14:30-17:30'],
                    'tuesday' => ['08:30-12:30', '14:30-17:30'],
                    'wednesday' => ['08:30-12:30', '14:30-17:30'],
                    'thursday' => ['08:30-12:30', '14:30-17:30'],
                    'friday' => ['08:30-12:30']
                ],
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Mariam',
                'last_name' => 'Ndiaye',
                'email' => 'dr.ndiaye@medical-app.com',
                'password' => Hash::make('doctor123'),
                'phone' => '76 555 66 77',
                'specialty_id' => 4, // Pédiatrie
                'license_number' => 'PED-004-SN',
                'bio' => 'Pédiatre avec une expertise en néonatologie.',
                'address' => 'Hôpital pour Enfants, Fann',
                'city' => 'Dakar',
                'consultation_fee' => 30000,
                'available_hours' => [
                    'monday' => ['07:30-11:30', '13:30-17:30'],
                    'tuesday' => ['07:30-11:30', '13:30-17:30'],
                    'wednesday' => ['07:30-11:30', '13:30-17:30'],
                    'thursday' => ['07:30-11:30', '13:30-17:30'],
                    'friday' => ['07:30-11:30'],
                    'saturday' => ['08:00-12:00']
                ],
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Ba',
                'email' => 'dr.ba@medical-app.com',
                'password' => Hash::make('doctor123'),
                'phone' => '77 222 33 44',
                'specialty_id' => 6, // Dentisterie
                'license_number' => 'DENT-005-SN',
                'bio' => 'Chirurgien dentiste, spécialiste en implantologie.',
                'address' => 'Cabinet Dentaire Moderne, Point E',
                'city' => 'Dakar',
                'consultation_fee' => 20000,
                'available_hours' => [
                    'monday' => ['08:00-12:00', '14:00-18:00'],
                    'tuesday' => ['08:00-12:00', '14:00-18:00'],
                    'wednesday' => ['08:00-12:00', '14:00-18:00'],
                    'thursday' => ['08:00-12:00', '14:00-18:00'],
                    'friday' => ['08:00-12:00', '14:00-18:00'],
                    'saturday' => ['09:00-14:00']
                ],
                'is_active' => true,
                'email_verified_at' => now()
            ]
        ];

        foreach ($doctors as $doctor) {
            Doctor::create($doctor);
        }
    }
}