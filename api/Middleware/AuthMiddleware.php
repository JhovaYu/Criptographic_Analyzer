<?php
namespace Middleware;

use Helpers\JwtHelper;

class AuthMiddleware {
    public static function authenticate() {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $authHeader = $headers['Authorization']
            ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(["message" => "No autorizado. Token no proporcionado o inválido."]);
            exit;
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(["message" => "No autorizado. Token inválido o expirado."]);
            exit;
        }

        return $payload; // Retornamos los datos del usuario logueado
    }

    public static function authorize($userPayload, $requiredPermission) {
        // En un caso real, aquí consultaríamos la BD para verificar si el usuario tiene el permiso.
        // Por simplicidad, si los permisos vienen en el JWT, validamos contra eso:
        if (!isset($userPayload['permissions']) || !in_array($requiredPermission, $userPayload['permissions'])) {
            http_response_code(403);
            echo json_encode(["message" => "Prohibido. No tiene los permisos necesarios."]);
            exit;
        }
    }
}
