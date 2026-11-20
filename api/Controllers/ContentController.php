<?php
namespace Controllers;

use PDO;
use Helpers\DocumentCrypto;

class ContentController {
    private $conn;
    public function __construct() {
        $this->conn = (new \Config\Database())->getConnection();
    }
    private function logAudit($userId, $action, $details = null) {
        $stmt = $this->conn->prepare('INSERT INTO audit_logs (user_id, action, ip_address, details) VALUES (:user_id, :action, :ip_address, :details)');
        $stmt->execute([':user_id' => $userId, ':action' => $action, ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', ':details' => $details ? json_encode($details) : null]);
    }
    public function getDocuments($userId) {
        $stmt = $this->conn->query('SELECT d.id, d.title, d.content, d.crypto_version, d.signature, d.created_at, u.name AS author FROM documents d LEFT JOIN users u ON d.created_by = u.id ORDER BY d.id DESC');
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($documents as &$doc) {
            if ((int)$doc['crypto_version'] === 1) {
                try {
                    $doc['content'] = DocumentCrypto::decrypt($doc['content']);
                    $doc['signature_status'] = DocumentCrypto::verify($doc['title'], $doc['content'], $doc['signature']) ? 'valid' : 'invalid';
                } catch (\Throwable $e) {
                    error_log('Documento ' . $doc['id'] . ': ' . $e->getMessage());
                    $doc['content'] = null;
                    $doc['signature_status'] = 'error';
                }
            } else {
                $doc['signature_status'] = 'legacy';
            }
            unset($doc['signature'], $doc['crypto_version']);
        }
        unset($doc);
        $this->logAudit($userId, 'Consultó los documentos');
        echo json_encode(['data' => $documents]);
    }
    public function createDocument($userId, $data) {
        $title = trim((string)($data->title ?? ''));
        $content = (string)($data->content ?? '');
        if ($title === '' || trim($content) === '' || preg_match_all('/./us', $title) === false || preg_match_all('/./us', $title) > 255) {
            http_response_code(400);
            echo json_encode(['message' => 'El título (máximo 255 caracteres) y el contenido son obligatorios.']);
            return;
        }
        try {
            $ciphertext = DocumentCrypto::encrypt($content);
            $signature = DocumentCrypto::sign($title, $content);
            $stmt = $this->conn->prepare('INSERT INTO documents (title, content, created_by, crypto_version, signature) VALUES (:title, :content, :created_by, 1, :signature)');
            $stmt->execute([':title' => $title, ':content' => $ciphertext, ':created_by' => $userId, ':signature' => $signature]);
            $this->logAudit($userId, 'Creó un nuevo documento', ['title' => $title]);
            http_response_code(201);
            echo json_encode(['message' => 'Documento cifrado y firmado correctamente.']);
        } catch (\Throwable $e) {
            error_log('Error al crear documento: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['message' => 'No se pudo guardar el documento. Revisa la configuración criptográfica y la base de datos.']);
        }
    }
    public function updateDocument($userId, $docId, $data) {
        $title = trim((string)($data->title ?? ''));
        $content = (string)($data->content ?? '');
        if ($title === '' || trim($content) === '' || preg_match_all('/./us', $title) === false || preg_match_all('/./us', $title) > 255) {
            http_response_code(400);
            echo json_encode(['message' => 'El título (máximo 255 caracteres) y el contenido son obligatorios.']);
            return;
        }
        try {
            $ciphertext = DocumentCrypto::encrypt($content);
            $signature = DocumentCrypto::sign($title, $content);
            $stmt = $this->conn->prepare('UPDATE documents SET title = :title, content = :content, crypto_version = 1, signature = :signature WHERE id = :id RETURNING id');
            $stmt->execute([':title' => $title, ':content' => $ciphertext, ':signature' => $signature, ':id' => $docId]);
            if ($stmt->fetchColumn() === false) {
                http_response_code(404);
                echo json_encode(['message' => 'Documento no encontrado.']);
                return;
            }
            $this->logAudit($userId, 'Actualizó un documento', ['doc_id' => (int)$docId, 'title' => $title]);
            echo json_encode(['message' => 'Documento actualizado, re-cifrado y firmado correctamente.']);
        } catch (\Throwable $e) {
            error_log('Error al actualizar documento: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['message' => 'No se pudo actualizar el documento.']);
        }
    }
    public function deleteDocument($userId, $docId) {
        $stmt = $this->conn->prepare('DELETE FROM documents WHERE id = :id RETURNING title');
        $stmt->execute([':id' => $docId]);
        if ($stmt->fetchColumn() === false) {
            http_response_code(404);
            echo json_encode(['message' => 'Documento no encontrado.']);
            return;
        }
        $this->logAudit($userId, 'Eliminó un documento', ['doc_id' => $docId]);
        echo json_encode(['message' => 'Documento eliminado correctamente.']);
    }
}
