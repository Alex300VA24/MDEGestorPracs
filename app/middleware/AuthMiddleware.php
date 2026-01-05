<?php
// app/Middleware/AuthMiddleware.php
namespace App\Middleware;

use App\Core\Request;

class AuthMiddleware {
    public function handle(Request $request) {
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            return false;
        }
        
        return true;
    }
}