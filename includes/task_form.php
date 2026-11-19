<?php /* Құру және өңдеу үшін ортақ форма. Керек айнымалылар: $form, $errors, $categories, $submit_label */ ?>
<form method="post" novalidate class="card-form">
    <?= csrf_field() ?>
    <div class="field <?= isset($errors['title']) ? 'has-error' : '' ?>">
        <label for="title">Жаттығу атауы *</label>
        <input type="text" id="title" name="title" maxlength="120" value="<?= e($form['title']) ?>" required>
        <?php if (isset($errors['title'])): ?><small class="error"><?= e($errors['title']) ?></small><?php endif; ?>
    </div>
    <div class="field <?= isset($errors['description']) ? 'has-error' : '' ?>">
        <label for="description">Сипаттама</label>
        <textarea id="description" name="description" rows="4" maxlength="1000" data-counter="counter"><?= e($form['description']) ?></textarea>
        <small class="muted"><span id="counter">0</span>/1000</small>
        <?php if (isset($errors['description'])): ?><small class="error"><?= e($errors['description']) ?></small><?php endif; ?>
    </div>
    <div class="grid-3">
        <div class="field <?= isset($errors['category_id']) ? 'has-error' : '' ?>">
            <label for="category_id">Санат</label>
            <select id="category_id" name="category_id">
                <option value="">— Таңдалмаған —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (string)$form['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?><small class="error"><?= e($errors['category_id']) ?></small><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['priority']) ? 'has-error' : '' ?>">
            <label for="priority">Басымдық</label>
            <select id="priority" name="priority">
                <?php foreach (['low', 'medium', 'high'] as $p): ?>
                    <option value="<?= $p ?>" <?= $form['priority'] === $p ? 'selected' : '' ?>><?= e(priority_label($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field <?= isset($errors['due_date']) ? 'has-error' : '' ?>">
            <label for="due_date">Мерзімі</label>
            <input type="date" id="due_date" name="due_date" value="<?= e($form['due_date']) ?>">
            <?php if (isset($errors['due_date'])): ?><small class="error"><?= e($errors['due_date']) ?></small><?php endif; ?>
        </div>
    </div>
    <div class="field <?= isset($errors['duration_min']) ? 'has-error' : '' ?>" style="max-width:260px">
        <label for="duration_min">Жоспарланған уақыт (минут)</label>
        <input type="number" id="duration_min" name="duration_min" min="1" max="600" placeholder="мыс: 45" value="<?= e($form['duration_min']) ?>">
        <?php if (isset($errors['duration_min'])): ?><small class="error"><?= e($errors['duration_min']) ?></small><?php endif; ?>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= e($submit_label) ?></button>
        <a class="btn btn-outline" href="dashboard.php">Бас тарту</a>
    </div>
</form>
