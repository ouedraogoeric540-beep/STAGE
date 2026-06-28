<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket — {{ $ticket->evenement->titre }}</title>
    <style>
        @page { margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #e2e8f0;
            color: #0f172a;
            margin: 0; padding: 40px;
        }
        .ticket-wrapper {
            background: #ffffff;
            border-radius: 24px;
            width: 100%;
            max-width: 400px; /* Wallet shape */
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid #cbd5e1;
        }
        .header {
            background: #1e3a8a;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .event-title {
            font-size: 24px; font-weight: 800;
            margin-bottom: 8px;
        }
        .badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px; font-weight: 600;
            margin-top: 10px;
        }
        .body {
            padding: 30px 20px;
            text-align: center;
        }
        .owner-info {
            margin-bottom: 25px;
        }
        .owner-label {
            font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .owner-name {
            font-size: 18px; font-weight: 700; color: #0f172a;
        }
        .owner-email {
            font-size: 12px; color: #64748b;
        }
        .qr-container {
            margin-bottom: 25px;
        }
        .qr-code {
            padding: 15px; background: #ffffff; border-radius: 16px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .qr-code img {
            width: 180px; height: 180px;
        }
        .code-unique {
            margin-top: 12px; font-family: monospace; font-size: 18px;
            font-weight: bold; color: #3b82f6; letter-spacing: 3px;
        }
        .footer-info {
            background: #f8fafc;
            padding: 20px;
            border-top: 2px dashed #e2e8f0;
        }
        .row {
            width: 100%;
        }
        .col {
            display: inline-block; width: 49%; vertical-align: top;
        }
        .col-right {
            text-align: right;
        }
        .label {
            font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .value {
            font-size: 13px; font-weight: bold; color: #0f172a;
        }
    </style>
</head>
<body>
    <div style="text-align: center; padding-top: 20px;">
        <div class="ticket-wrapper" style="display: inline-block; text-align: left;">
            <!-- Header -->
            <div class="header">
                <div class="event-title">{{ $ticket->evenement->titre }}</div>
                <div class="badge">
                    {{ $ticket->categorie->nom }} &nbsp;•&nbsp; 
                    @if($ticket->prix_paye == 0) GRATUIT
                    @else {{ number_format($ticket->prix_paye, 0, ',', ' ') }} FCFA
                    @endif
                </div>
            </div>
            
            <!-- Body: QR & Owner -->
            <div class="body">
                <div class="owner-info">
                    <div class="owner-label">Billet appartenant à</div>
                    <div class="owner-name">{{ $ticket->participant->nom }}</div>
                    <div class="owner-email">{{ $ticket->participant->email }}</div>
                </div>
                
                <div class="qr-container">
                    @if(isset($qrCodeBase64))
                    <div class="qr-code">
                        <img src="{{ $qrCodeBase64 }}" alt="QR Code"/>
                    </div>
                    @endif
                    <div class="code-unique">{{ $ticket->code_unique }}</div>
                </div>
            </div>
            
            <!-- Footer: Details -->
            <div class="footer-info">
                <div class="row">
                    <div class="col">
                        <div class="label">Date & Heure</div>
                        <div class="value">{{ $ticket->evenement->date->format('d/m/Y') }}</div>
                        <div style="font-size: 12px; color: #475569;">{{ $ticket->evenement->date->format('H:i') }}</div>
                    </div>
                    <div class="col col-right">
                        <div class="label">Lieu</div>
                        <div class="value">{{ $ticket->evenement->lieu }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; font-size: 11px; color: #64748b;">
            Ticket généré le {{ $ticket->created_at->format('d/m/Y à H:i') }}<br/>
            Ce ticket est unique et nominatif. Une pièce d'identité peut vous être demandée.
        </div>
    </div>
</body>
</html>