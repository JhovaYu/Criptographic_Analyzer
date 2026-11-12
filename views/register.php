<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Plataforma de Eventos</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap');
        
        :root {
            --primary: #10b981;
            --primary-hover: #059669;
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 20px;
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .form-group select option {
            background-color: var(--bg-color);
            color: var(--text-main);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .alert.error {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert.success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .footer-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link a:hover {
            color: #34d399;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Crear una cuenta</h1>
            <p>Únete a la Plataforma de Eventos</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?action=register">
            <div class="form-group">
                <label for="name">Nombre Completo</label>
                <input type="text" id="name" name="name" required autocomplete="off" placeholder="Ej. Juan Pérez">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autocomplete="off" placeholder="jperez">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="custom_field_1">Empresa / Organización (Opcional)</label>
                    <input type="text" id="custom_field_1" name="custom_field_1" placeholder="Ej. ACME Corp">
                </div>

                <div class="form-group">
                    <label for="custom_field_2">Cargo (Opcional)</label>
                    <input type="text" id="custom_field_2" name="custom_field_2" placeholder="Ej. Gerente">
                </div>
            </div>

            <button type="submit" name="register" class="btn-submit">Registrar Cuenta</button>
        </form>

        <div class="footer-link">
            ¿Ya tienes una cuenta? <a href="index.php?action=login">Inicia sesión aquí</a>
        </div>
    </div>
</body>
</html>
