<?php
// app/Views/home/error.php
?>
<div style="max-width: 600px; margin: 40px auto; text-align: center;">
    <div class="card" style="padding: 40px 24px;">
        <div style="font-size: 48px; font-weight: 800; color: var(--danger); margin-bottom: 8px;">
            <?= e($statusCode ?? 500) ?>
        </div>
        <h1 style="font-size: 22px; font-weight: 700; margin-bottom: 12px;"><?= e($title ?? 'An Error Occurred') ?></h1>
        <p style="color: var(--text-secondary); font-size: 15px; margin-bottom: 24px;">
            <?= e($message ?? 'Something went wrong while processing your request.') ?>
        </p>

        <?php if (!empty($trace) && (getenv('APP_ENV') !== 'production')): ?>
            <pre style="text-align: left; background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm); font-size: 11px; overflow-x: auto; margin-bottom: 20px;"><?= e($trace) ?></pre>
        <?php endif; ?>

        <div style="display: flex; justify-content: center; gap: 12px;">
            <a href="<?= url('home') ?>" class="btn btn-primary">Return to Storefront</a>
            <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
        </div>
    </div>
</div>
