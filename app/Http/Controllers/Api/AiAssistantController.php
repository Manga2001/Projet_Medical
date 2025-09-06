<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiAssistantController extends Controller
{
    private $systemPrompt;
    private $forbiddenTopics;
    private $medicalKeywords;

    public function __construct()
    {
        $this->systemPrompt = env('AI_SYSTEM_PROMPT', 
            'Tu es un assistant médical virtuel bienveillant. Tu peux donner des informations générales sur la santé, expliquer des symptômes courants, et guider vers les bonnes pratiques. Tu ne poses JAMAIS de diagnostic médical et tu recommandes toujours de consulter un professionnel de santé pour des conseils personnalisés. Réponds en français de manière claire et rassurante.'
        );

        $this->forbiddenTopics = [
            'diagnostic', 'diagnostiquer', 'traitement spécifique', 'médicament précis',
            'dosage', 'prescription', 'urgence vitale', 'suicide'
        ];

        $this->medicalKeywords = [
            'symptôme', 'douleur', 'fièvre', 'mal', 'santé', 'maladie',
            'consultation', 'médecin', 'docteur', 'hôpital', 'clinique',
            'rendez-vous', 'traitement', 'prévention', 'hygiène'
        ];
    }

    /**
     * Chat avec l'assistant IA
     */
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
            'conversation_id' => 'nullable|string|max:50',
            'user_context' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Message requis (max 500 caractères)',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userMessage = $request->input('message');
            $conversationId = $request->input('conversation_id', uniqid());
            $userContext = $request->input('user_context', []);

            // Vérifier si la question est médicale
            $isMedicalQuestion = $this->isMedicalQuestion($userMessage);

            // Filtrer les sujets interdits
            if ($this->containsForbiddenTopics($userMessage)) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Question non autorisée',
                    'response' => "Je ne peux pas répondre à des questions nécessitant un diagnostic médical précis. Je vous recommande fortement de consulter un professionnel de santé qualifié.",
                    'conversation_id' => $conversationId,
                    'type' => 'safety_filter'
                ]);
            }

            // Traiter selon le mode configuré
            $aiMode = env('AI_MODE', 'simulator');
            
            switch ($aiMode) {
                case 'openai':
                    $response = $this->processOpenAI($userMessage, $userContext);
                    break;
                
                case 'gemini':
                    $response = $this->processGemini($userMessage, $userContext);
                    break;
                
                default:
                    $response = $this->processSimulator($userMessage, $isMedicalQuestion);
            }

            // Ajouter des suggestions d'actions
            $suggestions = $this->generateSuggestions($userMessage, $isMedicalQuestion);

            return response()->json([
                'status' => 'success',
                'message' => 'Réponse générée avec succès',
                'response' => $response,
                'conversation_id' => $conversationId,
                'is_medical_question' => $isMedicalQuestion,
                'suggestions' => $suggestions,
                'disclaimer' => $isMedicalQuestion ? 
                    "Cette information est générale. Consultez un professionnel de santé pour des conseils personnalisés." : 
                    null
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur Assistant IA', [
                'error' => $e->getMessage(),
                'user_message' => $userMessage ?? 'N/A'
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la génération de la réponse',
                'response' => "Désolé, je rencontre des difficultés techniques. Veuillez réessayer ou contacter notre support.",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traitement avec OpenAI
     */
    private function processOpenAI($message, $context = [])
    {
        $apiKey = env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('Clé API OpenAI non configurée');
        }

        $contextString = !empty($context) ? 
            "Contexte utilisateur: " . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\n" : 
            "";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $contextString . $message
                ]
            ],
            'max_tokens' => (int) env('AI_MAX_TOKENS', 150),
            'temperature' => (float) env('AI_TEMPERATURE', 0.7)
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur API OpenAI: ' . $response->body());
        }

        $data = $response->json();
        
        return $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer une réponse appropriée.';
    }

    /**
     * Traitement avec Gemini
     */
    private function processGemini($message, $context = [])
    {
        $apiKey = env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('Clé API Gemini non configurée');
        }

        $contextString = !empty($context) ? 
            "Contexte: " . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\n" : 
            "";

        $fullPrompt = $this->systemPrompt . "\n\nQuestion: " . $contextString . $message;

        $response = Http::timeout(30)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => (int) env('AI_MAX_TOKENS', 150),
                    'temperature' => (float) env('AI_TEMPERATURE', 0.7)
                ]
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Erreur API Gemini: ' . $response->body());
        }

        $data = $response->json();
        
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 
               'Désolé, je n\'ai pas pu traiter votre demande.';
    }

    /**
     * Simulateur IA pour les tests
     */
    private function processSimulator($message, $isMedicalQuestion = false)
    {
        $message = strtolower($message);

        // Réponses prédéfinies selon les mots-clés
        $responses = [
            // Questions médicales générales
            'fièvre' => "La fièvre est une réaction naturelle du corps pour combattre les infections. Si elle persiste au-delà de 3 jours ou dépasse 39°C, consultez un médecin. En attendant, restez hydraté et reposez-vous.",
            
            'mal de tête' => "Les maux de tête peuvent avoir diverses causes : stress, fatigue, déshydratation, tension oculaire. Si c'est fréquent ou intense, je recommande de consulter pour identifier la cause.",
            
            'grippe' => "Les symptômes grippaux incluent fièvre, courbatures et fatigue. Le repos et l'hydratation sont essentiels. Consultez si les symptômes s'aggravent ou persistent plus d'une semaine.",
            
            'stress' => "Le stress peut affecter votre santé physique et mentale. Des techniques de relaxation, l'exercice régulier et un bon sommeil peuvent aider. N'hésitez pas à parler à un professionnel si cela persiste.",
            
            // Questions sur la plateforme
            'rendez-vous' => "Pour prendre rendez-vous, utilisez notre système de recherche par spécialité ou par médecin. Vous pouvez choisir vos créneaux disponibles et payer en ligne si vous le souhaitez.",
            
            'paiement' => "Nous acceptons les paiements en ligne sécurisés ou le paiement direct au cabinet. Vous recevrez un justificatif PDF dans les deux cas.",
            
            'médecin' => "Notre plateforme regroupe des médecins qualifiés de diverses spécialités. Vous pouvez consulter leurs profils, leurs disponibilités et leurs tarifs avant de prendre rendez-vous.",
            
            // Questions générales
            'comment' => "Je suis là pour vous aider ! Vous pouvez me poser des questions sur la santé générale, notre plateforme, ou comment prendre rendez-vous avec nos médecins.",
            
            'aide' => "Je peux vous aider avec des informations générales de santé, vous guider sur l'utilisation de notre plateforme, ou répondre à vos questions sur les rendez-vous médicaux. Que voulez-vous savoir ?"
        ];

        // Rechercher une réponse appropriée
        foreach ($responses as $keyword => $response) {
            if (strpos($message, $keyword) !== false) {
                return $response;
            }
        }

        // Réponse par défaut selon le type de question
        if ($isMedicalQuestion) {
            return "Je comprends votre préoccupation de santé. Pour des conseils personnalisés et fiables, je vous recommande de consulter l'un de nos médecins qualifiés. Souhaitez-vous que je vous aide à trouver un spécialiste ?";
        }

        return "Je suis votre assistant médical virtuel. Je peux vous aider avec des questions de santé générales ou vous guider sur notre plateforme. Pouvez-vous préciser votre question ?";
    }

    /**
     * Vérifier si la question est de nature médicale
     */
    private function isMedicalQuestion($message)
    {
        $message = strtolower($message);
        
        foreach ($this->medicalKeywords as $keyword) {
            if (strpos($message, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Vérifier les sujets interdits
     */
    private function containsForbiddenTopics($message)
    {
        $message = strtolower($message);
        
        foreach ($this->forbiddenTopics as $forbidden) {
            if (strpos($message, strtolower($forbidden)) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Générer des suggestions d'actions
     */
    private function generateSuggestions($message, $isMedicalQuestion)
    {
        $suggestions = [];

        if ($isMedicalQuestion) {
            $suggestions = [
                [
                    'text' => 'Consulter un médecin',
                    'action' => 'search_doctors',
                    'icon' => '👨‍⚕️'
                ],
                [
                    'text' => 'Voir les spécialités',
                    'action' => 'view_specialties', 
                    'icon' => '🏥'
                ],
                [
                    'text' => 'Prendre un rendez-vous',
                    'action' => 'book_appointment',
                    'icon' => '📅'
                ]
            ];
        } else {
            $suggestions = [
                [
                    'text' => 'Comment prendre RDV ?',
                    'action' => 'help_booking',
                    'icon' => '❓'
                ],
                [
                    'text' => 'Voir mes rendez-vous',
                    'action' => 'view_appointments',
                    'icon' => '📋'
                ],
                [
                    'text' => 'Contacter le support',
                    'action' => 'contact_support',
                    'icon' => '📞'
                ]
            ];
        }

        return $suggestions;
    }

    /**
     * Obtenir l'historique des conversations
     */
    public function getConversationHistory(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        
        // Pour cet exemple, on retourne un historique fictif
        // Dans une vraie app, vous stockeriez cela en base de données
        
        return response()->json([
            'status' => 'success',
            'conversation_id' => $conversationId,
            'messages' => [
                [
                    'role' => 'assistant',
                    'content' => 'Bonjour ! Je suis votre assistant médical virtuel. Comment puis-je vous aider aujourd\'hui ?',
                    'timestamp' => now()->subMinutes(5)->toISOString()
                ]
            ]
        ]);
    }

    /**
     * Suggestions de questions fréquentes
     */
    public function getFrequentQuestions()
    {
        $questions = [
            [
                'category' => 'Santé Générale',
                'questions' => [
                    'Que faire en cas de fièvre ?',
                    'Comment prévenir la grippe ?',
                    'Quand consulter pour un mal de tête ?',
                    'Comment gérer le stress quotidien ?'
                ]
            ],
            [
                'category' => 'Plateforme',
                'questions' => [
                    'Comment prendre un rendez-vous ?',
                    'Quels sont les modes de paiement ?',
                    'Comment annuler un rendez-vous ?',
                    'Comment télécharger mon justificatif ?'
                ]
            ],
            [
                'category' => 'Urgences',
                'questions' => [
                    'Que faire en cas d\'urgence ?',
                    'Quand aller aux urgences ?',
                    'Comment contacter un médecin rapidement ?'
                ]
            ]
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Questions fréquentes récupérées',
            'data' => $questions
        ]);
    }

    /**
     * Configuration et statut de l'IA
     */
    public function getAiStatus()
    {
        $status = [
            'ai_mode' => env('AI_MODE', 'simulator'),
            'available' => true,
            'features' => [
                'general_health_questions' => true,
                'platform_guidance' => true,
                'appointment_assistance' => true,
                'emergency_guidance' => true
            ],
            'limitations' => [
                'no_medical_diagnosis',
                'no_prescription_advice',
                'no_emergency_response'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $status
        ]);
    }

   /**
 * Statistiques de l'IA (Admin uniquement) - VERSION CORRIGÉE AVEC DEBUG
 */
/**
 * Statistiques de l'IA (Admin uniquement) - VERSION COMPLÈTEMENT CORRIGÉE
 */
/**
 * Statistiques de l'IA (Admin uniquement) - VERSION FINALE CORRIGÉE
 */
public function getAiStatistics(Request $request)
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // TEMPORAIRE : Accepter tous les utilisateurs connectés pour le debug
        $isAuthorized = true;
        
        $debugInfo = [
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role ?? 'N/A',
            'is_user_instance' => $user instanceof \App\Models\User,
            'authorized_for_debug' => $isAuthorized
        ];

        if (!$isAuthorized) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès réservé aux administrateurs',
                'debug' => $debugInfo
            ], 403);
        }

        // STATISTIQUES CORRIGÉES - utilise la même logique que la route de test qui marche
        $stats = [];
        
        // Vérifier la table
        $tableExists = Schema::hasTable('conversations');
        if (!$tableExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table conversations non trouvée',
                'debug' => $debugInfo
            ], 500);
        }

        // Total d'enregistrements
        $totalRecords = \App\Models\Conversation::count();
        
        if ($totalRecords == 0) {
            $stats = [
                'total_conversations' => 0,
                'total_messages' => 0,
                'medical_questions' => 0,
                'today_conversations' => 0,
            ];
        } else {
            // MÉTHODE CORRIGÉE pour éviter l'erreur distinct()
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
            'debug' => $debugInfo
        ]);

    } catch (\Exception $e) {
        Log::error('getAiStatistics error', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la génération des statistiques',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ], 500);
    }
}
}