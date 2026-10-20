<?php
/**
 * @var int      $page
 * @var int      $pages
 * @var callable $pageUrl fn (int $n): string
 */
if ($pages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Paginação">
    <?php if ($page > 1): ?>
        <a href="<?= e($pageUrl($page - 1)) ?>" class="page-link" aria-label="Página anterior"><i class="fa-solid fa-chevron-left"></i></a>
    <?php endif; ?>
    <?php for ($n = 1; $n <= $pages; $n++): ?>
        <?php if ($n === 1 || $n === $pages || abs($n - $page) <= 1): ?>
            <a href="<?= e($pageUrl($n)) ?>" class="page-link<?= $n === $page ? ' is-active' : '' ?>" <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
        <?php elseif (abs($n - $page) === 2): ?>
            <span class="page-gap">…</span>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
        <a href="<?= e($pageUrl($page + 1)) ?>" class="page-link" aria-label="Próxima página"><i class="fa-solid fa-chevron-right"></i></a>
    <?php endif; ?>
</nav>
