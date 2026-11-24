<?php $kinds = ['' => 'All', 'contact' => 'Website', 'private' => 'Private events', 'careers' => 'Job applications']; $label = ['contact' => 'Website', 'private' => 'Private event', 'careers' => 'Job application']; ?>
<h1>Messages</h1><p class="muted">Website messages, private event requests and job applications. Reply by email; tick “Handled” when done.</p>
<nav class="tabs" aria-label="Filter"><?php foreach ($kinds as $k => $l): ?><a href="/admin/messages<?= $k ? '?kind=' . $k : '' ?>"<?= (string)$kind === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach ?></nav>
<?php if (!$rows): ?><div class="empty">No messages.</div><?php endif ?>
<div class="grid" style="gap:12px"><?php foreach ($rows as $r): ?>
<article class="card"<?= $r['handled'] ? ' style="opacity:.65"' : '' ?>>
  <div class="sh" style="margin-bottom:6px"><div><span class="tag"><?= e($label[$r['kind']] ?? $r['kind']) ?></span> <b><?= e($r['name']) ?></b> · <a href="mailto:<?= e($r['email']) ?>?subject=<?= rawurlencode('Re: ' . ($r['subject'] ?: ($label[$r['kind']] ?? 'Your message')) . ' — ' . shop()['name']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div><span class="muted"><?= e(to_local($r['created_at'])->format('M j, g:i A')) ?></span></div>
  <?php if ($r['subject'] || $r['event_date'] || $r['guests']): ?><p style="margin:0 0 6px"><b><?= e(implode(' · ', array_filter([$r['subject'], $r['event_date'] ? 'Date: ' . $r['event_date'] : '', $r['guests'] ? $r['guests'] . ' guests' : '']))) ?></b></p><?php endif ?>
  <p style="margin:0 0 10px;white-space:pre-wrap"><?= e($r['body']) ?></p>
  <form method="post" action="/admin/messages"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><?php if ($kind): ?><input type="hidden" name="kind" value="<?= e($kind) ?>"><?php endif ?><input type="hidden" name="handled" value="<?= $r['handled'] ? '' : '1' ?>"><button class="btn ghost sm"><?= $r['handled'] ? 'Mark as not handled' : 'Mark handled' ?></button></form>
</article><?php endforeach ?></div>
