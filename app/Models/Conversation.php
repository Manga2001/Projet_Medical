<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_type',
        'user_id',
        'user_message',
        'ai_response',
        'is_medical_question',
        'ai_mode',
        'user_context',
        'suggestions',
        'session_id'
    ];

    protected $casts = [
        'user_context' => 'array',
        'suggestions' => 'array',
        'is_medical_question' => 'boolean'
    ];

    /**
     * Récupérer les conversations d'un utilisateur
     */
    public static function getUserConversations($conversationId, $limit = 50)
    {
        return self::where('conversation_id', $conversationId)
                   ->orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get()
                   ->reverse()
                   ->values();
    }

    /**
     * Sauvegarder une interaction
     */
    public static function saveInteraction($conversationId, $userMessage, $aiResponse, $context = [])
    {
        return self::create([
            'conversation_id' => $conversationId,
            'user_type' => $context['user_type'] ?? 'anonymous',
            'user_id' => $context['user_id'] ?? null,
            'user_message' => $userMessage,
            'ai_response' => $aiResponse,
            'is_medical_question' => $context['is_medical_question'] ?? false,
            'ai_mode' => $context['ai_mode'] ?? 'simulator',
            'user_context' => $context['user_context'] ?? [],
            'suggestions' => $context['suggestions'] ?? [],
            'session_id' => $context['session_id'] ?? null
        ]);
    }

    /**
     * Statistiques des conversations
     */
    public static function getStats()
    {
        return [
            'total_conversations' => self::distinct('conversation_id')->count(),
            'total_messages' => self::count(),
            'medical_questions' => self::where('is_medical_question', true)->count(),
            'today_conversations' => self::whereDate('created_at', today())->distinct('conversation_id')->count(),
            'ai_modes' => self::selectRaw('ai_mode, count(*) as count')
                             ->groupBy('ai_mode')
                             ->pluck('count', 'ai_mode')
                             ->toArray()
        ];
    }
}