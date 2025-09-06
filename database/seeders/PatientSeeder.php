<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = [
            [
                'first_name' => 'Khadija',
                'last_name' => 'Sarr',
                'email' => 'khadija.sarr@email.com',
                'password' => Hash::make('patient123'),
                'phone' => '77 100 200 300',
                'date_of_birth' => '1995-03-12',
                'gender' => 'female',
                'address' => 'HLM Grand Yoff, Dakar',
                'city' => 'Dakar',
                'emergency_contact_name' => 'Awa Sarr',
                'emergency_contact_phone' => '77 100 200 301',
                'medical_history' => 'Hypertension légère diagnostiquée en 2022',
                'allergies' => 'Pénicilline',
                'blood_type' => 'O+',
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Mamadou',
                'last_name' => 'Diop',
                'email' => 'mamadou.diop@email.com',
                'password' => Hash::make('patient123'),
                'phone' => '76 400 500 600',
                'date_of_birth' => '1988-07-25',
                'gender' => 'male',
                'address' => 'Médina, Dakar',
                'city' => 'Dakar',
                'emergency_contact_name' => 'Aissatou Diop',
                'emergency_contact_phone' => '76 400 500 601',
                'medical_history' => 'Diabète type 2, suivi depuis 2020',
                'allergies' => 'Aucune allergie connue',
                'blood_type' => 'A+',
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Bineta',
                'last_name' => 'Kane',
                'email' => 'bineta.kane@email.com',
                'password' => Hash::make('patient123'),
                'phone' => '77 700 800 900',
                'date_of_birth' => '2000-11-08',
                'gender' => 'female',
                'address' => 'Sacré Cœur, Dakar',
                'city' => 'Dakar',
                'emergency_contact_name' => 'Ousmane Kane',
                'emergency_contact_phone' => '77 700 800 901',
                'medical_history' => 'Aucun antécédent médical particulier',
                'allergies' => 'Fruits de mer',
                'blood_type' => 'B-',
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Cheikh',
                'last_name' => 'Mbaye',
                'email' => 'cheikh.mbaye@email.com',
                'password' => Hash::make('patient123'),
                'phone' => '76 111 222 333',
                'date_of_birth' => '1992-01-30',
                'gender' => 'male',
                'address' => 'Liberté 6, Dakar',
                'city' => 'Dakar',
                'emergency_contact_name' => 'Fatou Mbaye',
                'emergency_contact_phone' => '76 111 222 334',
                'medical_history' => 'Asthme léger depuis l\'enfance',
                'allergies' => 'Pollen, acariens',
                'blood_type' => 'AB+',
                'is_active' => true,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Astou',
                'last_name' => 'Gueye',
                'email' => 'astou.gueye@email.com',
                'password' => Hash::make('patient123'),
                'phone' => '77 888 999 000',
                'date_of_birth' => '1985-09-15',
                'gender' => 'female',
                'address' => 'Parcelles Assainies, Dakar',
                'city' => 'Dakar',
                'emergency_contact_name' => 'Modou Gueye',
                'emergency_contact_phone' => '77 888 999 001',
                'medical_history' => 'Migraine chronique',
                'allergies' => 'Aspirine',
                'blood_type' => 'O-',
                'is_active' => true,
                'email_verified_at' => now()
            ]
        ];

        foreach ($patients as $patient) {
            Patient::create($patient);
        }
    }
}