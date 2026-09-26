<?php
/**
 * Page links for a list. Keeps the list's filters in every link.
 *
 * @var App\Support\Paginator $paginator
 * @var array                 $query  current filters (without the page)
 */
if ($paginator->pages() <= 1) {
    return;
}

$link = static fn (int $page): string => url(current_path(), $query + ($page > 1 ? ['faqja' => $page] : []));
$page = $paginator->page;
?>
<nav class="list-pagination" aria-label="Faqet e listës">
    <ul class="pagination" role="list">
        <li>
            <?php if ($page > 1): ?>
                <a href="<?= e($link($page - 1)) ?>" aria-label="Faqja e mëparshme"><?= icon('chevron-left', 'icon--sm') ?></a>
            <?php else: ?>
                <span class="is-disabled" aria-hidden="true"><?= icon('chevron-left', 'icon--sm') ?></span>
            <?php endif; ?>
        </li>
        <?php foreach ($paginator->window() as $number): ?>
            <li>
                <?php if ($number === null): ?>
                    <span aria-hidden="true">…</span>
                <?php elseif ($number === $page): ?>
                    <a href="<?= e($link($number)) ?>" aria-current="page"><?= e($number) ?></a>
                <?php else: ?>
                    <a href="<?= e($link($number)) ?>"><?= e($number) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <li>
            <?php if ($page < $paginator->pages()): ?>
                <a href="<?= e($link($page + 1)) ?>" aria-label="Faqja tjetër"><?= icon('chevron-right', 'icon--sm') ?></a>
            <?php else: ?>
                <span class="is-disabled" aria-hidden="true"><?= icon('chevron-right', 'icon--sm') ?></span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
