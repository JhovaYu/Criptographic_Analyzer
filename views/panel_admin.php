<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    
    <!-- Fuentes y CSS base -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS (Estilo oscuro) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.dataTables.min.css">

    <style>
        :root {
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --danger: #ef4444;
            --success: #10b981;
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
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
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

        .navbar h2 {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(to right, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover { background-color: var(--primary-hover); }

        .btn-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .btn-danger:hover {
            background-color: var(--danger);
            color: white;
        }

        .btn-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .btn-success:hover {
            background-color: var(--success);
            color: white;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-size: 20px;
            color: var(--text-main);
        }

        /* Modificaciones para DataTables en modo oscuro */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-muted) !important;
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 6px;
            padding: 6px 10px;
        }

        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            color: var(--text-main);
        }

        table.dataTable thead th, table.dataTable thead td {
            border-bottom: 1px solid var(--border) !important;
            color: var(--text-muted);
            font-weight: 600;
        }

        table.dataTable tbody tr {
            background-color: transparent !important;
        }

        table.dataTable tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        table.dataTable tbody td {
            border-bottom: 1px solid var(--border) !important;
            padding: 12px 10px;
        }
        
        tr.dtrg-group td {
            background-color: rgba(245, 158, 11, 0.1) !important;
            color: var(--primary) !important;
            font-weight: bold;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-proximamente { background: #FEF3C7; color: #92400E; }
        .badge-en_curso { background: #D1FAE5; color: #065F46; }
        .badge-concluido { background: #F3F4F6; color: #374151; }
        .badge-deleted { background: #FEE2E2; color: #991B1B; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }

        .grouping-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .grouping-controls select {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: var(--text-main);
            border-radius: 6px;
            padding: 8px 12px;
            outline: none;
        }

        .grouping-controls select:focus {
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2>Panel de Administrador</h2>
        <div class="user-info">
            <span>Bienvenido, <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?></span>
            <a href="index.php?action=logout" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        
        <div class="card">
            <div class="card-header">
                <h3>Gestión General de Eventos</h3>
            </div>
            
            <div class="grouping-controls">
                <label style="color: var(--text-muted); align-self: center;">Agrupar por:</label>
                <select id="eventGroupBy">
                    <option value="">(Ninguno)</option>
                    <option value="4">Estatus</option>
                    <option value="6">Moderador (Creador)</option>
                    <option value="2">Fecha de Inicio (Día)</option>
                </select>
            </div>

            <table id="eventsTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estatus</th>
                        <th>Estado BD</th>
                        <th>Moderador</th>
                        <th>Código</th>
                        <th>Contraseña</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $evt): ?>
                        <tr>
                            <td><?= $evt['id'] ?></td>
                            <td><strong><?= htmlspecialchars($evt['name']) ?></strong></td>
                            <td><?= date('Y-m-d H:i', strtotime($evt['start_time'])) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($evt['end_time'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $evt['status_calc'] ?>">
                                    <?= str_replace('_', ' ', ucfirst($evt['status_calc'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($evt['is_active']): ?>
                                    <span class="badge badge-active">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-deleted">En Papelera</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($evt['creator_name']) ?></td>
                            <td><code><?= htmlspecialchars($evt['event_code']) ?></code></td>
                            <td>
                                <?php if (!empty($evt['event_password'])): ?>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input type="password" value="<?= htmlspecialchars($evt['event_password']) ?>" readonly style="background: transparent; border: none; color: var(--text-muted); width: 60px; font-size: 12px; outline: none;" class="pwd-field-<?= $evt['id'] ?>">
                                        <button type="button" onclick="const f = document.querySelector('.pwd-field-<?= $evt['id'] ?>'); f.type = f.type === 'password' ? 'text' : 'password';" style="background: transparent; border: 1px solid var(--border); color: var(--text-muted); cursor: pointer; border-radius: 4px; padding: 2px 6px; font-size: 10px;">Ver</button>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">Sin clave</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($evt['is_active']): ?>
                                    <a href="index.php?action=event&id=<?= $evt['id'] ?>" class="btn" style="padding: 4px 8px; font-size: 12px; background: var(--primary);">Entrar (Mod)</a>
                                    <a href="index.php?action=delete_event&id=<?= $evt['id'] ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" onclick="return confirm('¿Mover a papelera?');">Borrar</a>
                                <?php else: ?>
                                    <a href="index.php?action=admin_restore_event&id=<?= $evt['id'] ?>" class="btn btn-success" style="padding: 4px 8px; font-size: 12px;">Restaurar</a>
                                    <a href="index.php?action=admin_delete_event&id=<?= $evt['id'] ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;" onclick="return confirm('¡Esto eliminará el evento y sus datos permanentemente!');">Eliminar Fuerte</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Gestión de Usuarios</h3>
            </div>
            
            <table id="usersTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Fecha de Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <?php 
                                    $c = $u['role_name'] === 'Administrador' ? 'primary' : ($u['role_name'] === 'Moderador' ? 'success' : 'muted'); 
                                ?>
                                <span style="color: var(--<?= $c ?>); font-weight: 600;"><?= $u['role_name'] ?></span>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($u['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-outline" style="border:1px solid var(--border); background:transparent; color:var(--text-muted); font-size:12px; padding:4px 8px;" disabled>Próximamente</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- jQuery y DataTables Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar tabla de eventos
            var tableEvents = $('#eventsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "order": [[ 2, "desc" ]], // Ordenar por fecha inicio descendente por defecto
                "pageLength": 10,
                rowGroup: {
                    enable: false // Desactivado por defecto para evitar el desastre visual
                }
            });

            // Lógica para cambiar agrupación dinámicamente
            $('#eventGroupBy').on('change', function() {
                var val = $(this).val();
                if (val) {
                    tableEvents.rowGroup().enable().dataSrc(parseInt(val));
                    // Si agrupamos por fecha (columna 2), cortamos la fecha para agrupar solo por día
                    if(val == 2){
                        tableEvents.rowGroup().dataSrc(function(row) {
                            return row[2].split(' ')[0]; // Toma solo la parte Y-m-d
                        });
                    }
                } else {
                    tableEvents.rowGroup().disable();
                }
                tableEvents.draw();
            });

            // Inicializar tabla de usuarios
            $('#usersTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "order": [[ 4, "desc" ]], // Ordenar por fecha de registro descendente
                "pageLength": 10
            });
        });
    </script>
</body>
</html>
