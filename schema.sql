-- Creación de tabla roles
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Insertar los roles básicos
INSERT INTO roles (name) VALUES ('Administrador'), ('Moderador'), ('Asistente') ON CONFLICT DO NOTHING;

-- Creación de tabla usuarios
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL REFERENCES roles(id),
    name VARCHAR(100) NOT NULL,
    custom_field_1 VARCHAR(150),
    custom_field_2 VARCHAR(150),
    es_suplente BOOLEAN DEFAULT FALSE,
    session_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Creación de tabla eventos
CREATE TABLE IF NOT EXISTS events (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    creator_id INT NOT NULL REFERENCES users(id),
    event_code VARCHAR(50) NOT NULL UNIQUE,
    event_password VARCHAR(255),
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    status VARCHAR(20) DEFAULT 'proximamente', -- 'proximamente', 'en_curso', 'concluido' (Manual override opcional)
    is_active BOOLEAN DEFAULT TRUE, -- Soft delete para moderadores
    field1_active BOOLEAN DEFAULT TRUE,
    field1_name VARCHAR(100) DEFAULT 'Organización/Empresa',
    field2_active BOOLEAN DEFAULT TRUE,
    field2_name VARCHAR(100) DEFAULT 'Cargo/Puesto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Creación de tabla event_participants (para historial de quién asistió)
CREATE TABLE IF NOT EXISTS event_participants (
    id SERIAL PRIMARY KEY,
    event_id INT NOT NULL REFERENCES events(id),
    user_id INT NOT NULL REFERENCES users(id),
    role_in_event VARCHAR(20) NOT NULL, -- 'Moderador', 'Asistente'
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, user_id)
);

-- Creación de tabla de turnos (la cola)
CREATE TABLE IF NOT EXISTS turnos (
    id SERIAL PRIMARY KEY,
    event_id INT NOT NULL REFERENCES events(id),
    user_id INT NOT NULL REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'espera', -- Puede ser: 'espera', 'hablando', 'finalizado'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
