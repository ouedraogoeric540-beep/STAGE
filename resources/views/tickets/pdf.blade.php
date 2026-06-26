<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket — {{ $ticket->evenement->titre }}</title>
    <style>
        @page { margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f5f8ff;
            color: #0d1b3e;
            margin: 0; padding: 40px;
        }
        .ticket {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(37,99,235,0.1);
            position: relative;
            overflow: hidden;
        }
        .header {
            background: #2563eb;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .brand {
            font-size: 24px; font-weight: bold; letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .event-title {
            font-size: 28px; font-weight: 800;
            margin-top: 15px; margin-bottom: 10px;
        }
        .event-details {
            font-size: 14px; opacity: 0.9;
        }
        .body {
            padding: 30px;
        }
        .row {
            width: 100%; margin-bottom: 20px;
        }
        .col {
            display: inline-block; width: 48%; vertical-align: top;
        }
        .label {
            font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .value {
            font-size: 16px; font-weight: bold; color: #0f172a;
        }
        .divider {
            border-top: 2px dashed #e2e8f0; margin: 30px 0;
        }
        .qr-container {
            text-align: center; margin-top: 20px;
        }
        .qr-code {
            padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px;
            display: inline-block;
        }
        .qr-code img {
            width: 150px; height: 150px;
        }
        .code-unique {
            margin-top: 10px; font-family: monospace; font-size: 22px;
            font-weight: bold; color: #2563eb; letter-spacing: 4px;
        }
        .footer {
            background: #f1f5f9; padding: 20px; text-align: center;
            font-size: 12px; color: #64748b;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <div class="brand">SECUREPASS</div>
            <div class="event-title">{{ $ticket->evenement->titre }}</div>
            <div class="event-details">
                📅 {{ $ticket->evenement->date->format('d/m/Y à H:i') }} &nbsp; | &nbsp; 📍 {{ $ticket->evenement->lieu }}
            </div>
        </div>
        <div class="body">
            <div class="row">
                <div class="col">
                    <div class="label">Participant</div>
                    <div class="value">{{ $ticket->participant->nom }}</div>
                </div>
                <div class="col">
                    <div class="label">Catégorie</div>
                    <div class="value">{{ $ticket->categorie->nom }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="label">Email</div>
                    <div class="value">{{ $ticket->participant->email }}</div>
                </div>
                <div class="col">
                    <div class="label">Prix</div>
                    <div class="value">
                        @if($ticket->prix_paye == 0) <span style="color:#10b981;">Gratuit</span>
                        @else {{ number_format($ticket->prix_paye, 0, ',', ' ') }} FCFA
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="qr-container">
                <div class="label">Votre QR Code d'accès</div>
                @if(isset($qrCodeBase64))
                <div class="qr-code">
                    <img src="{{ $qrCodeBase64 }}" alt="QR Code"/>
                </div>
                @endif
                <div class="code-unique">{{ $ticket->code_unique }}</div>
            </div>
        </div>
        <div class="footer">
            Ticket acheté le {{ $ticket->created_at->format('d/m/Y à H:i') }} - Réf: {{ $ticket->paiement->reference ?? 'N/A' }}<br/>
            Ce ticket est unique et nominatif. Une pièce d'identité peut vous être demandée.
        </div>
    </div>
</body>
</html>