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
        .promo-box {
            background: linear-gradient(135deg, #f3edf7 0%, #e8def8 100%);
            border: 2px dashed #6650a4;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .promo-code {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #6650a4;
            background: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin: 12px 0;
            border: 1px solid #d0bcff;
        }
        .steps {
            font-size: 14px;
            color: #49454f;
            text-align: left;
            margin-top: 12px;
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
                        @if($userName)
                            <p><strong>Hola {{ $userName }},</strong></p>
                        @else
                            <p><strong>Hola,</strong></p>
                        @endif

                        <div>
                            {!! nl2br(e($supportMessage)) !!}
                        </div>

                        @if($promoCode)
                            <div class="promo-box">
                                <div style="font-weight: 600; color: #49454f; text-transform: uppercase; font-size: 13px;">Tu Código Promocional para Google Play Store</div>
                                <div class="promo-code">{{ $promoCode }}</div>
                                <div class="steps">
                                    <strong>¿Cómo canjearlo en tu dispositivo Android?</strong>
                                    <ol style="margin: 8px 0 0 20px; padding: 0;">
                                        <li>Abre la app <strong>Google Play Store</strong> en tu móvil o tablet.</li>
                                        <li>Toca en tu icono de perfil (arriba a la derecha) &gt; <em>Pagos y suscripciones</em> &gt; <em>Canjear código</em>.</li>
                                        <li>Introduce el código anterior y pulsa <em>Canjear</em>.</li>
                                        <li>Abre <strong>ScoreBox</strong> y disfruta de tu versión PRO.</li>
                                    </ol>
                                </div>
                            </div>
                        @endif

                        @php
                            $suggestionParams = [];
                            if (! empty($recipientEmail)) {
                                $suggestionParams['email'] = $recipientEmail;
                            }
                            if (! empty($userName) && $userName !== 'músico') {
                                $suggestionParams['name'] = $userName;
                            }
                            $suggestionUrl = route('suggestions.create', $suggestionParams);
                        @endphp

                        <div style="background-color: #f7f2fa; border-radius: 12px; padding: 22px 24px; margin: 28px 0; border: 1px solid #e8def8; text-align: center;">
                            <p style="margin: 0 0 8px; font-weight: 700; color: #1c1b1f; font-size: 16px;">
                                💬 ¿Quieres dejar algún comentario sobre la aplicación?
                            </p>
                            <p style="margin: 0 0 16px; color: #49454f; font-size: 14px; line-height: 1.5;">
                                Tu opinión nos ayuda a hacer de ScoreBox la mejor herramienta para músicos. Si tienes sugerencias, ideas de funciones o mejoras, nos encantará escucharte.
                            </p>
                            <a href="{{ $suggestionUrl }}"
                               style="display: inline-block; background-color: #6650a4; color: #ffffff !important; text-decoration: none; padding: 11px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 6px rgba(102, 80, 164, 0.25);">
                                Dejar comentario o sugerencia &rarr;
                            </a>
                        </div>

                        <p style="margin-top: 28px; color: #49454f;">
                            Atentamente,<br>
                            <strong>Pablo de ScoreBox</strong>
                        </p>
                    </div>
                    <div class="footer">
                        <p>Este es un mensaje directo del equipo de soporte de <strong>ScoreBox</strong>.</p>
                        <p style="margin-top: 12px; font-size: 11px; color: #7a757f;">
                            &copy; {{ date('Y') }} ScoreBox. Todos los derechos reservados.
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
