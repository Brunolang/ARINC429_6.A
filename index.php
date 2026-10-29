<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validId(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error = '';
$validationError = '';
$editingInstrument = null;
$instruments = [];

try {
    $pdo = databaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
            throw new RuntimeException('La sesión expiró. Recarga la página e inténtalo de nuevo.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save') {
            $description = trim((string) ($_POST['ins_descripcion'] ?? ''));
            $descriptionLength = function_exists('mb_strlen')
                ? mb_strlen($description, 'UTF-8')
                : strlen($description);

            if ($description === '') {
                $validationError = 'Escribe la descripción del instrumento.';
            } elseif ($descriptionLength > 50) {
                $validationError = 'La descripción no puede superar los 50 caracteres.';
            } else {
                $id = validId($_POST['ins_id'] ?? null);
                if ($id === null) {
                    $statement = $pdo->prepare(
                        'INSERT INTO instrumentos (ins_descripcion) VALUES (:descripcion)'
                    );
                    $statement->execute(['descripcion' => $description]);
                    $_SESSION['flash_message'] = 'Instrumento creado correctamente.';
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE instrumentos SET ins_descripcion = :descripcion WHERE ins_id = :id'
                    );
                    $statement->execute(['descripcion' => $description, 'id' => $id]);
                    $_SESSION['flash_message'] = 'Instrumento actualizado correctamente.';
                }

                header('Location: index.php');
                exit;
            }
        } elseif ($action === 'delete') {
            $id = validId($_POST['ins_id'] ?? null);
            if ($id === null) {
                throw new RuntimeException('El instrumento seleccionado no es válido.');
            }

            $statement = $pdo->prepare('DELETE FROM instrumentos WHERE ins_id = :id');
            $statement->execute(['id' => $id]);
            $_SESSION['flash_message'] = $statement->rowCount() > 0
                ? 'Instrumento eliminado correctamente.'
                : 'No se encontró el instrumento para eliminar.';

            header('Location: index.php');
            exit;
        }
    }

    if (isset($_GET['edit'])) {
        $editId = validId($_GET['edit']);
        if ($editId !== null) {
            $statement = $pdo->prepare(
                'SELECT ins_id, ins_descripcion FROM instrumentos WHERE ins_id = :id'
            );
            $statement->execute(['id' => $editId]);
            $editingInstrument = $statement->fetch() ?: null;
        }

        if ($editingInstrument === null) {
            $error = 'No se encontró el instrumento que quieres editar.';
        }
    }

    $instruments = $pdo->query(
        'SELECT ins_id, ins_descripcion FROM instrumentos ORDER BY ins_id DESC'
    )->fetchAll();
} catch (PDOException $exception) {
    $error = 'No se pudo conectar o consultar la base de datos. Revisa la configuración en config.php y confirma que exista la tabla instrumentos.';
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}

$formDescription = $editingInstrument['ins_descripcion'] ?? '';
$formId = $editingInstrument['ins_id'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $formDescription = trim((string) ($_POST['ins_descripcion'] ?? ''));
    $formId = validId($_POST['ins_id'] ?? null) ?? '';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f7fb">
    <title>Instrumentos | ARINC 429</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="page-shell">
        <header class="topbar">
            <a class="brand" href="index.php" aria-label="Instrumentos, inicio">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span>ARINC <strong>429</strong></span>
            </a>
            <span class="topbar-label">Panel de administración</span>
        </header>

        <section class="intro">
            <div class="eyebrow"><span class="status-dot"></span> CATÁLOGO</div>
            <h1>Instrumentos</h1>
            <p>Administra el catálogo de instrumentos de forma sencilla.</p>
        </section>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success" role="status"><?= escape((string) $message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= escape($error) ?></div>
        <?php endif; ?>
        <?php if ($validationError !== ''): ?>
            <div class="alert alert-error" role="alert"><?= escape($validationError) ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <section class="card form-card" aria-labelledby="form-title">
                <div class="card-heading">
                    <div>
                        <span class="section-kicker"><?= $formId !== '' ? 'ACTUALIZAR' : 'NUEVO REGISTRO' ?></span>
                        <h2 id="form-title"><?= $formId !== '' ? 'Editar instrumento' : 'Agregar instrumento' ?></h2>
                    </div>
                    <span class="heading-icon" aria-hidden="true">＋</span>
                </div>

                <form method="post" action="index.php" class="instrument-form">
                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="ins_id" value="<?= escape((string) $formId) ?>">

                    <label for="ins_descripcion">Descripción</label>
                    <input
                        id="ins_descripcion"
                        name="ins_descripcion"
                        type="text"
                        maxlength="50"
                        placeholder="Ej. Altímetro"
                        value="<?= escape((string) $formDescription) ?>"
                        required
                        autofocus
                    >
                    <div class="field-hint">Máximo 50 caracteres.</div>

                    <div class="form-actions">
                        <button class="button button-primary" type="submit">
                            <?= $formId !== '' ? 'Guardar cambios' : 'Crear instrumento' ?>
                        </button>
                        <?php if ($formId !== ''): ?>
                            <a class="button button-secondary" href="index.php">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="card list-card" aria-labelledby="list-title">
                <div class="card-heading list-heading">
                    <div>
                        <span class="section-kicker">REGISTROS</span>
                        <h2 id="list-title">Instrumentos registrados</h2>
                    </div>
                    <span class="count-badge"><?= count($instruments) ?> total</span>
                </div>

                <?php if ($instruments === []): ?>
                    <div class="empty-state">
                        <span class="empty-icon" aria-hidden="true">⌕</span>
                        <h3>Aún no hay instrumentos</h3>
                        <p>Agrega el primero usando el formulario.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col" class="actions-header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instruments as $instrument): ?>
                                    <tr>
                                        <td class="id-cell">#<?= escape((string) $instrument['ins_id']) ?></td>
                                        <td class="description-cell"><?= escape((string) $instrument['ins_descripcion']) ?></td>
                                        <td>
                                            <div class="row-actions">
                                                <a class="icon-button edit-button" href="?edit=<?= escape((string) $instrument['ins_id']) ?>" aria-label="Editar <?= escape((string) $instrument['ins_descripcion']) ?>" title="Editar">✎</a>
                                                <form method="post" action="index.php" onsubmit="return confirm('¿Eliminar este instrumento?');">
                                                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="ins_id" value="<?= escape((string) $instrument['ins_id']) ?>">
                                                    <button class="icon-button delete-button" type="submit" aria-label="Eliminar <?= escape((string) $instrument['ins_descripcion']) ?>" title="Eliminar">⌫</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <footer class="footer">Gestión de instrumentos <span>·</span> Base de datos ARINC 429</footer>
    </main>
</body>
</html>