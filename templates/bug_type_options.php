<?php if (isset($enableAllOption) && $enableAllOption === true): ?>
    <option value="All" <?= ($selectedType === 'All') ? 'selected="selected"' : ''; ?>>
        All
    </option>
<?php endif; ?>
<option value="Bug" <?= ($selectedType === 'Bug') ? 'selected="selected"' : ''; ?>>
    Bug
</option>
<option value="Feature/Change Request" <?= ($selectedType === 'Feature/Change Request') ? 'selected="selected"' : ''; ?>>
    Feature/Change Request
</option>
<option value="Documentation Problem" <?= ($selectedType === 'Documentation Problem') ? 'selected="selected"' : ''; ?>>
    Documentation Problem
</option>
<option value="Security" <?= ($selectedType === 'Security') ? 'selected="selected"' : ''; ?>>
    Security
</option>
