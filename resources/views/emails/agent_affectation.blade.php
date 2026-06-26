<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Affectation - SecurePass</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f5f8ff; color: #0f172a; margin: 0; padding: 40px; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(37,99,235,0.1); overflow: hidden; }
        .header { background: #2563eb; color: #fff; padding: 30px; text-align: center; }
        .brand { font-size: 24px; font-weight: 900; letter-spacing: 1px; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 20px; font-weight: 700; margin-bottom: 20px; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-top: 24px; }
        .event-title { font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 12px; }
        .info { font-size: 15px; color: #475569; margin-bottom: 8px; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="brand">SECUREPASS</div>
        </div>
        <div class="content">
            <div class="greeting">Bonjour {{ $agent->name ?? 'Agent' }},</div>
            <p>Vous avez été affecté à un nouvel événement par l'organisateur. Veuillez vous préparer à scanner les tickets pour cet événement :</p>
            
            <div class="card">
                <div class="event-title">{{ $evenement->titre }}</div>
                <div class="info">📅 Date : {{ \Carbon\Carbon::parse($evenement->date)->format('d/m/Y à H:i') }}</div>
                <div class="info">📍 Lieu : {{ $evenement->lieu }}</div>
            </div>
            
            <p style="margin-top: 24px; font-size: 14px; color: #64748b;">Connectez-vous à votre espace agent le jour J pour ouvrir le scanner.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SecurePass. Tous droits réservés.
        </div>
    </div>
</body>
</html>
