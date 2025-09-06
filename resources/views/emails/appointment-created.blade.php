@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $patient->first_name }},
    </div>
    
    <p>Votre demande de rendez-vous a été enregistrée avec succès !</p>
    
    <div class="alert alert-info">
        <strong>📅 Votre rendez-vous est en attente de confirmation par le médecin.</strong>
        <br>Vous recevrez un email de confirmation dès que le médecin aura validé votre demande.
    </div>
    
    <div class="info-card">
        <h3>📋 Détails du rendez-vous</h3>
        <div class="info-row">
            <span class="info-label">Référence :</span>
            <span class="info-value"><strong>{{ $appointment->reference }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date et heure :</span>
            <span class="info-value">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Motif :</span>
            <span class="info-value">{{ $appointment->reason }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut :</span>
            <span class="info-value">🟡 {{ $appointment->status_label }}</span>
        </div>
    </div>
    
    <div class="info-card">
        <h3>👨‍⚕️ Informations médecin</h3>
        <div class="info-row">
            <span class="info-label">Médecin :</span>
            <span class="info-value">{{ $doctor->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Spécialité :</span>
            <span class="info-value">{{ $specialty->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Adresse :</span>
            <span class="info-value">{{ $doctor->address }}, {{ $doctor->city }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ $doctor->phone }}</span>
        </div>
    </div>
    
    <div class="info-card">
        <h3>💰 Informations de paiement</h3>
        <div class="info-row">
            <span class="info-label">Montant :</span>
            <span class="info-value"><strong>{{ number_format($appointment->amount, 0, ',', ' ') }} FCFA</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Mode de paiement :</span>
            <span class="info-value">
                @if($appointment->payment_method === 'online')
                    💳 Paiement en ligne
                @else
                    💵 Paiement au cabinet
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut paiement :</span>
            <span class="info-value">
                @if($appointment->payment_status === 'paid')
                    ✅ Payé
                @else
                    ⏳ {{ ucfirst($appointment->payment_status) }}
                @endif
            </span>
        </div>
    </div>
    
    @if($appointment->payment_method === 'online' && $appointment->payment_status === 'pending')
    <div class="alert alert-warning">
        <strong>⚠️ Paiement requis</strong>
        <br>Pour confirmer définitivement votre rendez-vous, veuillez effectuer le paiement en ligne.
    </div>
    
    <div style="text-align: center;">
        <a href="{{ url('/appointments/' . $appointment->id . '/payment') }}" class="button button-success">
            💳 Effectuer le paiement
        </a>
    </div>
    @endif
    
    <div style="margin-top: 30px;">
        <p><strong>Que faire maintenant ?</strong></p>
        <ul style="padding-left: 20px;">
            <li>Attendez la confirmation du médecin (sous 24h généralement)</li>
            @if($appointment->payment_method === 'online' && $appointment->payment_status === 'pending')
            <li>Effectuez le paiement si ce n'est pas déjà fait</li>
            @endif
            <li>Notez bien la date et l'heure de votre rendez-vous</li>
            <li>Préparez vos documents médicaux si nécessaire</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/appointments/' . $appointment->id) }}" class="button">
            📋 Voir mes rendez-vous
        </a>
    </div>
    
    <p style="margin-top: 20px; font-style: italic; color: #666;">
        Si vous avez des questions, n'hésitez pas à nous contacter ou à contacter directement le cabinet médical.
    </p>
@endsection