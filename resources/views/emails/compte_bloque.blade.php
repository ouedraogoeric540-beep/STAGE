<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Bloqué - SecurePass</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0f172a; padding: 40px 16px; }
        .wrapper { max-width: 560px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 20px; overflow: hidden; border: 1px solid #334155; }
        .header { background: linear-gradient(135deg, #ef4444, #dc2626); padding: 36px 32px; text-align: center; }
        .shield-icon { font-size: 52px; margin-bottom: 12px; display: block; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 800; letter-spacing: 0.5px; }
        .header p { color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 6px; }
        .content { padding: 32px; }
        .greeting { color: #f1f5f9; font-size: 18px; font-weight: 700; margin-bottom: 16px; }
        .body-text { color: #94a3b8; font-size: 14px; line-height: 1.7; margin-bottom: 24px; }
        .alert-box { background: #451a1a; border: 1px solid #7f1d1d; border-left: 4px solid #ef4444; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .alert-box p { color: #fca5a5; font-size: 14px; line-height: 1.6; }
        .alert-box strong { color: #ef4444; }
        .info-grid { background: #0f172a; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #1e293b; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-size: 13px; }
        .info-value { color: #e2e8f0; font-size: 13px; font-weight: 600; }
        .tip { background: #172554; border: 1px solid #1e3a8a; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
        .tip p { color: #93c5fd; font-size: 13px; line-height: 1.6; }
        .tip p strong { color: #60a5fa; }
        .footer { background: #0f172a; padding: 20px 32px; text-align: center; }
        .footer p { color: #475569; font-size: 12px; line-height: 1.6; }
        .brand { color: #3b82f6; font-weight: 800; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div style="text-align:center; margin-bottom: 20px;">
            <span class="brand" style="font-size:20px; color:#3b82f6;">SECUREPASS</span>
        </div>
        <div class="card">
            <div class="header">
                <span class="shield-icon">🔒</span>
                <h1>Compte Temporairement Bloqué</h1>
                <p>Alerte de sécurité sur votre compte</p>
            </div>
            <div class="content">
                <p class="greeting">Bonjour {{ $user->name ?? 'Utilisateur' }},</p>
                <p class="body-text">
                    Nous avons détecté plusieurs tentatives de connexion échouées sur votre compte SecurePass. 
                    Par mesure de sécurité, votre compte a été <strong style="color:#ef4444;">temporairement bloqué</strong>.
                </p>

                <div class="alert-box">
                    <p>⚠️ <strong>3 tentatives de connexion échouées</strong> ont été enregistrées sur votre compte. 
                    Pour protéger vos données, l'accès a été suspendu pendant <strong>2 heures</strong>.</p>
                </div>

                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Compte concerné</span>
                        <span class="info-value">{{ $user->email }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Durée du blocage</span>
                        <span class="info-value">2 heures</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Déblocage automatique le</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($debloquageAt)->setTimezone('Africa/Abidjan')->format('d/m/Y à H:i') }}</span>
                    </div>
                </div>

                <div class="tip">
                    <p>💡 <strong>Ce n'était pas vous ?</strong> Si vous n'êtes pas à l'origine de ces tentatives, 
                    nous vous conseillons de modifier votre mot de passe dès que votre compte sera débloqué 
                    et de contacter notre support.</p>
                </div>

                <p class="body-text" style="font-size: 13px;">
                    Votre compte sera automatiquement débloqué sans aucune action de votre part. 
                    Si vous avez besoin d'une assistance immédiate, contactez-nous.
                </p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} <span class="brand">SecurePass</span>. Tous droits réservés.<br>
                Cet email a été envoyé automatiquement suite à une alerte de sécurité.</p>
            </div>
        </div>
    </div>
</body>
</html>
