<?php
declare(strict_types=1);
$title = 'Website-Darstellung';
ob_start();
?>
<div class="view-stack">
    <section class="surface">
        <header class="page-header">
            <div class="page-header__main">
                <h1 class="page-title">Website-Darstellung</h1>
                <p class="page-subtitle"><?php echo htmlspecialchars((string)$customer['name'], ENT_QUOTES); ?></p>
            </div>
            <div class="page-actions"><a class="btn btn--secondary btn--sm" href="/admin/customers/<?php echo (int)$customer['id']; ?>">Kunde</a></div>
        </header>
        <?php if ($success): ?><div class="alert alert--success"><?php echo htmlspecialchars((string)$success, ENT_QUOTES); ?></div><?php endif; ?>
        <?php foreach ($errors as $error): ?><div class="alert alert--error"><?php echo htmlspecialchars((string)$error, ENT_QUOTES); ?></div><?php endforeach; ?>
        <form method="POST" action="/admin/customers/<?php echo (int)$customer['id']; ?>/website">
            <?php echo Csrf::field(); ?>
            <div class="form-group">
                <label for="website_mode">Darstellung</label>
                <select class="input" id="website_mode" name="website_mode">
                    <option value="multipage" <?php echo $mode === 'multipage' ? 'selected' : ''; ?>>Mehrseitige Website</option>
                    <option value="onepager" <?php echo $mode === 'onepager' ? 'selected' : ''; ?>>Onepager</option>
                </select>
            </div>
            <div class="page-actions"><button class="btn btn--primary" type="submit">Speichern</button></div>
        </form>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
