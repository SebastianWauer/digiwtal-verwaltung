<?php
declare(strict_types=1);
$title = 'Pagebuilder-Bloecke';
ob_start();
?>
<div class="view-stack">
    <section class="surface">
        <header class="page-header">
            <div class="page-header__main">
                <h1 class="page-title">Pagebuilder-Bloecke</h1>
                <p class="page-subtitle"><?php echo htmlspecialchars((string)$customer['name'], ENT_QUOTES); ?></p>
            </div>
            <div class="page-actions">
                <a class="btn btn--secondary btn--sm" href="/admin/customers/<?php echo (int)$customer['id']; ?>">Kunde</a>
            </div>
        </header>
        <?php if ($success): ?>
            <div class="alert alert--success"><?php echo htmlspecialchars((string)$success, ENT_QUOTES); ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert--error"><?php echo htmlspecialchars((string)$error, ENT_QUOTES); ?></div>
        <?php endforeach; ?>
        <form method="POST" action="/admin/customers/<?php echo (int)$customer['id']; ?>/blocks">
            <?php echo Csrf::field(); ?>
            <?php foreach (['Standard', 'Kundenspezifisch'] as $group): ?>
                <?php $items = array_filter($available, static fn(array $item): bool => ($item['customer_key'] === '' ? 'Standard' : 'Kundenspezifisch') === $group); ?>
                <?php if ($items === []) continue; ?>
                <h2 class="section-title"><?php echo $group; ?></h2>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Block</th><th>Freigegeben</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $key => $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['label'], ENT_QUOTES); ?></td>
                                <td>
                                    <label class="checkbox-line">
                                        <input type="checkbox" name="blocks[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>" <?php echo in_array($key, $selected, true) ? 'checked' : ''; ?> aria-label="<?php echo htmlspecialchars($item['label'] . ' freigeben', ENT_QUOTES); ?>">
                                        <span>Aktiv</span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            <div class="page-actions"><button class="btn btn--primary" type="submit">Speichern</button></div>
        </form>
    </section>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
