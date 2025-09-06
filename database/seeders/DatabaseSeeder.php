<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ordre important : les dépendances d'abord
        $this->call([
            UserSeeder::class,        // Administrateurs
            SpecialtySeeder::class,   // Spécialités (avant les médecins)
            DoctorSeeder::class,      // Médecins (après les spécialités)
            PatientSeeder::class,     // Patients
            AppointmentSeeder::class, // Rendez-vous (après patients et médecins)
        ]);

        $this->command->info('✅ Base de données peuplée avec succès !');
        $this->command->info('📊 Données créées :');
        $this->command->info('   - 2 Administrateurs');
        $this->command->info('   - 10 Spécialités médicales');
        $this->command->info('   - 5 Médecins');
        $this->command->info('   - 5 Patients');
        $this->command->info('   - 6 Rendez-vous');
    }
}