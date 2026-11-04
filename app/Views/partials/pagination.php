<?php
// app/Views/partials/pagination.php
// Expects $paginator (App\Core\Paginator).
if ($paginator->totalPages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Product pages">
    <?php if ($paginator->page > 1): ?>
        <a href="<?= page_url($paginator->page - 1) ?>" rel="prev">&larr; Prev</a>
    <?php else: ?>
        <span class="disabled">&larr; Prev</span>
    <?php endif; ?>

    <?php foreach ($paginator->window() as $p): ?>
        <?php if ($p === null): ?>
            <span class="gap">&hellip;</span>
        <?php elseif ($p === $paginator->page): ?>
            <span class="current" aria-current="page"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= page_url($p) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($paginator->page < $paginator->totalPages): ?>
        <a href="<?= page_url($paginator->page + 1) ?>" rel="next">Next &rarr;</a>
    <?php else: ?>
        <span class="disabled">Next &rarr;</span>
    <?php endif; ?>
</nav>
<div class="pagination-summary">
    Showing <?= $paginator->firstItem() ?>&ndash;<?= $paginator->lastItem() ?> of <?= $paginator->total ?> products
</div>
