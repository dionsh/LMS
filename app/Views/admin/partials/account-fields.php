<?php
/**
 * The form fields for adding or editing an account, by role.
 *
 * @var string                $role     student | teacher | admin
 * @var array                 $values   AccountForm values
 * @var array<string, string> $errors
 * @var array                 $classes  grouped class options (students only)
 * @var array                 $subjects subject options (teachers only)
 * @var bool                  $creating
 */

use App\Services\AccountForm;
?>
<fieldset class="form-section">
    <legend class="form-section__title">Të dhënat personale</legend>
    <div class="form-grid form-grid--2">
        <div class="field">
            <label class="field__label" for="first_name">Emri<span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" id="first_name" name="first_name" value="<?= e($values['first_name']) ?>" maxlength="60" required autocomplete="off"<?= field_invalid($errors, 'first_name') ?>>
            <?= field_error($errors, 'first_name') ?>
        </div>
        <div class="field">
            <label class="field__label" for="last_name">Mbiemri<span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" id="last_name" name="last_name" value="<?= e($values['last_name']) ?>" maxlength="60" required autocomplete="off"<?= field_invalid($errors, 'last_name') ?>>
            <?= field_error($errors, 'last_name') ?>
        </div>
        <div class="field">
            <label class="field__label" for="email">Email-i</label>
            <input class="input" id="email" name="email" type="email" value="<?= e($values['email']) ?>" autocomplete="off"
                   <?= field_invalid($errors, 'email', 'email-hint') ?: ' aria-describedby="email-hint"' ?>>
            <p class="field__hint" id="email-hint">Opsional.</p>
            <?= field_error($errors, 'email') ?>
        </div>
        <div class="field">
            <label class="field__label" for="phone">Telefoni</label>
            <input class="input" id="phone" name="phone" type="tel" value="<?= e($values['phone']) ?>" autocomplete="off"<?= field_invalid($errors, 'phone') ?>>
            <?= field_error($errors, 'phone') ?>
        </div>
    </div>
</fieldset>

<?php if ($role === 'student'): ?>
    <fieldset class="form-section">
        <legend class="form-section__title">Shkolla</legend>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label class="field__label" for="class_id">Klasa<?php if ($creating): ?><span class="field__required" aria-hidden="true">*</span><?php endif; ?></label>
                <select class="select" id="class_id" name="class_id"<?= field_invalid($errors, 'class_id') ?>>
                    <option value=""><?= $creating ? 'Zgjidhni klasën' : 'Pa klasë këtë vit' ?></option>
                    <?php foreach ($classes as $grade => $options): ?>
                        <optgroup label="Klasa <?= e($grade) ?>">
                            <?php foreach ($options as $option): ?>
                                <option value="<?= e($option['id']) ?>"<?= (string) $option['id'] === $values['class_id'] ? ' selected' : '' ?>><?= e($option['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'class_id') ?>
            </div>
            <div class="field">
                <label class="field__label" for="student_number">Numri i amzës</label>
                <input class="input" id="student_number" name="student_number" value="<?= e($values['student_number']) ?>" maxlength="30" autocomplete="off"<?= field_invalid($errors, 'student_number') ?>>
                <?= field_error($errors, 'student_number') ?>
            </div>
            <div class="field">
                <label class="field__label" for="date_of_birth">Data e lindjes</label>
                <input class="input" id="date_of_birth" name="date_of_birth" type="date" value="<?= e($values['date_of_birth']) ?>"<?= field_invalid($errors, 'date_of_birth') ?>>
                <?= field_error($errors, 'date_of_birth') ?>
            </div>
            <div class="field">
                <label class="field__label" for="gender">Gjinia</label>
                <select class="select" id="gender" name="gender"<?= field_invalid($errors, 'gender') ?>>
                    <option value="">—</option>
                    <option value="F"<?= $values['gender'] === 'F' ? ' selected' : '' ?>>Femër</option>
                    <option value="M"<?= $values['gender'] === 'M' ? ' selected' : '' ?>>Mashkull</option>
                </select>
                <?= field_error($errors, 'gender') ?>
            </div>
        </div>
    </fieldset>
<?php endif; ?>

<?php if ($role === 'teacher'): ?>
    <fieldset class="form-section">
        <legend class="form-section__title">Mësimi</legend>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label class="field__label" for="timetable_number">Numri në orar</label>
                <input class="input input--short" id="timetable_number" name="timetable_number" value="<?= e($values['timetable_number']) ?>" inputmode="numeric" maxlength="3" autocomplete="off"
                       <?= field_invalid($errors, 'timetable_number', 'timetable_number-hint') ?: ' aria-describedby="timetable_number-hint"' ?>>
                <p class="field__hint" id="timetable_number-hint">Numri me të cilin mësimdhënësi shënohet në orarin e shtypur të shkollës, p.sh. 25. Opsional.</p>
                <?= field_error($errors, 'timetable_number') ?>
            </div>
        </div>
        <fieldset class="field">
            <legend class="field__label">Lëndët që jep</legend>
            <p class="field__hint" id="subject_ids-hint">Kur i caktohet lënda një klase, këta mësimdhënës ofrohen të parët.</p>
            <div class="check-grid">
                <?php foreach ($subjects as $subject): ?>
                    <label class="check">
                        <input type="checkbox" name="subject_ids[]" value="<?= e($subject['id']) ?>"<?= in_array((int) $subject['id'], $values['subject_ids'], true) ? ' checked' : '' ?>>
                        <?= e($subject['name']) ?><?= (int) $subject['is_active'] === 0 ? ' <span class="meta">(joaktive)</span>' : '' ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?= field_error($errors, 'subject_ids') ?>
        </fieldset>
    </fieldset>

    <fieldset class="form-section">
        <legend class="form-section__title">Faqja e shkollës</legend>
        <div class="form-grid form-grid--2">
            <div class="field">
                <label class="field__label" for="title">Titulli</label>
                <input class="input" id="title" name="title" value="<?= e($values['title']) ?>" maxlength="30" list="titles" autocomplete="off"
                       <?= field_invalid($errors, 'title', 'title-hint') ?: ' aria-describedby="title-hint"' ?>>
                <datalist id="titles">
                    <?php foreach (AccountForm::TITLES as $title): ?><option value="<?= e($title) ?>"><?php endforeach; ?>
                </datalist>
                <p class="field__hint" id="title-hint">Shfaqet para emrit, p.sh. “Prof. <?= e($values['first_name'] !== '' ? $values['first_name'] : 'Enver') ?>”.</p>
                <?= field_error($errors, 'title') ?>
            </div>
            <div class="field">
                <label class="field__label" for="specialization">Fusha</label>
                <input class="input" id="specialization" name="specialization" value="<?= e($values['specialization']) ?>" maxlength="120" autocomplete="off"
                       <?= field_invalid($errors, 'specialization', 'specialization-hint') ?: ' aria-describedby="specialization-hint"' ?>>
                <p class="field__hint" id="specialization-hint">P.sh. Matematikë, Gjuhë angleze.</p>
                <?= field_error($errors, 'specialization') ?>
            </div>
        </div>
        <div class="field">
            <label class="field__label" for="bio">Përshkrim i shkurtër</label>
            <textarea class="textarea" id="bio" name="bio" maxlength="2000"<?= field_invalid($errors, 'bio') ?>><?= e($values['bio']) ?></textarea>
            <?= field_error($errors, 'bio') ?>
        </div>
        <label class="check"><input type="checkbox" name="show_on_website" value="1"<?= $values['show_on_website'] ? ' checked' : '' ?>> Shfaqe në faqen publike “Stafi”</label>
    </fieldset>
<?php endif; ?>

<?php if ($creating): ?>
    <fieldset class="form-section">
        <legend class="form-section__title">Hyrja në portal</legend>
        <label class="check"><input type="checkbox" name="issue_slip" value="1"<?= $values['issue_slip'] ? ' checked' : '' ?>> Lësho fletën e hyrjes menjëherë</label>
        <p class="field__hint">Emri i përdoruesit krijohet automatikisht nga emri. Nëse nuk e lëshoni tani, llogaria ruhet pa mundësi hyrjeje derisa ta lëshoni më vonë.</p>
    </fieldset>
<?php endif; ?>
