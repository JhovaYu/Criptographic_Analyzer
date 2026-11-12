<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Moderador - <?= htmlspecialchars($config['event_name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap');
        
        :root {
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
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
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
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
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            width: 100%;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .actions-bar h3 {
            font-size: 24px;
            font-weight: 600;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover { background-color: var(--primary-hover); }

        .btn-danger {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            background-color: #ef4444;
            color: white;
        }

        .queue-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .queue-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s;
        }

        .queue-item:first-child {
            border: 2px solid var(--primary);
            background: rgba(139, 92, 246, 0.1);
            transform: scale(1.02);
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .item-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-muted);
            min-width: 40px;
            text-align: center;
        }

        .queue-item:first-child .item-number {
            color: var(--primary);
        }

        .item-details h4 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .item-details p {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .badge {
            background-color: #3b82f6;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 10px;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border: 1px dashed var(--border);
            border-radius: 16px;
            color: var(--text-muted);
        }

    </style>
</head>
<body>
    <nav class="navbar">
        <h2>Moderador - <?= htmlspecialchars($config['event_name']) ?></h2>
        <div class="nav-actions">
            <a href="index.php?action=dashboard" class="btn-outline">Volver al Lobby</a>
            <a href="index.php?action=logout" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <!-- Tarjeta de Info del Evento -->
        <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div>
                <h4 style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Código de Acceso</h4>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <code style="background: rgba(15, 23, 42, 0.6); padding: 8px 12px; border-radius: 6px; font-size: 16px; color: var(--primary); font-weight: bold; letter-spacing: 2px;">
                        <?= htmlspecialchars($event['event_code']) ?>
                    </code>
                    
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 11px; color: var(--text-muted); margin-bottom: 2px;">Contraseña</span>
                        <?php if (!empty($event['event_password'])): ?>
                            <div style="display: flex; gap: 5px;">
                                <input type="password" id="mod_pwd" value="<?= htmlspecialchars($event['event_password']) ?>" readonly style="background: transparent; border: none; color: var(--text-main); font-size: 14px; width: 80px; outline: none; font-family: monospace;">
                                <button onclick="const f = document.getElementById('mod_pwd'); f.type = f.type === 'password' ? 'text' : 'password';" style="background: transparent; border: 1px solid var(--border); color: var(--text-muted); border-radius: 4px; font-size: 11px; padding: 0 5px; cursor: pointer;">Ver</button>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--text-main); font-size: 14px;">(Sin contraseña)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <h3>Fila de Asistentes en Vivo</h3>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="pasarSiguiente()">Siguiente Turno</button>
                <button class="btn btn-danger" onclick="limpiarFila()">Limpiar Fila</button>
            </div>
        </div>

        <div id="queueList" class="queue-list">
            <!-- Cargando... -->
            <div class="empty-state">Cargando fila...</div>
        </div>
    </div>

    <script>
        const queueList = document.getElementById('queueList');

        function actualizarFila() {
            fetch('index.php?action=api&api_action=estado_fila')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    const fila = data.fila;
                    
                    if (fila.length === 0) {
                        queueList.innerHTML = '<div class="empty-state"><h3>La fila está vacía</h3><p>Nadie ha pedido la palabra todavía.</p></div>';
                        return;
                    }

                    let html = '';
                    fila.forEach((item, index) => {
                        const esPrimero = index === 0;
                        const btnEliminar = !esPrimero ? `<button class="btn btn-danger" onclick="eliminarTurno(${item.turno_id})" style="padding: 6px 12px; font-size: 12px;">X</button>` : '';
                        
                        let infoExtra = '';
                        if (item.custom_field_1) infoExtra += item.custom_field_1 + ' | ';
                        if (item.custom_field_2) infoExtra += item.custom_field_2;
                        
                        // Quitar separador final si queda suelto
                        infoExtra = infoExtra.replace(/ \| $/, '');

                        html += `
                        <div class="queue-item">
                            <div class="item-info">
                                <div class="item-number">${index + 1}</div>
                                <div class="item-details">
                                    <h4>${item.name} ${item.es_suplente ? '<span class="badge">Suplente</span>' : ''}</h4>
                                    <p>${infoExtra}</p>
                                </div>
                            </div>
                            <div>
                                ${esPrimero ? '<span style="color:var(--primary); font-weight:bold;">Tienen la palabra 🎙️</span>' : btnEliminar}
                            </div>
                        </div>
                        `;
                    });

                    queueList.innerHTML = html;
                })
                .catch(err => console.error(err));
        }

        function pasarSiguiente() {
            fetch('index.php?action=api&api_action=pasar_siguiente', { method: 'POST' })
                .then(() => actualizarFila());
        }

        function limpiarFila() {
            if (confirm('¿Estás seguro de que quieres eliminar a todos de la fila?')) {
                fetch('index.php?action=api&api_action=limpiar_fila', { method: 'POST' })
                    .then(() => actualizarFila());
            }
        }

        function eliminarTurno(turno_id) {
            const formData = new FormData();
            formData.append('turno_id', turno_id);

            fetch('index.php?action=api&api_action=eliminar_turno', { 
                method: 'POST',
                body: formData 
            }).then(() => actualizarFila());
        }

        // Ejecutar inmediatamente al cargar y luego cada 1.5 segundos
        actualizarFila();
        setInterval(actualizarFila, 1500);
    </script>
</body>
</html>
