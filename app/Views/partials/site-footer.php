<?php
/**
 * Public website footer. Contact lines appear only when the admin has
 * filled them in (Faqja e shkollës) — nothing is shown as a placeholder.
 */

use App\Support\Navigation;

$schoolName = setting('school_name', 'Gjimnazi “Kuvendi i Arbërit”');
$address = trim(setting('school_address') . (setting('school_city') !== '' ? ', ' . setting('school_city') : ''), ', ');
$phone = setting('school_phone');
$email = setting('school_email');
?>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__about">
                <?= partial('partials/brand', ['variant' => 'light']) ?>
                <p class="site-footer__statement">
                    <?= e(setting('school_tagline', 'Faqja e shkollës dhe portali mësimor i nxënësve dhe mësimdhënësve.')) ?>
                </p>
            </div>

            <nav aria-labelledby="footer-school">
                <h2 id="footer-school">Shkolla</h2>
                <ul role="list">
                    <?php foreach (Navigation::site() as $item): ?>
                        <?php if ($item['key'] !== 'home'): ?>
                            <li><a href="<?= e(url($item['path'])) ?>"><?= e($item['label']) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li><a href="<?= e(url('/pyetje-te-shpeshta')) ?>">Pyetje të shpeshta</a></li>
                </ul>
            </nav>

            <div>
                <h2>Na kontaktoni</h2>
                <ul class="site-footer__contact" role="list">
                    <?php if ($address !== ''): ?>
                        <li><?= icon('map-pin', 'icon--sm') ?><span><?= e($address) ?></span></li>
                    <?php endif; ?>
                    <?php if ($phone !== ''): ?>
                        <li><?= icon('phone', 'icon--sm') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></li>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                        <li><?= icon('mail', 'icon--sm') ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
                    <?php endif; ?>
                    <li><?= icon('message', 'icon--sm') ?><a href="<?= e(url('/kontakti')) ?>">Na shkruani</a></li>
                    <li><?= icon('lock', 'icon--sm') ?><a href="<?= e(url('/hyr')) ?>">Portali mësimor</a></li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <span>© <?= e(date('Y')) ?> <?= e($schoolName) ?>. Të gjitha të drejtat e rezervuara.</span>
            <a href="#main">Kthehu lart ↑</a>
        </div>
    </div>
</footer>
