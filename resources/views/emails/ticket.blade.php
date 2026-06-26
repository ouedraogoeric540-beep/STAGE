<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre ticket SecurePass</title>
    <style>
        /* Styles de base pour l'affichage et la compatibilité des clients mail */
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
            border: 1px solid #e2e8f0;
        }
        /* En-tête */
        .email-header {
            background-color: #0f172a; 
            padding: 30px;
            text-align: center;
        }
        .brand-logo {
            color: #10b981; 
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        /* Corps de l'e-mail */
        .email-body {
            padding: 40px 30px;
        }
        h1 {
            color: #0f172a;
            font-size: 22px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 15px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin-top: 0;
            margin-bottom: 20px;
        }
        /* Encadré Récapitulatif */
        .order-summary {
            background-color: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #3b82f6; 
        }
        .order-title {
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 12px;
            font-size: 16px;
        }
        .order-details {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
        }
        .order-details strong {
            color: #0f172a;
        }
        /* Bouton d'action principale */
        .btn-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn-action {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 6px;
            font-size: 15px;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        /* Instructions de sécurité */
        .security-notice {
            font-size: 13px;
            color: #64748b;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 15px;
            border-radius: 6px;
            line-height: 1.5;
        }
        /* Pied de page */
        .email-footer {
            background-color: #f8fafc;
            padding: 25px 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="email-wrapper">
        <div class="email-container">
            
            <div class="email-header">
                <span class="brand-logo">🛡️ SecurePass</span>
            </div>

            <div class="email-body">
                <h1>Merci pour votre confiance, {{ $ticket->participant->nom }} !</h1>
                <p>Votre paiement a été validé avec succès. Vous trouverez votre ticket d'accès officiel au format PDF en pièce jointe de cet e-mail.</p>
                
                <div class="order-summary">
                    <div class="order-title">🎟️ Récapitulatif de votre réservation :</div>
                    <div class="order-details">
                        <strong>Événement :</strong> {{ $ticket->evenement->titre }}<br>
                        <strong>Date :</strong> {{ $ticket->evenement->date->format('d/m/Y à H:i') }}<br>
                        <strong>Lieu :</strong> {{ $ticket->evenement->lieu }}<br>
                        <strong>Catégorie :</strong> {{ $ticket->categorie->nom }}<br>
                        <strong>Numéro de Billet :</strong> {{ $ticket->code_unique }}
                    </div>
                </div>

                <p>Afin de faciliter votre entrée le jour de l'événement, veuillez télécharger le document joint sur votre smartphone ou l'imprimer.</p>

                <div class="btn-container">
                    <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/mes-tickets" class="btn-action">Accéder à mes tickets</a>
                </div>

                <div class="security-notice">
                    <strong>🔒 Consigne importante :</strong> Le QR Code présent sur votre ticket est unique et strictement personnel. Ne le partagez avec personne et ne le publiez pas sur les réseaux sociaux sous peine d'annulation de votre droit d'accès.
                </div>
            </div>

            <div class="email-footer">
                <p style="margin: 0 0 5px 0;">&copy; {{ date('Y') }} SecurePass. Tous droits réservés.</p>
                <p style="margin: 0;">Cet e-mail est automatique, merci de ne pas y répondre. Pour toute assistance, contactez le support.</p>
            </div>

        </div>
    </div>

</body>
</html>