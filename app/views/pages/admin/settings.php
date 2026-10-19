<?php

/**
 * @var array<string, array<string, array{0: string, 1: string}>> $fields
 * @var array<string, string> $values
 * @var list<float> $levels
 * @var list<array> $terms
 */

use App\Csrf;

?>
<?php partial('page-head', ['heading' => 'Settings', 'subtitle' => 'Every configurable number. Changes apply to new investments and future payouts.', 'icon' => 'zap']) ?>

<form method="post" action="<?= e(url('/admin/settings')) ?>" class="settings-form" novalidate data-validate
      data-confirm="Save all settings?">
    <?= Csrf::field() ?>

    <section class="card card--pad" aria-labelledby="terms-title">
        <h2 class="card__title" id="terms-title">Plan Terms (Compound and Fixed Deposit)</h2>
        <?= field_error('terms') ?>
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr><th scope="col">Days</th><th scope="col">Principal part %</th><th scope="col">Profit %</th><th scope="col">Active</th></tr></thead>
                <tbody>
                <?php foreach ($terms as $t) :
                    $base = 'terms[' . $t['id'] . ']'; ?>
                    <tr>
                        <td data-label="Days"><input class="input input--sm" name="<?= e($base) ?>[days]" type="number" min="1" step="1" required
                                   value="<?= e($t['days']) ?>" aria-label="Days for term <?= e($t['id']) ?>"></td>
                        <td data-label="Principal part %"><input class="input input--sm" name="<?= e($base) ?>[principal_rate]" type="number" min="0" max="100" step="0.001" required
                                   value="<?= e((float) $t['principal_rate']) ?>" aria-label="Principal part percent"></td>
                        <td data-label="Profit %"><input class="input input--sm" name="<?= e($base) ?>[profit_rate]" type="number" min="0" max="100" step="0.001" required
                                   value="<?= e((float) $t['profit_rate']) ?>" aria-label="Profit percent"></td>
                        <td data-label="Active"><label class="check"><input type="checkbox" name="<?= e($base) ?>[is_active]" value="1"<?= $t['is_active'] ? ' checked' : '' ?>> <span>On</span></label></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="settings-grid">
        <?php foreach ($fields as $group => $items) : ?>
            <section class="card card--pad">
                <h2 class="card__title"><?= e($group) ?></h2>
                <div class="form">
                    <?php foreach ($items as $key => [$label, $type]) :
                        $value = old($key, $values[$key] ?? ''); ?>
                        <div class="field">
                            <?php if ($type === 'bool') : ?>
                                <input type="hidden" name="<?= e($key) ?>" value="0">
                                <label class="check"><input type="checkbox" name="<?= e($key) ?>" value="1"<?= $value === '1' ? ' checked' : '' ?>> <span><?= e($label) ?></span></label>
                            <?php elseif ($type === 'period') : ?>
                                <span class="field__label"><?= e($label) ?></span>
                                <div class="choice-grid">
                                    <?php foreach (['day' => 'Day (live)', 'hour' => 'Hour (testing)'] as $opt => $optLabel) : ?>
                                        <label class="choice choice--center">
                                            <input type="radio" name="<?= e($key) ?>" value="<?= e($opt) ?>"<?= $value === $opt ? ' checked' : '' ?>>
                                            <span class="choice__title"><?= e($optLabel) ?></span>
                                        </label>
                                    <?php endforeach ?>
                                </div>
                            <?php elseif ($type === 'levels') : ?>
                                <span class="field__label"><?= e($label) ?></span>
                                <div class="level-inputs">
                                    <?php foreach ($levels as $i => $pct) : ?>
                                        <label class="level-input">
                                            <span>L<?= $i + 1 ?></span>
                                            <input class="input input--sm" name="<?= e($key) ?>[]" type="number" min="0" max="100" step="0.001" required value="<?= e($pct) ?>">
                                        </label>
                                    <?php endforeach ?>
                                </div>
                            <?php else : ?>
                                <label class="field__label" for="s-<?= e($key) ?>"><?= e($label) ?></label>
                                <input class="input<?= $type === 'address' ? ' input--mono' : '' ?>" id="s-<?= e($key) ?>" name="<?= e($key) ?>"
                                       value="<?= e($value) ?>" required aria-describedby="err-<?= e($key) ?>"<?= invalid_attr($key) ?>
                                    <?= match ($type) {
                                        'money' => 'type="number" min="0" step="0.01" inputmode="decimal"',
                                        'pct' => 'type="number" min="0" max="100" step="0.001" inputmode="decimal"',
                                        'int' => 'type="number" min="0" step="1" inputmode="numeric"',
                                        'day' => 'type="number" min="1" max="31" step="1" inputmode="numeric"',
                                        'time' => 'type="time"',
                                        'address' => 'type="text" pattern="0x[a-fA-F0-9]{40}" spellcheck="false"',
                                        default => 'type="text"',
                                    } ?>>
                            <?php endif ?>
                            <?= field_error($key) ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>

    <div class="settings-save">
        <button class="btn btn--gold btn--lg" type="submit">Save Settings</button>
    </div>
</form>
