<?php
/**
 * Design system reference (development only).
 *
 * @var array $periods  @var array $week  @var array $homework  @var array $grades
 * @var array $students @var array $stories @var list<string> $icons
 */

use App\Support\Format;
use App\Support\Labels;

$swatches = [
    ['--brand', 'Teal-i i logos', 'Logo, tituj të mëdhenj, vija, fokusi'],
    ['--brand-strong', 'Teal i fortë', 'Lidhje, butona kryesorë, tekst teal'],
    ['--brand-deep', 'Teal i thellë', 'Hover; tekst mbi teal të çelët'],
    ['--brand-tint', 'Teal i çelët', 'Sot, ora aktuale, rreshti i zgjedhur'],
    ['--ink', 'Boja', 'Tekst, seksione të errëta, shiriti anësor'],
    ['--ink-2', 'Boja 2', 'Tekst dytësor'],
    ['--ink-3', 'Boja 3', 'Meta dhe përshkrime'],
    ['--paper', 'Letra', 'Sfondi i faqeve'],
    ['--paper-2', 'Letra 2', 'Breza dhe mbushje të lehta'],
    ['--surface', 'Sipërfaqja', 'Karta, tabela, formularë'],
    ['--line', 'Vija', 'Vija të holla dhe kufij'],
    ['--line-strong', 'Vija e fortë', 'Kufijtë e fushave të formularit'],
    ['--clay', 'Terrakota', 'Theks editorial — fasada e shkollës'],
    ['--clay-strong', 'Terrakota e fortë', 'Terrakota si tekst i vogël'],
    ['--success', 'Sukses', 'Vetëm për gjendje, me fjalë ose ikonë'],
    ['--warning', 'Kujdes', 'Vetëm për gjendje, me fjalë ose ikonë'],
    ['--danger', 'Gabim', 'Vetëm për gjendje, me fjalë ose ikonë'],
];

$pairs = [
    ['--ink', '--paper', 4.5, 'Teksti kryesor mbi letër'],
    ['--ink-2', '--paper', 4.5, 'Teksti dytësor mbi letër'],
    ['--ink-3', '--paper', 4.5, 'Meta mbi letër'],
    ['--ink-3', '--surface', 4.5, 'Meta mbi karta'],
    ['--brand-strong', '--surface', 4.5, 'Lidhjet mbi karta'],
    ['--brand-strong', '--paper', 4.5, 'Lidhjet mbi letër'],
    ['--brand-deep', '--brand-tint', 4.5, 'Teksti mbi teal të çelët'],
    ['--on-ink', '--brand-strong', 4.5, 'Butoni kryesor, nota 5'],
    ['--on-ink', '--danger', 4.5, 'Butoni i fshirjes'],
    ['--on-ink', '--ink', 4.5, 'Teksti i bardhë mbi bojë'],
    ['--on-ink-2', '--ink', 4.5, 'Teksti dytësor mbi bojë'],
    ['--brand', '--ink', 4.5, 'Teal mbi bojë (shiriti anësor)'],
    ['--success', '--success-tint', 4.5, 'Statusi: sukses'],
    ['--warning', '--warning-tint', 4.5, 'Statusi: kujdes, nota 2'],
    ['--danger', '--danger-tint', 4.5, 'Statusi: gabim, nota 1'],
    ['--clay-strong', '--paper', 4.5, 'Terrakota si tekst'],
    ['--line-strong', '--surface', 3, 'Kufiri i fushave (jo tekst)'],
    ['--focus', '--paper', 3, 'Unaza e fokusit mbi letër (jo tekst)'],
    ['--focus', '--surface', 3, 'Unaza e fokusit mbi karta (jo tekst)'],
    ['--brand-strong', '--paper', 3, 'Treguesi i faqes/skedës aktive (jo tekst)'],
    ['--brand', '--surface', 3, 'Treguesi i shiritit poshtë (jo tekst)'],
];

$sections = [
    'logo' => 'Logo', 'ngjyrat' => 'Ngjyrat', 'kontrasti' => 'Kontrasti', 'tipografia' => 'Tipografia',
    'butonat' => 'Butonat', 'formularet' => 'Formularët', 'statuset' => 'Statuset dhe notat',
    'mesazhet' => 'Mesazhet', 'kartat' => 'Kartat', 'tabelat' => 'Tabelat', 'orari' => 'Orari',
    'dialogu' => 'Dialogu dhe menyja', 'editoriale' => 'Editoriale', 'ikonat' => 'Ikonat',
];

$today = 5; // the demo is set on a Friday
?>
<section class="section sg-intro">
    <div class="container">
        <p class="kicker">Vetëm për zhvillim · T03</p>
        <h1 class="display">Gjuha vizuale e shkollës</h1>
        <p class="lead sg-intro__lead">
            Çdo ngjyrë, madhësi dhe komponent i faqes dhe i portalit, në një vend.
            Gjithçka rrjedh nga logoja: një ngjyrë e sigurt, hapësirë e bardhë, vija të drejta.
        </p>
        <div class="cluster sg-intro__actions">
            <a class="btn btn--primary" href="<?= e(route('dev.portal')) ?>">Portali (shembull) <?= icon('arrow-right') ?></a>
            <a class="btn btn--secondary" href="<?= e(route('login')) ?>">Faqja e hyrjes</a>
            <a class="btn btn--quiet" href="<?= e(route('dev.system')) ?>">Gjendja e sistemit</a>
        </div>
        <nav class="sg-toc" aria-label="Seksionet">
            <?php foreach ($sections as $id => $label): ?>
                <a href="#<?= e($id) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</section>

<!-- ============================================================ LOGO -->
<section class="section section--band" id="logo">
    <div class="container">
        <header class="section-head"><h2 class="section-head__title">Logo dhe motivi</h2><span class="meta">SVG i rindërtuar nga logoja zyrtare</span></header>
        <div class="grid grid--3">
            <div class="sg-frame sg-frame--paper"><?= partial('partials/brand') ?></div>
            <div class="sg-frame sg-frame--surface"><?= partial('partials/brand') ?></div>
            <div class="sg-frame sg-frame--ink"><?= partial('partials/brand', ['variant' => 'light']) ?></div>
        </div>
        <div class="sg-marks">
            <?php foreach ([96, 56, 32, 20] as $size): ?>
                <figure>
                    <img src="<?= e(asset('img/brand/mark.svg')) ?>" alt="" width="<?= e($size) ?>" height="<?= e($size) ?>">
                    <figcaption class="meta num"><?= e($size) ?> px</figcaption>
                </figure>
            <?php endforeach; ?>
            <figure class="sg-motif-sample">
                <div class="motif" aria-hidden="true"></div>
                <figcaption class="meta">Motivi: tri vijat e logos</figcaption>
            </figure>
        </div>
    </div>
</section>

<!-- ============================================================ COLOURS -->
<section class="section" id="ngjyrat">
    <div class="container">
        <header class="section-head"><h2 class="section-head__title">Ngjyrat</h2><span class="meta">Vlerat lexohen drejtpërdrejt nga tokens.css</span></header>
        <div class="sg-swatches">
            <?php foreach ($swatches as [$token, $name, $role]): ?>
                <div class="sg-swatch" data-swatch="<?= e($token) ?>">
                    <span class="sg-swatch__chip" data-chip></span>
                    <strong><?= e($name) ?></strong>
                    <code><?= e($token) ?> · <span data-hex>…</span></code>
                    <span class="meta"><?= e($role) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tight" id="kontrasti">
    <div class="container">
        <header class="section-head">
            <h2 class="section-head__title">Kontrasti (WCAG AA)</h2>
            <span class="meta" data-contrast-summary>Duke llogaritur…</span>
        </header>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr><th scope="col">Çifti</th><th scope="col">Shembull</th><th scope="col" class="num">Raporti</th><th scope="col" class="num">Minimumi</th><th scope="col">Rezultati</th></tr></thead>
                <tbody>
                    <?php foreach ($pairs as [$fg, $bg, $min, $label]): ?>
                        <tr data-contrast data-fg="<?= e($fg) ?>" data-bg="<?= e($bg) ?>" data-min="<?= e($min) ?>">
                            <td data-label="Çifti"><span class="table__primary"><?= e($label) ?></span><span class="table__secondary"><?= e($fg) ?> mbi <?= e($bg) ?></span></td>
                            <td data-label="Shembull"><span class="sg-sample" data-sample>Aa Ëë Çç 4,25</span></td>
                            <td data-label="Raporti" class="num" data-ratio>—</td>
                            <td data-label="Minimumi" class="num"><?= e(sq_number($min, 1)) ?>:1</td>
                            <td data-label="Rezultati"><span class="badge" data-result>…</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ============================================================ TYPE -->
<section class="section section--band" id="tipografia">
    <div class="container">
        <header class="section-head"><h2 class="section-head__title">Tipografia</h2><span class="meta" data-font-status>Duke kontrolluar shkronjat…</span></header>
        <div class="sg-type">
            <div class="sg-type__row"><span class="meta">Hero · Newsreader 400</span><p class="hero__title sg-dark-text">Dija ndërtohet <em>bashkë</em>.</p></div>
            <div class="sg-type__row"><span class="meta">Display · Newsreader 400</span><p class="display">Kuvendi i Arbërit</p></div>
            <div class="sg-type__row"><span class="meta">H1 · Newsreader 500</span><h1>Dy nxënës fitojnë Hackathonin Kombëtar</h1></div>
            <div class="sg-type__row"><span class="meta">H2</span><h2>Detyrat në pritje</h2></div>
            <div class="sg-type__row"><span class="meta">H3</span><h3>Orari i së premtes</h3></div>
            <div class="sg-type__row"><span class="meta">H4 · Manrope 700</span><h4>Informacione për prindërit</h4></div>
            <div class="sg-type__row"><span class="meta">Kicker</span><p><span class="kicker">Arritje · 24 shtator 2026</span><span class="kicker kicker--clay">Në fokus</span></p></div>
            <div class="sg-type__row"><span class="meta">Lead</span><p class="lead">Portali mësimor bashkon orarin, detyrat, notat dhe njoftimet e shkollës në një vend të vetëm.</p></div>
            <div class="sg-type__row"><span class="meta">Body · Manrope 16</span><p>Nxënësit shohin detyrat e reja sapo mësimdhënësi i publikon, i dorëzojnë me skedarë dhe marrin notën dhe komentin në të njëjtin vend. Ç’ë ëshë — shkronjat shqipe shfaqen saktë.</p></div>
            <div class="sg-type__row"><span class="meta">Meta · 14</span><p class="meta">Publikuar më 24 shtator 2026 · Administrata</p></div>
            <div class="sg-type__row"><span class="meta">Shifra tabelare</span>
                <p class="num sg-tnum"><span data-tnum="1">11111</span><span data-tnum="0">00000</span> <span class="meta">— mesatarja 4,25 · 1 234 nxënës</span></p>
            </div>
            <div class="sg-type__row"><span class="meta">Prozë (artikuj) · Newsreader 19</span>
                <div class="prose">
                    <p>Ekipi i shkollës sonë kaloi tridhjetë e gjashtë orë pa gjumë, por me një ide të qartë: librat shkollorë duhet të kalojnë nga një brez nxënësish te tjetri.</p>
                    <blockquote><p>Nuk fituam ne; fitoi ideja që dija ndahet.</p></blockquote>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ BUTTONS -->
<section class="section" id="butonat">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Butonat</h2><span class="meta">Minimumi 44 px për prekje</span></header>
        <div class="cluster">
            <button class="btn btn--primary" type="button">Dorëzo detyrën</button>
            <button class="btn btn--secondary" type="button">Ndrysho</button>
            <button class="btn btn--quiet" type="button"><?= icon('plus') ?>Shto nxënës</button>
            <button class="btn btn--danger" type="button"><?= icon('trash') ?>Fshij</button>
            <button class="btn btn--primary" type="button" disabled>Joaktiv</button>
            <button class="btn btn--primary" type="button" aria-busy="true">Duke ruajtur</button>
        </div>
        <div class="cluster">
            <button class="btn btn--primary btn--lg" type="button">Hyr në portal <?= icon('arrow-right') ?></button>
            <button class="btn btn--secondary btn--sm" type="button">E vogël</button>
            <a class="link-arrow" href="#butonat">Të gjitha lajmet <?= icon('arrow-right') ?></a>
            <button class="icon-btn" type="button" aria-label="Lajmërimet"><?= icon('bell') ?><span class="count-badge" aria-hidden="true">3</span></button>
            <button class="icon-btn" type="button" aria-label="Kërko"><?= icon('search') ?></button>
        </div>
        <div class="sg-frame sg-frame--ink cluster">
            <button class="btn btn--primary" type="button">Kryesor</button>
            <button class="btn btn--on-ink" type="button">Mbi sfond të errët</button>
        </div>
    </div>
</section>

<!-- ============================================================ FORMS -->
<section class="section section--band" id="formularet">
    <div class="container">
        <header class="section-head"><h2 class="section-head__title">Formularët</h2><span class="meta">Etiketa të dukshme, gabimi pranë fushës</span></header>
        <!-- A <div>, not a <form>: this demo must never submit (the password field would end up in a URL) -->
        <div class="card form sg-form">
            <p class="form-note">Fushat me <span class="field__required">*</span> janë të detyrueshme.</p>
            <div class="form-grid form-grid--2">
                <div class="field">
                    <label class="field__label" for="sg-first">Emri<span class="field__required" aria-hidden="true">*</span></label>
                    <input class="input" id="sg-first" name="first_name" value="Arta" autocomplete="given-name" required>
                </div>
                <div class="field">
                    <label class="field__label" for="sg-email">Email-i</label>
                    <input class="input" id="sg-email" name="email" type="email" value="arta.gashi@" aria-invalid="true" aria-describedby="sg-email-error">
                    <p class="field__error" id="sg-email-error"><?= icon('alert', 'icon--sm') ?>Shkruani një adresë të vlefshme, p.sh. emri@shembull.com.</p>
                </div>
                <div class="field">
                    <label class="field__label" for="sg-class">Klasa</label>
                    <select class="select" id="sg-class" name="class">
                        <option>X-13</option><option selected>XI-5</option><option>XII-1</option>
                    </select>
                </div>
                <div class="field">
                    <label class="field__label" for="sg-password">Fjalëkalimi</label>
                    <div class="input-group">
                        <input class="input" id="sg-password" name="password" type="password" value="shembull123" autocomplete="new-password" aria-describedby="sg-password-hint">
                        <button class="icon-btn input-group__btn" type="button" data-password-toggle aria-controls="sg-password" aria-pressed="false" aria-label="Shfaq fjalëkalimin"><?= icon('eye') ?></button>
                    </div>
                    <p class="field__hint" id="sg-password-hint">Të paktën 8 karaktere.</p>
                </div>
                <div class="field">
                    <label class="field__label" for="sg-username">Emri i përdoruesit</label>
                    <input class="input" id="sg-username" value="arta.gashi" readonly>
                    <p class="field__hint">Krijohet automatikisht nga emri.</p>
                </div>
                <div class="field">
                    <label class="field__label" for="sg-disabled">Viti shkollor</label>
                    <input class="input" id="sg-disabled" value="2026/2027" disabled>
                </div>
            </div>
            <div class="field">
                <label class="field__label" for="sg-text">Përgjigja juaj</label>
                <textarea class="textarea" id="sg-text" placeholder="Shkruani përgjigjen ose një shënim për mësimdhënësin…"></textarea>
            </div>
            <label class="file-drop" for="sg-files">
                <?= icon('upload') ?>
                <strong>Zgjidhni skedarët</strong>
                <span class="meta">PDF, Word, PowerPoint, foto · deri në 10 MB secili</span>
                <input class="visually-hidden" id="sg-files" type="file" multiple>
            </label>
            <label class="check"><input type="checkbox" checked> Publikoje menjëherë për nxënësit</label>
            <div class="form-actions">
                <button class="btn btn--primary" type="button">Ruaj</button>
                <button class="btn btn--quiet" type="button">Anulo</button>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ STATUSES & GRADES -->
<section class="section" id="statuset">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Statuset dhe notat</h2><span class="meta">Ngjyra shoqërohet gjithmonë me fjalë</span></header>
        <div class="cluster">
            <span class="badge badge--info">E re</span>
            <span class="badge">E dorëzuar</span>
            <span class="badge badge--warning">Me vonesë</span>
            <span class="badge badge--warning">Kthyer për përmirësim</span>
            <span class="badge badge--success">E vlerësuar</span>
            <span class="badge badge--danger">Afati ka kaluar</span>
            <span class="badge badge--success">Aktiv</span>
            <span class="badge">Joaktiv</span>
            <span class="badge badge--ink badge--plain">XI-5</span>
        </div>
        <div class="sg-grades">
            <?php foreach (Labels::GRADES as $value => $word): ?>
                <div class="sg-grade">
                    <span class="grade grade--<?= e($value) ?>" title="<?= e($word) ?>"><?= e($value) ?></span>
                    <span><?= e($word) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="sg-grade">
                <span class="grade grade--lg grade--4">4</span>
                <span>Nota e gjysmëvjetorit</span>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ ALERTS -->
<section class="section section--band" id="mesazhet">
    <div class="container stack">
        <header class="section-head"><h2 class="section-head__title">Mesazhet</h2></header>
        <div class="alert alert--success" role="status"><?= icon('check-circle') ?><p class="alert__body">Detyra u dorëzua me sukses.</p><button type="button" class="icon-btn alert__dismiss" data-dismiss aria-label="Mbyll mesazhin"><?= icon('close', 'icon--sm') ?></button></div>
        <div class="alert" role="status"><?= icon('info') ?><p class="alert__body">Afati për dorëzim është e hënë, 5 tetor, ora 23:59.</p></div>
        <div class="alert alert--warning" role="status"><?= icon('warning') ?><p class="alert__body"><strong class="alert__title">Kthyer për përmirësim</strong>Mësimdhënësi kërkon që të plotësoni pjesën e dytë të raportit.</p></div>
        <div class="alert alert--danger" role="alert"><?= icon('alert') ?>
            <div class="alert__body"><strong class="alert__title">Formulari ka 2 gabime</strong>
                <ul><li><a href="#sg-email">Email-i nuk është i vlefshëm</a></li><li>Zgjidhni të paktën një skedar</li></ul>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ CARDS -->
<section class="section" id="kartat">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Kartat, listat, statistikat</h2></header>
        <div class="grid grid--stats">
            <div class="stat"><span class="stat__label">Nxënës aktivë</span><span class="stat__value">1 284</span><span class="stat__meta">45 klasa</span></div>
            <div class="stat"><span class="stat__label">Mësimdhënës</span><span class="stat__value">86</span><span class="stat__meta">2 ndërrime</span></div>
            <div class="stat"><span class="stat__label">Detyra këtë javë</span><span class="stat__value">132</span><span class="stat__meta">+18 nga java e kaluar</span></div>
            <div class="stat"><span class="stat__label">Mesatarja</span><span class="stat__value">4,12</span><span class="stat__meta">Gjysmëvjetori i parë</span></div>
        </div>
        <div class="grid grid--2">
            <article class="card">
                <header class="card__head"><h3 class="card__title">Detyrat në pritje</h3><a class="link-arrow" href="#kartat">Të gjitha <?= icon('arrow-right') ?></a></header>
                <ul class="item-list" role="list">
                    <?php foreach ($homework as $item): ?>
                        <li>
                            <div>
                                <span class="item__kicker"><?= e($item['subject']) ?></span>
                                <a class="item__title" href="#kartat"><?= e($item['title']) ?></a>
                                <span class="item__meta">Afati: <?= e(sq_date($item['due'], 'long')) ?> · <?= e(sq_date($item['due'], 'time')) ?></span>
                            </div>
                            <span class="badge badge--<?= e($item['status'][1]) ?>"><?= e($item['status'][0]) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <article class="card">
                <header class="card__head"><h3 class="card__title">Dorëzimet</h3><span class="meta num">24 / 31</span></header>
                <progress class="progress" value="24" max="31">24 nga 31</progress>
                <p class="meta sg-gap">24 nga 31 nxënës e kanë dorëzuar · 3 me vonesë</p>
                <div class="empty sg-gap">
                    <div class="empty__mark motif" aria-hidden="true"></div>
                    <h4 class="empty__title">Nuk keni detyra në pritje.</h4>
                    <p class="empty__text">Kur mësimdhënësit publikojnë detyra të reja, ato shfaqen këtu bashkë me afatin.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- ============================================================ TABLES -->
<section class="section section--band" id="tabelat">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Tabelat</h2><span class="meta">Në telefon rreshtat bëhen blloqe</span></header>
        <nav class="breadcrumbs" aria-label="Gjurma"><ol><li><a href="#tabelat">Paneli</a></li><li><a href="#tabelat">Nxënësit</a></li><li aria-current="page">XI-5</li></ol></nav>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr><th scope="col">Nxënësi</th><th scope="col">Klasa</th><th scope="col">Gjendja</th><th scope="col">Hyrja e fundit</th><th scope="col"><span class="visually-hidden">Veprime</span></th></tr></thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td data-label="Nxënësi"><span class="table__primary"><?= e($student['name']) ?></span><span class="table__secondary"><?= e($student['username']) ?></span></td>
                            <td data-label="Klasa"><?= e($student['class']) ?></td>
                            <td data-label="Gjendja"><span class="badge badge--<?= $student['status'] === 'active' ? 'success' : 'plain' ?>"><?= e(Labels::USER_STATUSES[$student['status']]) ?></span></td>
                            <td data-label="Hyrja e fundit" class="num"><?= $student['login'] ? e(sq_date($student['login'], 'datetime')) : '<span class="meta">Asnjëherë</span>' ?></td>
                            <td data-label="Veprime"><a class="btn btn--quiet btn--sm" href="#tabelat">Ndrysho</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <nav aria-label="Faqet">
            <ul class="pagination" role="list">
                <li><span class="is-disabled" aria-hidden="true"><?= icon('chevron-left', 'icon--sm') ?></span></li>
                <li><a href="#tabelat" aria-current="page">1</a></li>
                <li><a href="#tabelat">2</a></li>
                <li><a href="#tabelat">3</a></li>
                <li><span aria-hidden="true">…</span></li>
                <li><a href="#tabelat">12</a></li>
                <li><a href="#tabelat" aria-label="Faqja tjetër"><?= icon('chevron-right', 'icon--sm') ?></a></li>
            </ul>
        </nav>
    </div>
</section>

<!-- ============================================================ TIMETABLE -->
<section class="section" id="orari">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Orari</h2><span class="meta">Oraret nga databaza · ndërrimi i paradites</span></header>

        <div class="schedule-days">
            <div class="tabs" role="tablist" aria-label="Ditët e javës">
                <?php foreach ([1, 2, 3, 4, 5] as $day): ?>
                    <button class="tab" type="button" role="tab" id="sg-tab-<?= e($day) ?>" aria-controls="sg-day-<?= e($day) ?>"
                            aria-selected="<?= $day === $today ? 'true' : 'false' ?>" tabindex="<?= $day === $today ? '0' : '-1' ?>">
                        <?= e(Labels::DAYS_SHORT[$day]) ?><?php if ($day === $today): ?><span class="tab__note">sot</span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php foreach ([1, 2, 3, 4, 5] as $day): ?>
                <div class="tab-panel" role="tabpanel" id="sg-day-<?= e($day) ?>" aria-labelledby="sg-tab-<?= e($day) ?>" <?= $day === $today ? '' : 'hidden' ?>>
                    <?= partial('partials/timetable-day', ['periods' => $periods, 'lessons' => $week[$day] ?? [], 'current' => $day === $today ? 3 : null]) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="schedule-week">
            <?= partial('partials/timetable-week', ['periods' => $periods, 'week' => $week, 'today' => $today, 'current' => 3, 'caption' => 'Orari javor i klasës XI-5']) ?>
        </div>

        <div class="grid grid--2">
            <div class="card">
                <header class="card__head"><h3 class="card__title"><?= e(Format::ucfirst(Labels::day($today))) ?></h3><span class="meta">Pamja e një dite</span></header>
                <?= partial('partials/timetable-day', ['periods' => $periods, 'lessons' => $week[$today], 'current' => 3]) ?>
            </div>
            <div class="sg-bell">
                <h3>Orari i orëve</h3>
                <p class="meta">Dy ndërrime · gjashtë orë nga 45 minuta · pushimi i madh pas orës së 2-të dhe të 4-t</p>
                <?= partial('partials/timetable-day', ['periods' => $periods, 'lessons' => [], 'current' => null, 'compact' => true]) ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ DIALOG & MENU -->
<section class="section section--band" id="dialogu">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Dialogu dhe menyja</h2></header>
        <div class="cluster">
            <form method="post" action="<?= e(route('dev.csrf')) ?>" data-confirm="Të fshihet detyra?" data-confirm-text="Detyra “Ekuacionet kuadratike” dhe 24 dorëzimet e saj do të fshihen përgjithmonë." data-confirm-button="Fshij detyrën">
                <?= csrf_field() ?>
                <button class="btn btn--danger" type="submit"><?= icon('trash') ?>Fshij detyrën</button>
            </form>
            <details class="menu">
                <summary class="btn btn--secondary">Veprime <?= icon('chevron-down', 'icon--sm') ?></summary>
                <div class="menu__panel">
                    <a class="menu__item" href="#dialogu"><?= icon('pencil') ?>Ndrysho</a>
                    <a class="menu__item" href="#dialogu"><?= icon('download') ?>Shkarko dorëzimet</a>
                    <div class="menu__separator"></div>
                    <a class="menu__item" href="#dialogu"><?= icon('trash') ?>Fshij</a>
                </div>
            </details>
        </div>
    </div>
</section>

<!-- ============================================================ EDITORIAL -->
<section class="section" id="editoriale">
    <div class="container stack--lg">
        <header class="section-head"><h2 class="section-head__title">Lajmet e fundit</h2><a class="link-arrow" href="#editoriale">Të gjitha lajmet <?= icon('arrow-right') ?></a></header>
        <div class="stories">
            <?php foreach ($stories as $index => $story): ?>
                <?php if ($index === 0): ?>
                    <a class="story story--lead" href="#editoriale">
                        <div class="story__media"><img src="<?= e(asset('img/school.jpg')) ?>" alt="" width="640" height="480" loading="lazy"></div>
                        <span class="kicker kicker--clay"><?= e($story['category']) ?> · <?= e(sq_date($story['date'])) ?></span>
                        <h3 class="story__title"><?= e($story['title']) ?></h3>
                        <p class="story__excerpt"><?= e($story['excerpt']) ?></p>
                    </a>
                    <div class="stories__side">
                <?php else: ?>
                    <a class="story" href="#editoriale">
                        <div class="story__media story__media--placeholder" aria-hidden="true"></div>
                        <div>
                            <span class="kicker"><?= e($story['category']) ?> · <?= e(sq_date($story['date'])) ?></span>
                            <h3 class="story__title"><?= e($story['title']) ?></h3>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
                    </div>
        </div>
    </div>
</section>

<section class="section section--band">
    <div class="container stack--lg">
        <figure class="pull-quote">
            <blockquote class="pull-quote__text">Shkolla nuk është ndërtesa; janë njerëzit që mblidhen në të për të mësuar bashkë.</blockquote>
            <figcaption class="pull-quote__by">Shembull citati</figcaption>
        </figure>
        <dl class="numbers">
            <div><dt>Nxënës</dt><dd>1 284</dd></div>
            <div><dt>Klasa</dt><dd>45</dd></div>
            <div><dt>Mësimdhënës</dt><dd>86</dd></div>
            <div><dt>Lëndë</dt><dd>17</dd></div>
        </dl>
        <p class="meta">Numrat këtu janë shembuj; në faqen publike do të lexohen nga databaza.</p>
    </div>
</section>

<!-- ============================================================ ICONS -->
<section class="section" id="ikonat">
    <div class="container">
        <header class="section-head"><h2 class="section-head__title">Ikonat</h2><span class="meta num"><?= e(count($icons)) ?> ikona · një familje vijash</span></header>
        <ul class="sg-icons" role="list">
            <?php foreach ($icons as $name): ?>
                <li><?= icon($name, 'icon--lg') ?><code><?= e($name) ?></code></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
