<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f6f4f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1c1b1f;
            -webkit-text-size-adjust: 100%;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e7e0ec;
            margin-top: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(102, 80, 164, 0.08);
        }
        .header-banner {
            background-color: #6650a4;
            line-height: 0;
            font-size: 0;
            text-align: center;
            overflow: hidden;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        .header-image {
            width: 100% !important;
            max-width: 600px !important;
            height: auto !important;
            display: block !important;
            border: 0;
            outline: none;
            margin: 0 auto;
        }
        .content {
            padding: 36px 32px;
            font-size: 16px;
            line-height: 1.65;
            color: #1c1b1f;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 16px;
        }
        .content h1, .content h2, .content h3 {
            color: #1c1b1f;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        .content a {
            color: #6650a4;
            font-weight: 600;
            text-decoration: underline;
        }
        .content a.button-link,
        .content .button-link {
            display: inline-block !important;
            background-color: #6650a4 !important;
            color: #ffffff !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            padding: 12px 24px !important;
            border-radius: 8px !important;
            box-shadow: 0 3px 8px rgba(102, 80, 164, 0.25) !important;
            margin: 10px 0 6px !important;
            text-align: center !important;
        }
        .content img {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 8px;
            display: block;
            margin: 16px auto;
        }
        .content blockquote {
            margin: 16px 0;
            padding: 12px 18px;
            background-color: #f8f5fb;
            border-left: 4px solid #6650a4;
            border-radius: 0 8px 8px 0;
            color: #49454f;
        }
        .footer {
            background-color: #f8f5fa;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #625b71;
            border-top: 1px solid #e7e0ec;
        }
        .footer p {
            margin: 4px 0;
        }
        .footer a {
            color: #625b71;
            text-decoration: underline;
        }
        .footer .unsubscribe-link {
            display: inline-block;
            margin-top: 8px;
            color: #b3261e;
            font-weight: 600;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f6f4f9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <div class="container">
                    <div class="header-banner" style="background-color: #6650a4; line-height: 0; font-size: 0; text-align: center; overflow: hidden; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                        <img src="{{ isset($message) ? $message->embed(public_path('images/headerMail.png')) : asset('images/headerMail.png') }}"
                             alt="ScoreBox"
                             class="header-image"
                             style="width: 100% !important; max-width: 600px !important; height: auto !important; display: block !important; border: 0; outline: none; margin: 0 auto;"
                             width="600">
                    </div>
                    <div class="content">
                        {!! \App\Models\MarketingCampaign::processContentForEmail($content, $message ?? null) !!}
                    </div>
                    <div class="footer">
                        <p>Has recibido este correo electrónico porque eres usuario registrado en la aplicación <strong>ScoreBox</strong>.</p>
                        <p>Si ya no deseas recibir novedades, avisos ni comunicaciones promocionales:</p>
                        <p>
                            <a href="{{ $unsubscribeUrl }}" class="unsubscribe-link" target="_blank">
                                Darse de baja de estas comunicaciones
                            </a>
                        </p>
                        <p style="margin-top: 16px; font-size: 11px; color: #7a757f;">
                            &copy; {{ date('Y') }} ScoreBox. Todos los derechos reservados.
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
