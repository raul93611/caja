<?php
// ============================================================
// api.php — AJAX backend
// ============================================================
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Requiere sesión activa
if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uid    = (int)$_SESSION['usuario_id'];
$db     = getDB();

switch ($action) {

    // ── Obtener categorías por tipo ──────────────────────────
    case 'categorias':
        $tipo = $_POST['tipo'] ?? '';
        if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
            echo json_encode(['ok' => false, 'error' => 'Tipo inválido.']);
            exit;
        }
        $stmt = $db->prepare('SELECT id, nombre FROM categorias WHERE tipo = ? ORDER BY nombre');
        $stmt->execute([$tipo]);
        echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Obtener todas las categorías ─────────────────────────
    case 'categorias_todas':
        $stmt = $db->query('SELECT id, nombre, tipo FROM categorias ORDER BY tipo, nombre');
        echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Obtener una transacción para editar ──────────────────
    case 'obtener':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT id, tipo, categoria_id, monto, cantidad, detalles, fecha FROM transacciones WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $uid]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['ok' => false, 'error' => 'Transacción no encontrada.']);
            exit;
        }
        echo json_encode(['ok' => true, 'data' => $row]);
        break;

    // ── Actualizar transacción ───────────────────────────────
    case 'actualizar':
        $id       = (int)($_POST['id'] ?? 0);
        $tipo     = $_POST['tipo']         ?? '';
        $catId    = (int)($_POST['categoria_id'] ?? 0);
        $monto    = $_POST['monto']        ?? '';
        $cantidad = $_POST['cantidad']     ?? '';
        $detalles = trim($_POST['detalles'] ?? '');
        $fecha    = $_POST['fecha']        ?? '';

        // Validaciones
        if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
            echo json_encode(['ok' => false, 'error' => 'Tipo inválido.']); exit;
        }
        $montoF = filter_var(str_replace(',', '.', $monto), FILTER_VALIDATE_FLOAT);
        if (!$montoF || $montoF <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Monto inválido.']); exit;
        }
        $cantidadF = filter_var(str_replace(',', '.', $cantidad), FILTER_VALIDATE_FLOAT);
        if (!$cantidadF || $cantidadF <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Cantidad inválida.']); exit;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['ok' => false, 'error' => 'Fecha inválida.']); exit;
        }

        // Verificar que la categoría pertenece al tipo
        $stmtCat = $db->prepare('SELECT id FROM categorias WHERE id = ? AND tipo = ?');
        $stmtCat->execute([$catId, $tipo]);
        if (!$stmtCat->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'Categoría inválida.']); exit;
        }

        // Verificar que la transacción pertenece al usuario
        $stmtChk = $db->prepare('SELECT id FROM transacciones WHERE id = ? AND usuario_id = ?');
        $stmtChk->execute([$id, $uid]);
        if (!$stmtChk->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'No autorizado.']); exit;
        }

        $stmt = $db->prepare('
            UPDATE transacciones
            SET tipo = ?, categoria_id = ?, monto = ?, cantidad = ?, detalles = ?, fecha = ?
            WHERE id = ? AND usuario_id = ?
        ');
        $stmt->execute([$tipo, $catId, round($montoF, 2), round($cantidadF, 3), $detalles ?: null, $fecha, $id, $uid]);
        echo json_encode(['ok' => true]);
        break;

    // ── Eliminar transacción ─────────────────────────────────
    case 'eliminar':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID inválido.']); exit;
        }

        // Solo puede eliminar sus propias transacciones
        $stmt = $db->prepare('DELETE FROM transacciones WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $uid]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'No se encontró la transacción.']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción desconocida.']);
}
