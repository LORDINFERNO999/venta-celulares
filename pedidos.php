<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requiere_login();
$yo = usuario_actual();

$ESTADOS = ['PENDIENTE', 'APROBADA', 'ENTREGADA', 'RECHAZADA', 'EXPIRADA', 'ERROR'];

$aviso = '';
$errorBD = '';

// ---- Acción: el admin actualiza el estado de un pedido ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && es_admin()) {
    $ref = $_POST['reference_code'] ?? '';
    $nuevo = $_POST['estado'] ?? '';
    if ($ref && in_array($nuevo, $ESTADOS, true)) {
        try {
            getDB()->prepare('UPDATE ordenes SET estado = ? WHERE reference_code = ?')
                   ->execute([$nuevo, $ref]);
            $aviso = "Pedido {$ref} actualizado a {$nuevo}.";
        } catch (Throwable $e) {
            $errorBD = 'No se pudo actualizar el pedido.';
        }
    }
    // Evita reenvío al recargar
    if (!$errorBD) {
        header('Location: pedidos.php' . (isset($_GET['estado']) ? '?estado=' . urlencode($_GET['estado']) : ''));
        exit;
    }
}

// ---- Filtro por estado ----
$filtro = strtoupper($_GET['estado'] ?? '');
if (!in_array($filtro, $ESTADOS, true)) {
    $filtro = '';
}

// ---- Traer pedidos ----
$pedidos = [];
$resumen = ['total' => 0, 'aprobadas' => 0, 'ingresos' => 0.0];

try {
    $pdo = getDB();

    // Resumen general (sin filtro)
    $r = $pdo->query("SELECT
            COUNT(*) AS total,
            SUM(estado IN ('APROBADA','ENTREGADA')) AS aprobadas,
            SUM(CASE WHEN estado IN ('APROBADA','ENTREGADA') THEN valor ELSE 0 END) AS ingresos
        FROM ordenes")->fetch();
    if ($r) {
        $resumen['total']     = (int) $r['total'];
        $resumen['aprobadas'] = (int) $r['aprobadas'];
        $resumen['ingresos']  = (float) $r['ingresos'];
    }

    // Listado (con filtro opcional)
    $sql = 'SELECT o.*, p.nombre AS producto_nombre, p.marca AS producto_marca
            FROM ordenes o
            LEFT JOIN productos p ON p.id = o.producto_id';
    $params = [];
    if ($filtro) {
        $sql .= ' WHERE o.estado = ?';
        $params[] = $filtro;
    }
    $sql .= ' ORDER BY o.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();
} catch (Throwable $e) {
    $errorBD = 'No pudimos leer los pedidos. Revisa que exista la tabla "ordenes" (usa database.sql).';
}

function badgeClase(string $estado): string
{
    return match ($estado) {
        'APROBADA', 'ENTREGADA' => 'ok',
        'RECHAZADA', 'EXPIRADA', 'ERROR' => 'no',
        default => 'pend',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos · Panel</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">

<header class="adm-top">
  <div class="adm-brand">signal<span>.</span>store <em>· pedidos</em></div>
  <div class="adm-user">
    <span><?= htmlspecialchars($yo['nombre']) ?> <b class="rol"><?= htmlspecialchars($yo['rol']) ?></b></span>
    <a href="index.php" class="adm-link">Tienda</a>
    <a href="logout.php" class="adm-link salir">Salir</a>
  </div>
</header>

<main class="adm-main">

  <?php if ($aviso): ?><div class="adm-aviso ok"><?= htmlspecialchars($aviso) ?></div><?php endif; ?>
  <?php if ($errorBD): ?><div class="adm-aviso no"><?= htmlspecialchars($errorBD) ?></div><?php endif; ?>

  <section class="adm-cards">
    <div class="adm-metric">
      <span class="lbl">Pedidos totales</span>
      <span class="val"><?= (int) $resumen['total'] ?></span>
    </div>
    <div class="adm-metric">
      <span class="lbl">Pagados / entregados</span>
      <span class="val"><?= (int) $resumen['aprobadas'] ?></span>
    </div>
    <div class="adm-metric">
      <span class="lbl">Ingresos confirmados</span>
      <span class="val">$<?= number_format($resumen['ingresos'], 0, ',', '.') ?></span>
    </div>
  </section>

  <div class="adm-filtros">
    <a href="pedidos.php" class="chip <?= $filtro === '' ? 'activo' : '' ?>">Todos</a>
    <?php foreach ($ESTADOS as $e): ?>
      <a href="pedidos.php?estado=<?= $e ?>" class="chip <?= $filtro === $e ? 'activo' : '' ?>"><?= $e ?></a>
    <?php endforeach; ?>
  </div>

  <div class="adm-tabla-wrap">
    <table class="adm-tabla">
      <thead>
        <tr>
          <th>Referencia</th>
          <th>Producto</th>
          <th>Comprador</th>
          <th>Valor</th>
          <th>Estado</th>
          <?php if (es_admin()): ?><th>Acción</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
          <tr><td colspan="<?= es_admin() ? 6 : 5 ?>" class="vacio">Aún no hay pedidos<?= $filtro ? ' con estado ' . htmlspecialchars($filtro) : '' ?>.</td></tr>
        <?php else: foreach ($pedidos as $o): ?>
          <tr>
            <td class="mono"><?= htmlspecialchars($o['reference_code'] ?? '—') ?><?php if (!empty($o['created_at'])): ?><small class="fecha"><?= htmlspecialchars($o['created_at']) ?></small><?php endif; ?></td>
            <td>
              <b><?= htmlspecialchars(trim(($o['producto_marca'] ?? '') . ' ' . ($o['producto_nombre'] ?? '')) ?: 'Producto #' . ($o['producto_id'] ?? '?')) ?></b>
            </td>
            <td><?= htmlspecialchars($o['comprador_email'] ?? '—') ?></td>
            <td class="mono">$<?= number_format((float) ($o['valor'] ?? 0), 0, ',', '.') ?></td>
            <td><span class="badge <?= badgeClase($o['estado'] ?? '') ?>"><?= htmlspecialchars($o['estado'] ?? '—') ?></span></td>
            <?php if (es_admin()): ?>
              <td>
                <form method="post" class="form-estado">
                  <input type="hidden" name="reference_code" value="<?= htmlspecialchars($o['reference_code'] ?? '') ?>">
                  <select name="estado" onchange="this.form.submit()">
                    <?php foreach ($ESTADOS as $e): ?>
                      <option value="<?= $e ?>" <?= ($o['estado'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</main>

</body>
</html>
