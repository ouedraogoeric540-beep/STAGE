<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #3b82f6;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px 30px;
            color: #334155;
            line-height: 1.6;
        }
        .content h2 {
            color: #1e293b;
            margin-top: 0;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn {
            background-color: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2563eb;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
        }
        .highlight {
            color: #3b82f6;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>EventSecure</h1>
    </div>

    <div class="content">
        <h2>Bonjour {{ $user->prenom ?? $user->name }},</h2>
        
        <p>Toute l'équipe est ravie de vous compter parmi nous ! 🎉</p>
        
        <p>Votre compte <span class="highlight">EventSecure</span> a été créé avec succès. Vous êtes désormais prêt(e) à découvrir les meilleurs événements, réserver vos billets en quelques clics et vivre des expériences inoubliables.</p>

        <p>Grâce à votre nouveau compte, vous pouvez :</p>
        <ul>
            <li>Explorer le catalogue de nos événements à venir</li>
            <li>Acheter et stocker vos tickets de manière 100% sécurisée</li>
            <li>Accéder à vos billets virtuels munis de codes QR uniques</li>
        </ul>

        <div class="btn-container">
            <a href="http://localhost:5173/evenements" class="btn">Découvrir les événements</a>
        </div>

        <p>Si vous avez la moindre question, n'hésitez pas à nous contacter.</p>
        <p>À très bientôt,<br><strong>L'équipe EventSecure</strong></p>
    </div>

    <div class="footer">
        <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} EventSecure. Tous droits réservés.</p>
    </div>
</div>

</body>
</html>
