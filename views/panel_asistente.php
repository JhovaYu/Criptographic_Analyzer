<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Asistente - <?= htmlspecialchars($config['event_name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');
        
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
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
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
        }

        .btn-outline {
            background-color: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .btn-logout {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background-color: #ef4444;
            color: white;
        }

        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
        }

        .user-name {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(to right, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info-text {
            color: var(--text-muted);
            margin-bottom: 30px;
            font-size: 15px;
        }

        .btn-pedir {
            width: 100%;
            padding: 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        }

        .btn-pedir:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-pedir:active {
            transform: translateY(2px);
            box-shadow: none;
        }
        
        .btn-pedir:disabled {
            background-color: #475569;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .status-container {
            display: none;
            margin-top: 25px;
            padding: 20px;
            border-radius: 15px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-container.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        .status-title {
            color: #10b981;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .status-posicion {
            font-size: 48px;
            font-weight: 800;
            color: white;
        }
        
        .turno-ahora {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.4);
        }
        .turno-ahora .status-title { color: #f59e0b; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Estilos del formulario de edición */
        .form-edit {
            text-align: left;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid var(--border);
            display: none; /* Se oculta si está en la fila */
        }
        
        .form-edit.visible {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 14px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .checkbox-wrapper label {
            margin: 0;
            font-size: 14px;
        }

        .btn-save {
            width: 100%;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .alert {
            padding: 10px;
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
        
        .badge {
            background-color: #ef4444;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            vertical-align: middle;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2><?= htmlspecialchars($config['event_name']) ?></h2>
        <div class="nav-actions">
            <a href="index.php?action=dashboard" class="btn-outline">Volver al Lobby</a>
            <a href="index.php?action=logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <?php if (!empty($message)): ?>
                <div class="alert <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <h1 class="user-name">
                <?= htmlspecialchars($user_info['name']) ?>
                <?= $user_info['es_suplente'] ? '<span class="badge">Suplente</span>' : '' ?>
            </h1>
            
            <p class="user-info-text">
                <?php 
                    $extras = [];
                    if ($config['field1_active'] && !empty($user_info['custom_field_1'])) $extras[] = htmlspecialchars($user_info['custom_field_1']);
                    if ($config['field2_active'] && !empty($user_info['custom_field_2'])) $extras[] = htmlspecialchars($user_info['custom_field_2']);
                    echo !empty($extras) ? implode(" | ", $extras) : 'Bienvenido a la sesión';
                ?>
            </p>

            <!-- Botón Inicial -->
            <button id="btnPedir" class="btn-pedir" onclick="pedirPalabra()">
                ✋ Pedir la palabra
            </button>

            <!-- Estado de Fila -->
            <div id="statusFila" class="status-container">
                <div id="statusTitle" class="status-title">Estás en la fila. Tu turno es el número:</div>
                <div id="statusPosicion" class="status-posicion">--</div>
            </div>

            <!-- Formulario de edición (Se oculta al estar en la fila) -->
            <div id="formEditContainer" class="form-edit visible">
                <h4 style="margin-bottom: 15px; color: var(--text-main); font-weight: 600;">Editar mi información</h4>
                <form method="POST" action="index.php?action=event&id=<?= $_SESSION['current_event_id'] ?>">
                    <div class="form-group">
                        <label for="name">Nombre Completo</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_info['name']) ?>" required>
                    </div>

                    <?php if ($config['field1_active']): ?>
                    <div class="form-group">
                        <label for="custom_field_1"><?= htmlspecialchars($config['field1_name']) ?></label>
                        <input type="text" id="custom_field_1" name="custom_field_1" value="<?= htmlspecialchars($user_info['custom_field_1'] ?? '') ?>">
                    </div>
                    <?php endif; ?>

                    <?php if ($config['field2_active']): ?>
                    <div class="form-group">
                        <label for="custom_field_2"><?= htmlspecialchars($config['field2_name']) ?></label>
                        <input type="text" id="custom_field_2" name="custom_field_2" value="<?= htmlspecialchars($user_info['custom_field_2'] ?? '') ?>">
                    </div>
                    <?php endif; ?>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="es_suplente" name="es_suplente" <?= $user_info['es_suplente'] ? 'checked' : '' ?>>
                        <label for="es_suplente">Soy un suplente (representando a otra persona)</label>
                    </div>

                    <button type="submit" name="update_profile" class="btn-save">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Lógica AJAX en tiempo real
        const btnPedir = document.getElementById('btnPedir');
        const statusFila = document.getElementById('statusFila');
        const statusPosicion = document.getElementById('statusPosicion');
        const statusTitle = document.getElementById('statusTitle');
        const formEditContainer = document.getElementById('formEditContainer');

        function actualizarEstado() {
            fetch('index.php?action=api&api_action=estado_asistente')
                .then(response => response.json())
                .then(data => {
                    if (data.en_fila) {
                        // Está en la fila: ocultar botón y formulario
                        btnPedir.style.display = 'none';
                        formEditContainer.classList.remove('visible');
                        statusFila.classList.add('active');
                        
                        if (data.posicion === 1) {
                            statusFila.classList.add('turno-ahora');
                            statusTitle.innerText = "🎙️ ¡ES TU TURNO AHORA MISMO!";
                            statusPosicion.innerText = "1";
                        } else {
                            statusFila.classList.remove('turno-ahora');
                            statusTitle.innerText = "Estás en espera. Tu posición es:";
                            statusPosicion.innerText = data.posicion;
                        }
                    } else {
                        // NO está en la fila: mostrar botón y formulario
                        btnPedir.style.display = 'block';
                        statusFila.classList.remove('active');
                        formEditContainer.classList.add('visible');
                        btnPedir.disabled = false;
                        btnPedir.innerText = "✋ Pedir la palabra";
                    }
                })
                .catch(err => console.error("Error consultando estado:", err));
        }

        function pedirPalabra() {
            btnPedir.disabled = true;
            btnPedir.innerText = "Enviando solicitud...";
            
            fetch('index.php?action=api&api_action=pedir_palabra', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        actualizarEstado(); // Forzar actualización inmediata
                    } else {
                        alert(data.message);
                        btnPedir.disabled = false;
                        btnPedir.innerText = "✋ Pedir la palabra";
                    }
                });
        }

        // Ejecutar inmediatamente al cargar y luego cada 2 segundos
        actualizarEstado();
        setInterval(actualizarEstado, 2000);
    </script>
</body>
</html>
