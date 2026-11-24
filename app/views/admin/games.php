<?php $f = $form ?? ($edit ? array_merge($edit, ['starts_at' => to_local($edit['starts_at'])->format('Y-m-d\TH:i')]) : ['starts_at' => now_local()->setTime(19, 0)->format('Y-m-d\TH:i')]); ?>
<h1>Games schedule</h1><p class="muted">The games you’re showing appear on the website’s home page and Games page.</p>
<form method="post" action="/admin/games" class="card form" style="margin-bottom:22px"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <h2><?= $edit ? 'Edit game' : 'Add a game' ?></h2>
  <div class="grid g4">
    <div class="field"><label for="g-league">League</label><select id="g-league" name="league"><?php foreach (LEAGUES as $k => $l): ?><option value="<?= $k ?>"<?= ($f['league'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
    <div class="field"><label for="g-away">Away team</label><input id="g-away" name="away" value="<?= e($f['away'] ?? '') ?>"></div>
    <div class="field"><label for="g-home">Home team</label><input id="g-home" name="home" value="<?= e($f['home'] ?? '') ?>"></div>
    <div class="field"><label for="g-start">Starts</label><input id="g-start" name="starts_at" type="datetime-local" value="<?= e($f['starts_at'] ?? '') ?>"></div>
    <div class="field"><label for="g-screens">Screens</label><input id="g-screens" name="screens" value="<?= e($f['screens'] ?? '') ?>" placeholder="e.g. Main bar, Patio"></div>
    <div class="field" style="grid-column:span 2"><label for="g-note">Note <span class="hint">(optional)</span></label><input id="g-note" name="note" value="<?= e($f['note'] ?? '') ?>" placeholder="e.g. $5 pints during the game"></div>
    <div class="checks" style="align-self:end"><label class="check"><input type="checkbox" name="sound" value="1"<?= !empty($f['sound']) ? ' checked' : '' ?>>Sound on</label><label class="check"><input type="checkbox" name="featured" value="1"<?= !empty($f['featured']) ? ' checked' : '' ?>>Featured</label></div>
  </div>
  <div class="acts"><button class="btn primary">Save game</button><?php if ($edit): ?><a class="btn ghost" href="/admin/games">Cancel</a><?php endif ?></div>
</form>
<?php if (!$games): ?><div class="empty">No games in the next 30 days.</div><?php else: ?>
<div class="tblwrap"><table class="tbl"><thead><tr><th>When</th><th>Game</th><th>Screens</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($games as $g): ?><tr><td><b><?= e(game_day_label($g['starts_at'])) ?></b> <?= e(fmt_time($g['starts_at'])) ?></td><td><?= e($g['league']) ?> · <?= e($g['away']) ?> at <?= e($g['home']) ?><?= $g['featured'] ? ' <span class="tag o-accepted">Featured</span>' : '' ?><div class="muted"><?= e($g['note']) ?></div></td><td><?= e($g['screens']) ?><?= $g['sound'] ? ' · sound on' : '' ?></td>
<td><div class="acts"><a class="btn ghost sm" href="/admin/games?edit=<?= (int)$g['id'] ?>">Edit</a><form method="post" action="/admin/games" data-confirm="Remove this game?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><button class="btn danger sm">Remove</button></form></div></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?>
