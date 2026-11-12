// Detectamos automáticamente la URL base (ideal para MAMP con subcarpetas)
const basePath = window.location.pathname.substring(0, window.location.pathname.indexOf('/frontend'));
const API_URL = window.location.origin + basePath + '/api';

const API = {
    // Para mitigar riesgos de XSS prolongados, usamos sessionStorage (se borra al cerrar la pestaña) 
    // en lugar de localStorage, como sugiere el requerimiento de seguridad de tokens.
    setToken(token) {
        sessionStorage.setItem('jwt_token', token);
    },
    
    getToken() {
        return sessionStorage.getItem('jwt_token');
    },
    
    clearToken() {
        sessionStorage.removeItem('jwt_token');
    },
    
    async request(endpoint, method = 'GET', body = null) {
        const headers = {
            'Content-Type': 'application/json'
        };
        
        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const config = {
            method,
            headers
        };
        
        if (body) {
            config.body = JSON.stringify(body);
        }
        
        try {
            const response = await fetch(`${API_URL}${endpoint}`, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
};
