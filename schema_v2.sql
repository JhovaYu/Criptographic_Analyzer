-- Creación de tabla roles
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Insertar los roles predeterminados
INSERT INTO roles (name, description) VALUES 
('Administrador', 'Tiene acceso completo a todas las funcionalidades.'), 
('Editor', 'Puede crear, editar y eliminar contenidos, pero no puede gestionar usuarios ni configuraciones globales.'), 
('Usuario Regular', 'Solo puede ver y consumir contenido.') 
ON CONFLICT DO NOTHING;

-- Creación de tabla permisos
CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Insertar permisos básicos
INSERT INTO permissions (name, description) VALUES 
('read_content', 'Acceso a visualizar recursos.'),
('write_content', 'Acceso para crear o modificar recursos.'),
('delete_content', 'Permiso para eliminar recursos.'),
('manage_users', 'Permiso para gestionar usuarios.'),
('manage_roles', 'Permiso para gestionar roles y permisos.')
ON CONFLICT DO NOTHING;

-- Relación Roles <-> Permisos (muchos a muchos)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission_id INT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);

-- Asignar permisos iniciales a los roles
-- Administrador: todos los permisos
-- Editor: read_content, write_content, delete_content
-- Usuario Regular: read_content
-- (Las asignaciones exactas se pueden hacer en un seeder posterior)

-- Creación de tabla usuarios
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Relación Usuarios <-> Roles (un usuario puede tener múltiples roles)
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id INT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, role_id)
);

-- Creación de tabla para el registro de auditoría con trazabilidad
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL, -- Puede ser null si es una acción anónima
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL, -- IPv4 o IPv6
    details JSONB, -- Detalles adicionales de la acción (opcional)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    -- Nota: Al ser una tabla de auditoría inmodificable, NO se deben hacer UPDATE o DELETE.
);
