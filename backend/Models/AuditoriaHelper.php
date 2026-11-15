<?php
// backend/Models/AuditoriaHelper.php

function auditar(string $accion, string $entidad, int $entidadId): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) {
        return; // Si no hay usuario en sesión, no audita o maneja un sistema de consola
    }

    global $pdo; // Asumiendo conexión PDO global o disponible en el ámbito
    if (!isset($pdo)) {
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO registro_auditoria (usuario_id, accion, entidad, entidad_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuarioId, $accion, $entidad, $entidadId]);
    } catch (Exception $e) {
        // Manejar o registrar error de auditoría si es necesario
    }
}