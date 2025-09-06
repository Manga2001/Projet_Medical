@extends('emails.layout')

@section('content')
    <div class="greeting">
        Bonjour {{ $patient->first_name }},
    </div>
    
    <div class="alert alert-warning">
        <strong>🔔 RAPPEL : Votre rendez-vous dans {{ $hoursUntil }} heure{{ $hoursUntil > 1 ? 's' : '' }}</strong>
        <br>N'oubliez pas votre consultation prévue demain !
    </div>
    
    <div class="info-card">
        <h3>📅 Détails de votre rendez-vous</h3>
        <div class="info-row">
            <span class="info-label">Date et heure :</span>
            <span class="info-value"><strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Référence :</span>
            <span class="info-value">{{ $appointment->reference }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Médecin :</span>
            <span class="info-value">{{ $doctor->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Spécialité :</span>
            <span class="info-value">{{ $specialty->name }}</span>
        </div>
    </div>
    
    <div class="info-card">
        <h3>📍 Lieu du rendez-vous</h3>
        <div class="info-row">
            <span class="info-label">Adresse :</span>
            <span class="info-value">{{ $doctor->address }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Ville :</span>
            <span class="info-value">{{ $doctor->city }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ $doctor->phone }}</span>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong>📝 Rappel important :</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Arrivez 15 minutes avant l'heure prévue</li>
            <li>Munissez-vous de votre pièce d'identité</li>
            <li>Apportez vos anciens examens médicaux</li>
            <li>Préparez la liste de vos médicaments actuels</li>
        </ul>
    </div>
    
    @if($appointment->payment_status === 'pending')
    <div class="alert alert-warning">
        <strong>💰 Paiement en attente</strong>
        <br>N'oubliez pas d'apporter {{ number_format($appointment->amount, 0, ',', ' ') }} FCFA pour régler votre consultation.
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <p style="font-size: 18px; color: #2c5aa0; font-weight: 600;">
            ⏰ Rendez-vous dans {{ $hoursUntil }}h !
        </p>
        
        <a href="{{ url('/api/pdf/appointments/' . $appointment->id . '/receipt') }}" class="button">
            📄 Télécharger mon justificatif
        </a>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <h4 style="margin: 0 0 10px 0; color: #dc3545;">🚨 En cas d'empêchement</h4>
        <p style="margin: 0; font-size: 14px; color: #666;">
            Si vous ne pouvez pas vous rendre à votre rendez-vous, merci de contacter le cabinet au {{ $doctor->phone }} 
            ou notre service client pour reprogrammer.
        </p>
    </div>
@endsection