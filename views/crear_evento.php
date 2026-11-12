<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evento</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing variables and some layout */
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 600px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        h1 { margin-top: 0; font-size: 1.5rem; color: var(--text-main); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-muted); }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 8px;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .row {
            display: flex; gap: 1rem;
        }
        .row .form-group { flex: 1; }
        .btn {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            width: 100%;
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            transition: background 0.2s;
        }
        .btn:hover { background: var(--primary-hover); }
        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border);
            margin-top: 0.5rem;
        }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.05); }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .checkbox-group input { width: auto; accent-color: var(--primary); }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;
        }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Nuevo Evento</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="index.php?action=create_event" method="POST">
            <div class="form-group">
                <label for="name">Nombre del Evento *</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>

            <div class="row">
                <div class="form-group">
                    <label for="event_password">Contraseña del Evento (Opcional)</label>
                    <input type="text" id="event_password" name="event_password" placeholder="Deja en blanco para no pedir contraseña">
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label for="start_time">Fecha de Inicio *</label>
                    <input type="datetime-local" id="start_time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label for="end_time">Fecha de Fin *</label>
                    <input type="datetime-local" id="end_time" name="end_time" required>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0;">
            <h3 style="color: var(--text-main);">Campos Personalizados (Opcional)</h3>

            <div class="row">
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="field1_active" name="field1_active" checked>
                        <label for="field1_active" style="margin:0;">Activar Campo 1</label>
                    </div>
                    <input type="text" name="field1_name" value="Organización/Empresa" placeholder="Nombre del campo 1">
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="field2_active" name="field2_active" checked>
                        <label for="field2_active" style="margin:0;">Activar Campo 2</label>
                    </div>
                    <input type="text" name="field2_name" value="Cargo/Puesto" placeholder="Nombre del campo 2">
                </div>
            </div>

            <button type="submit" class="btn">Crear Evento</button>
            <a href="index.php?action=dashboard" class="btn btn-outline">Cancelar</a>
        </form>
    </div>
</body>
</html>
