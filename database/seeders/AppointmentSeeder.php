<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointments = [
            [
                'reference' => Appointment::generateReference(),
                'patient_id' => 1, // Khadija Sarr
                'doctor_id' => 1,  // Dr. Diallo (Médecine Générale)
                'appointment_date' => Carbon::now()->addDays(1)->setTime(9, 0),
                'status' => 'confirmed',
                'reason' => 'Consultation de routine et suivi tension artérielle',
                'notes' => null,
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'amount' => 25000,
                'confirmed_at' => Carbon::now()
            ],
            [
                'reference' => Appointment::generateReference(),
                'patient_id' => 2, // Mamadou Diop
                'doctor_id' => 2,  // Dr. Sow (Cardiologie)
                'appointment_date' => Carbon::now()->addDays(2)->setTime(10, 30),
                'status' => 'confirmed',
                'reason' => 'Consultation cardiologique - suivi diabète',
                'notes' => null,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'amount' => 50000,
                'confirmed_at' => Carbon::now()
            ],
            [
                'reference' => Appointment::generateReference(),
                'patient_id' => 3, // Bineta Kane
                'doctor_id' => 3,  // Dr. Fall (Dermatologie)
                'appointment_date' => Carbon::now()->addDays(3)->setTime(14, 0),
                'status' => 'pending',
                'reason' => 'Problème de peau - éruption cutanée',
                'notes' => null,
                'payment_method' => 'online',
                'payment_status' => 'pending',
                'amount' => 35000,
                'confirmed_at' => null
            ],
            [
                'reference' => Appointment::generateReference(),
                'patient_id' => 4, // Cheikh Mbaye
                'doctor_id' => 1,  // Dr. Diallo (Médecine Générale)
                'appointment_date' => Carbon::now()->addDays(4)->setTime(15, 30),
                'status' => 'confirmed',
                'reason' => 'Suivi asthme et renouvellement ordonnance',
                'notes' => null,
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'amount' => 25000,
                'confirmed_at' => Carbon::now()
            ],
            [
                'reference' => Appointment::generateReference(),
                'patient_id' => 5, // Astou Gueye
                'doctor_id' => 5,  // Dr. Ba (Dentisterie)
                'appointment_date' => Carbon::now()->addDays(5)->setTime(11, 0),
                'status' => 'confirmed',
                'reason' => 'Nettoyage dentaire et contrôle',
                'notes' => null,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'amount' => 20000,
                'confirmed_at' => Carbon::now()
            ],
            [
                'reference' => Appointment::generateReference(),
                'patient_id' => 1, // Khadija Sarr
                'doctor_id' => 4,  // Dr. Ndiaye (Pédiatrie) - pour son enfant
                'appointment_date' => Carbon::now()->subDays(2)->setTime(9, 30),
                'status' => 'completed',
                'reason' => 'Vaccination enfant',
                'notes' => 'Vaccination réalisée avec succès. Prochain RDV dans 3 mois.',
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'amount' => 30000,
                'confirmed_at' => Carbon::now()->subDays(3)
            ]
        ];

        foreach ($appointments as $appointment) {
            Appointment::create($appointment);
        }
    }
}