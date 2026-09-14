<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desuscripción completada - ScoreBox</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f6f4f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1c1b1f;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(102, 80, 164, 0.08);
            max-width: 480px;
            width: 90%;
            padding: 40px 32px;
            text-align: center;
            border: 1px solid #e7e0ec;
        }
        .logo-img {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(102, 80, 164, 0.15);
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1c1b1f;
            margin: 0 0 12px 0;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #49454f;
            margin: 0 0 20px 0;
        }
        .email-badge {
            display: inline-block;
            background-color: #eaddff;
            color: #4f378b;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            word-break: break-all;
        }
        .resubscribe-btn {
            background-color: #6650a4;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        .resubscribe-btn:hover {
            background-color: #4f378b;
        }
        .resubscribe-btn:active {
            transform: scale(0.98);
        }
        .footer-note {
            margin-top: 28px;
            font-size: 13px;
            color: #625b71;
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/logo.png') }}" alt="ScoreBox Logo" class="logo-img">
        <h1>Te has dado de baja correctamente</h1>
        <p>
            No volverás a recibir novedades ni correos promocionales de <strong>ScoreBox</strong> en la dirección:
        </p>
        <p>
            <span class="email-badge">{{ $email }}</span>
        </p>
        <p class="footer-note">
            ¿Ha sido un error? Puedes reactivar tus notificaciones cuando quieras:
        </p>
        <form action="{{ $resubscribeUrl }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="resubscribe-btn">
                Volver a suscribirme
            </button>
        </form>
    </div>
</body>
</html>
