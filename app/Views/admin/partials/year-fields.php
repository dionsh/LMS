<?php
/**
 * Name and dates of a school year and its two semesters (add and edit forms).
 *
 * @var array $values @var array $errors
 */

use App\Services\YearForm;

$dateField = static function (string $name, string $label) use ($values, $errors): string {
    return '<div class="field">'
        . '<label class="field__label" for="' . e($name) . '">' . e($label) . '</label>'
        . '<input class="input" id="' . e($name) . '" name="' . e($name) . '" type="date" value="' . e($values[$name]) . '"' . field_invalid($errors, $name) . '>'
        . field_error($errors, $name)
        . '</div>';
};
?>
<div class="form-grid form-grid--3">
    <div class="field">
        <label class="field__label" for="name">Viti shkollor</label>
        <input class="input" id="name" name="name" value="<?= e($values['name']) ?>" maxlength="9" placeholder="2027/2028" autocomplete="off"<?= field_invalid($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
    </div>
    <?= $dateField('starts_on', 'Fillon më') ?>
    <?= $dateField('ends_on', 'Mbaron më') ?>
</div>
<?php foreach (YearForm::TERM_NAMES as $order => $termName): ?>
    <fieldset class="field">
        <legend class="field__label"><?= e($termName) ?></legend>
        <div class="form-grid form-grid--2">
            <?= $dateField('term' . $order . '_starts_on', 'Fillon më') ?>
            <?= $dateField('term' . $order . '_ends_on', 'Mbaron më') ?>
        </div>
    </fieldset>
<?php endforeach; ?>
