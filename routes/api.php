<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\Auth\DoctorAuthController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Api\SpecialtyController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\NotificationController;
use App\Models\Conversation;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

    // Route publique de test
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API Medical Appointment fonctionne !',
            'timestamp' => now(),
            'status' => 'success'
        ]);
    });

    // Routes IA (protégées par Sanctum)
    Route::prefix('ai')->middleware('auth:sanctum')->group(function () {
        Route::get('/conversation/{id}', [AiAssistantController::class, 'getConversationHistory']);
        Route::get('/status', [AiAssistantController::class, 'getAiStatus']);
        Route::get('/frequent-questions', [AiAssistantController::class, 'getFrequentQuestions']);
        Route::post('/chat', [AiAssistantController::class, 'chat']);
        Route::get('/admin/statistics', [AiAssistantController::class, 'getAiStatistics']);
    });

    // Route de diagnostic admin (protégée)
    Route::get('/debug/admin-check', function(Request $request) {
        $user = $request->user();
        
        return response()->json([
            'authenticated' => $user ? true : false,
            'user_type' => $user ? get_class($user) : null,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_role' => $user && isset($user->role) ? $user->role : 'NO_ROLE',
            'is_user_model' => $user instanceof \App\Models\User,
            'middleware_applied' => 'auth:sanctum should be active'
        ]);
    });

    // Route de test pour le template PDF (PUBLIC - POUR TEST UNIQUEMENT)
    Route::get('/test-pdf-simple', function() {
        try {
            $data = [
                'appointment' => (object)[
                    'reference' => 'TEST-123',
                    'appointment_date' => '2024-09-06 10:00:00',
                    'status' => 'confirmed',
                    'status_label' => 'Confirmé',
                    'reason' => 'Test de template',
                    'amount' => 25000,
                    'payment_method' => 'online',
                    'payment_status' => 'pending'
                ],
                'patient' => (object)[
                    'full_name' => 'Test Patient',
                    'email' => 'test@example.com',
                    'phone' => '77 123 456',
                    'date_of_birth' => now()->subYears(30)
                ],
                'doctor' => (object)[
                    'full_name' => 'Dr. Test',
                    'address' => 'Test Address',
                    'city' => 'Dakar',
                    'phone' => '77 987 654'
                ],
                'specialty' => (object)[
                    'name' => 'Médecine Générale'
                ],
                'payment' => null,
                'generated_at' => now(),
                'qr_code_data' => 'https://test.com/verify',
                'preview' => true
            ];
            
            return view('pdf.appointment-receipt', $data);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    });

Route::get('/database-test', function () {
    $tables = [
        'users' => DB::table('users')->count(),
        'patients' => DB::table('patients')->count(),
        'doctors' => DB::table('doctors')->count(),
        'specialties' => DB::table('specialties')->count(),
        'appointments' => DB::table('appointments')->count(),
        'payments' => DB::table('payments')->count(),
    ];
    
    return response()->json([
        'message' => 'Base de données configurée avec succès !',
        'tables' => $tables
    ]);
});


// ============================================================================
// ROUTES PUBLIQUES - Spécialités et Médecins
// ============================================================================

Route::get('/specialties', [SpecialtyController::class, 'index']);
Route::get('/specialties/{id}', [SpecialtyController::class, 'show']);
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
Route::get('/doctors/{id}/available-slots', [DoctorController::class, 'availableSlots']);

// Routes publiques pour la vérification
Route::get('/appointments/{id}/verify', [PdfController::class, 'verifyAppointment']);

// ============================================================================
// ROUTES D'AUTHENTIFICATION
// ============================================================================

// Routes Patients
Route::prefix('auth/patient')->group(function () {
    Route::post('/register', [PatientAuthController::class, 'register']);
    Route::post('/login', [PatientAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [PatientAuthController::class, 'logout']);
        Route::get('/profile', [PatientAuthController::class, 'profile']);
    });
});

// Routes Médecins
Route::prefix('auth/doctor')->group(function () {
    Route::post('/login', [DoctorAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [DoctorAuthController::class, 'logout']);
        Route::get('/profile', [DoctorAuthController::class, 'profile']);
        Route::put('/profile', [DoctorAuthController::class, 'updateProfile']);
    });
});

// Routes Administrateurs
Route::prefix('auth/admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/profile', [AdminAuthController::class, 'profile']);
    });
});

// Route pour identifier l'utilisateur connecté automatiquement
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $user = $request->user();
    
    if ($user instanceof \App\Models\Patient) {
        $user->load('appointments.doctor.specialty');
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'user_type' => 'patient'
        ]);
    }
    
    if ($user instanceof \App\Models\Doctor) {
        $user->load(['specialty', 'appointments.patient']);
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'user_type' => 'doctor'
        ]);
    }
    
    if ($user instanceof \App\Models\User) {
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'user_type' => 'admin'
        ]);
    }
    
    return response()->json([
        'status' => 'error',
        'message' => 'Type d\'utilisateur non reconnu'
    ], 400);
});


// ============================================================================
// ROUTES PROTÉGÉES
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Routes des rendez-vous
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        Route::put('/{id}/cancel', [AppointmentController::class, 'cancel']);
        Route::put('/{id}/confirm', [AppointmentController::class, 'confirm']);
    });
    
    // Routes des paiements
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/initiate', [PaymentController::class, 'initiatePayment']);
        Route::get('/{id}/status', [PaymentController::class, 'checkPaymentStatus']);
    });
    
    // Routes des PDF/Justificatifs
    Route::prefix('pdf')->group(function () {
        Route::get('/appointments/{id}/receipt', [PdfController::class, 'generateAppointmentReceipt']);
        Route::get('/appointments/{id}/preview', [PdfController::class, 'previewAppointmentReceipt']);
        Route::get('/payments/{id}/receipt', [PdfController::class, 'generatePaymentReceipt']);
    });
    
    // Routes des notifications/emails
    Route::prefix('notifications')->group(function () {
        Route::post('/test-email', [NotificationController::class, 'sendTestEmail']);
        Route::get('/email-config', [NotificationController::class, 'testEmailConfiguration']);
        Route::post('/appointments/{id}/created', [NotificationController::class, 'sendAppointmentCreated']);
        Route::post('/appointments/{id}/confirmed', [NotificationController::class, 'sendAppointmentConfirmed']);
        Route::post('/appointments/{id}/reminder', [NotificationController::class, 'sendAppointmentReminder']);
        Route::post('/payments/{id}/confirmed', [NotificationController::class, 'sendPaymentConfirmation']);
    });
    
    // Route de diagnostic pour debug
    Route::get('/debug/permissions/{appointmentId}', function ($appointmentId, Request $request) {
        try {
            $user = $request->user();
            
            $appointment = \App\Models\Appointment::with(['patient', 'doctor'])->find($appointmentId);
            
            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }
            
            return response()->json([
                'user_info' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'type' => get_class($user),
                    'role' => $user->role ?? 'N/A'
                ],
                'appointment_info' => [
                    'id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_email' => $appointment->patient->email ?? 'N/A',
                    'doctor_email' => $appointment->doctor->email ?? 'N/A'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    });
    
});

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
    });


    // Routes IA - VERSION MANUELLE
    Route::get('ai/status', function() {
        return response()->json([
            'ai_mode' => env('AI_MODE', 'simulator'),
            'available' => true,
            'features' => [
                'general_health_questions' => true,
                'platform_guidance' => true
            ]
        ]);
    });

    Route::get('ai/frequent-questions', function() {
        return response()->json([
            'status' => 'success',
            'data' => [
                [
                    'category' => 'Santé',
                    'questions' => ['Que faire en cas de fièvre ?', 'Comment gérer le stress ?']
                ]
            ]
        ]);
    });


    // Route alternative avec authentification manuelle
Route::get('/ai/admin/statistics-manual', function(Request $request) {
    $authHeader = $request->header('Authorization');
    $token = null;
    
    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
    }
    
    if (!$token) {
        return response()->json(['error' => 'Token manquant'], 401);
    }
    
    // Authentification manuelle
    $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
    if (!$personalAccessToken) {
        return response()->json(['error' => 'Token invalide'], 401);
    }
    
    $user = $personalAccessToken->tokenable;
    if (!$user || !($user instanceof \App\Models\User) || $user->role !== 'admin') {
        return response()->json(['error' => 'Admin requis'], 403);
    }
    
    // Statistiques
    $stats = [
        'total_conversations' => \App\Models\Conversation::distinct('conversation_id')->count(),
        'total_messages' => \App\Models\Conversation::count(),
        'manual_auth' => true,
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]
    ];
    
    return response()->json([
        'status' => 'success',
        'data' => $stats
    ]);
});




            // Route pour vérifier l'état de la table conversations
                Route::get('/test/check-conversations', function () {
                    try {
                        $tableExists = Schema::hasTable('conversations');
                        
                        if (!$tableExists) {
                            return response()->json([
                                'table_exists' => false,
                                'message' => 'Table conversations n\'existe pas. Lancez: php artisan migrate'
                            ]);
                        }

                        $totalRecords = Conversation::count();
                        
                        $stats = [
                            'table_exists' => true,
                            'total_records' => $totalRecords,
                        ];

                        if ($totalRecords > 0) {
                            $stats['distinct_conversations'] = Conversation::select('conversation_id')->distinct()->count();
                            $stats['medical_questions'] = Conversation::where('is_medical_question', true)->count();
                            $stats['recent_records'] = Conversation::orderBy('created_at', 'desc')
                                ->limit(3)
                                ->get(['id', 'conversation_id', 'user_message', 'created_at']);
                        } else {
                            $stats['message'] = 'Table vide - aucun enregistrement trouvé';
                        }

                        return response()->json($stats);

                    } catch (\Exception $e) {
                        return response()->json([
                            'error' => $e->getMessage(),
                            'line' => $e->getLine(),
                            'file' => basename($e->getFile())
                        ], 500);
                    }
                });

                // Route pour ajouter des conversations de test
                Route::get('/test/add-conversations', function () {
        try {
            if (!Schema::hasTable('conversations')) {
                return response()->json([
                    'error' => 'Table conversations n\'existe pas'
                ], 404);
            }

            // Générer des IDs uniques avec microtime et random
            $uniqueId = time() . '-' . mt_rand(1000, 9999);
            
            $testData = [
                [
                    'conversation_id' => 'test-' . $uniqueId . '-conv1',
                    'user_type' => 'anonymous',
                    'user_id' => null,
                    'user_message' => 'Test: J\'ai de la fièvre depuis 2 jours',
                    'ai_response' => 'Test: La fièvre peut indiquer une infection. Consultez un médecin si elle persiste.',
                    'is_medical_question' => true,
                    'ai_mode' => 'simulator',
                    'user_context' => '{}',
                    'suggestions' => '[{"text":"Consulter un médecin","action":"search_doctors"}]',
                    'session_id' => 'test-session-' . $uniqueId
                ],
                [
                    'conversation_id' => 'test-' . $uniqueId . '-conv2',
                    'user_type' => 'anonymous',
                    'user_id' => null,
                    'user_message' => 'Test: Comment annuler un rendez-vous?',
                    'ai_response' => 'Test: Vous pouvez annuler via votre espace personnel ou contacter le cabinet.',
                    'is_medical_question' => false,
                    'ai_mode' => 'simulator',
                    'user_context' => '{}',
                    'suggestions' => '[{"text":"Voir mes RDV","action":"view_appointments"}]',
                    'session_id' => 'test-session-' . $uniqueId
                ],
                [
                    'conversation_id' => 'test-' . $uniqueId . '-conv3',
                    'user_type' => 'anonymous',
                    'user_id' => null,
                    'user_message' => 'Test: Quels sont les tarifs de consultation?',
                    'ai_response' => 'Test: Les tarifs varient selon les spécialités. Consultez les profils des médecins.',
                    'is_medical_question' => false,
                    'ai_mode' => 'simulator',
                    'user_context' => '{}',
                    'suggestions' => '[]',
                    'session_id' => 'test-session-' . $uniqueId
                ]
            ];

            $created = 0;
            $errors = [];
            
            foreach ($testData as $data) {
                try {
                    // Vérifier que l'ID n'existe pas déjà (sécurité supplémentaire)
                    if (!Conversation::where('conversation_id', $data['conversation_id'])->exists()) {
                        Conversation::create($data);
                        $created++;
                    } else {
                        $errors[] = "ID {$data['conversation_id']} existe déjà";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Erreur pour {$data['conversation_id']}: " . $e->getMessage();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "$created nouvelles conversations créées",
                'total_records' => Conversation::count(),
                'errors' => $errors,
                'unique_id_used' => $uniqueId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    });

            // Route pour nettoyer les données de test
            Route::get('/test/clean-conversations', function () {
                try {
                    $deleted = Conversation::where('conversation_id', 'like', 'test-conv-%')->delete();
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => "$deleted conversations de test supprimées",
                        'remaining_records' => Conversation::count()
                    ]);

                } catch (\Exception $e) {
                    return response()->json([
                        'error' => $e->getMessage()
                    ], 500);
                }
            });




                Route::get('/test/statistics', function () {
        try {
            $stats = [];
            
            // Test de base - vérifier la table
            $tableExists = Schema::hasTable('conversations');
            $stats['table_exists'] = $tableExists;
            
            if (!$tableExists) {
                return response()->json([
                    'error' => 'Table conversations n\'existe pas',
                    'stats' => $stats
                ], 404);
            }

            // Compter le total d'enregistrements
            $totalRecords = \App\Models\Conversation::count();
            $stats['total_records'] = $totalRecords;
            
            if ($totalRecords == 0) {
                return response()->json([
                    'message' => 'Table vide',
                    'stats' => $stats
                ]);
            }

            // Statistiques détaillées - méthode sécurisée
            try {
                // 1. Total conversations distinctes (méthode sûre)
                $stats['total_conversations'] = DB::table('conversations')
                    ->select('conversation_id')
                    ->distinct()
                    ->count();
            } catch (\Exception $e) {
                $stats['total_conversations'] = 'Erreur: ' . $e->getMessage();
            }

            try {
                // 2. Total messages
                $stats['total_messages'] = \App\Models\Conversation::count();
            } catch (\Exception $e) {
                $stats['total_messages'] = 'Erreur: ' . $e->getMessage();
            }

            try {
                // 3. Questions médicales
                $stats['medical_questions'] = \App\Models\Conversation::where('is_medical_question', true)->count();
            } catch (\Exception $e) {
                $stats['medical_questions'] = 'Erreur: ' . $e->getMessage();
            }

            try {
                // 4. Conversations aujourd'hui
                $stats['today_conversations'] = DB::table('conversations')
                    ->select('conversation_id')
                    ->whereDate('created_at', today())
                    ->distinct()
                    ->count();
            } catch (\Exception $e) {
                $stats['today_conversations'] = 'Erreur: ' . $e->getMessage();
            }

            // Statistiques supplémentaires
            $stats['ai_modes'] = \App\Models\Conversation::select('ai_mode')
                ->selectRaw('count(*) as count')
                ->groupBy('ai_mode')
                ->pluck('count', 'ai_mode')
                ->toArray();

            // Aperçu des données récentes
            $stats['recent_conversations'] = \App\Models\Conversation::orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['id', 'conversation_id', 'user_message', 'is_medical_question', 'created_at']);

            return response()->json([
                'status' => 'success',
                'message' => 'Statistiques calculées avec succès',
                'data' => $stats,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    });


// Webhooks publics
Route::post('/payments/cinetpay/notify', [PaymentController::class, 'cinetPayNotify']);
Route::post('/cron/automatic-reminders', [NotificationController::class, 'sendAutomaticReminders']);



// Ajoutez ceci dans routes/api.php pour créer une version sans auth

Route::get('/ai/admin/statistics-public', function () {
    try {
        // Copie exacte de la logique qui marche dans /test/statistics
        $stats = [];
        
        $tableExists = Schema::hasTable('conversations');
        if (!$tableExists) {
            return response()->json([
                'error' => 'Table conversations n\'existe pas'
            ], 404);
        }

        $totalRecords = \App\Models\Conversation::count();
        
        if ($totalRecords == 0) {
            $stats = [
                'total_conversations' => 0,
                'total_messages' => 0,
                'medical_questions' => 0,
                'today_conversations' => 0,
            ];
        } else {
            $stats['total_conversations'] = DB::table('conversations')
                ->select('conversation_id')
                ->distinct()
                ->count();
                
            $stats['total_messages'] = \App\Models\Conversation::count();
            
            $stats['medical_questions'] = \App\Models\Conversation::where('is_medical_question', true)->count();
            
            $stats['today_conversations'] = DB::table('conversations')
                ->select('conversation_id')
                ->whereDate('created_at', today())
                ->distinct()
                ->count();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Statistiques récupérées avec succès',
            'data' => [
                'overview' => $stats,
                'generated_at' => now()->toISOString()
            ],
            'note' => 'Version publique pour debug - RETIRER EN PRODUCTION'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la génération des statistiques',
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ], 500);
    }
});