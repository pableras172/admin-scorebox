<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buzón de Sugerencias - ScoreBox</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f6f4f9 0%, #ebe6f3 100%);
            color: #1c1b1f;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .card {
            background: #ffffff;
            width: 100%;
            max-width: 580px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(102, 80, 164, 0.12);
            overflow: hidden;
            border: 1px solid #e7e0ec;
        }
        .header {
            background-color: #6650a4;
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header img {
            max-width: 220px;
            height: auto;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .header p {
            font-size: 14px;
            color: #e8def8;
        }
        .body {
            padding: 32px 28px;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #49454f;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            font-size: 15px;
            border: 1.5px solid #cac4d0;
            border-radius: 10px;
            background-color: #fdfcff;
            color: #1c1b1f;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        input:focus,
        select:focus,
        textarea:focus {
            border-color: #6650a4;
            box-shadow: 0 0 0 3px rgba(102, 80, 164, 0.15);
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        .btn-submit {
            width: 100%;
            background-color: #6650a4;
            color: #ffffff;
            border: none;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-submit:hover {
            background-color: #55408a;
        }
        .btn-submit:active {
            transform: scale(0.99);
        }
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #7a757f;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            @if(file_exists(public_path('images/headerMail.png')))
                <img src="{{ asset('images/headerMail.png') }}" alt="ScoreBox">
            @endif
            <h1>Buzón de Sugerencias</h1>
            <p>Tu opinión nos ayuda a hacer de ScoreBox la mejor app para músicos</p>
        </div>

        <div class="body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('suggestions.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="type">Tipo de aportación *</label>
                    <select name="type" id="type" required>
                        <option value="idea" {{ old('type') === 'idea' ? 'selected' : '' }}>💡 Idea o nueva funcionalidad</option>
                        <option value="bug" {{ old('type') === 'bug' ? 'selected' : '' }}>🐛 Reportar un fallo o problema</option>
                        <option value="scores_request" {{ old('type') === 'scores_request' ? 'selected' : '' }}>🎼 Petición de partituras / catálogo</option>
                        <option value="usability" {{ old('type') === 'usability' ? 'selected' : '' }}>⚡ Rendimiento o facilidad de uso</option>
                        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>💬 Otro comentario</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Tu Correo Electrónico *</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="tuemail@ejemplo.com" required>
                </div>

                <div class="form-group">
                    <label for="name">Tu Nombre (Opcional)</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Ej: Pablo">
                </div>

                <div class="form-group">
                    <label for="subject">Título o Asunto breve</label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}" placeholder="Ej: Añadir metrónomo visual con flash">
                </div>

                <div class="form-group">
                    <label for="message">Mensaje o Detalle *</label>
                    <textarea name="message" id="message" placeholder="Cuéntanos con detalle tu idea o el problema que has experimentado..." required>{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="btn-submit">Enviar Sugerencia</button>
            </form>

            <div class="footer">
                &copy; {{ date('Y') }} ScoreBox · MyMusicalScores. Gracias por ayudarnos a mejorar.
            </div>
        </div>
    </div>
</body>
</html>
