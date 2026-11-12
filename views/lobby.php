<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby de Eventos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        h1 {
            margin: 0;
            font-size: 2rem;
            color: var(--text-main);
        }
        .btn {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background: var(--primary-hover);
        }
        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border);
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 8px;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .event-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .event-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
        }
        .event-card p {
            margin: 0 0 1rem 0;
            color: var(--text-muted);
            font-size: 0.875rem;
            flex-grow: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            align-self: flex-start;
        }
        .status-proximamente { background: #FEF3C7; color: #92400E; }
        .status-en_curso { background: #D1FAE5; color: #065F46; }
        .status-concluido { background: rgba(255, 255, 255, 0.1); color: var(--text-muted); }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']); ?></h1>
            <a href="index.php?action=logout" class="btn btn-outline">Cerrar Sesión</a>
        </div>

        <?php if (!empty($lobby_error)): ?>
            <div class="alert"><?php echo htmlspecialchars($lobby_error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Unirse a un Evento</h2>
            <form action="index.php?action=join_event" method="POST" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label for="event_code">Código del Evento</label>
                    <input type="text" id="event_code" name="event_code" required placeholder="Ej: EVT-123">
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                    <label for="event_password">Contraseña (Opcional)</label>
                    <input type="password" id="event_password" name="event_password" placeholder="Solo si aplica">
                </div>
                <button type="submit" class="btn" style="height: 44px;">Unirse</button>
            </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2>Tus Eventos</h2>
            <?php if ($_SESSION['role_id'] == 2): // Moderador ?>
                <a href="index.php?action=create_event" class="btn">Crear Evento</a>
            <?php endif; ?>
        </div>

        <?php if (empty($events)): ?>
            <div class="card" style="text-align: center; border-style: dashed; background: transparent;">
                <p style="color: var(--text-muted);">No tienes eventos asignados ni has creado ninguno.</p>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $evt): ?>
                    <div class="event-card">
                        <?php 
                            $badgeClass = "status-" . $evt['status'];
                            $statusLabel = str_replace('_', ' ', ucfirst($evt['status']));
                        ?>
                        <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusLabel; ?></span>
                        <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
                        <p><?php echo htmlspecialchars($evt['description']); ?></p>
                        <p style="font-size: 0.75rem; margin-bottom: 1rem; color: var(--text-main);">
                            Inicio: <?php echo date('d/m/Y H:i', strtotime($evt['start_time'])); ?><br>
                            Fin: <?php echo date('d/m/Y H:i', strtotime($evt['end_time'])); ?>
                        </p>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="index.php?action=event&id=<?php echo $evt['id']; ?>" class="btn" style="flex: 1; text-align: center;">Entrar</a>
                            <?php if ($_SESSION['role_id'] == 2 && $evt['creator_id'] == $_SESSION['user_id']): ?>
                                <a href="index.php?action=delete_event&id=<?php echo $evt['id']; ?>" class="btn btn-outline" style="border-color: rgba(239, 68, 68, 0.4); color: #f87171;" onclick="return confirm('¿Seguro que deseas eliminar este evento?');">Borrar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
