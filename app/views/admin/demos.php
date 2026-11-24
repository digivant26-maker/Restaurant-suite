<?php /** DIGIVANT's list of demo requests (leads). */ ?>
<div class="sh" style="margin-bottom:16px"><h1 style="font-size:40px">Demo requests</h1>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn ghost" href="/admin/demos?format=csv">Download CSV</a>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cleanup"><button class="btn ghost">Delete expired demos now</button></form></div></div>
<p class="muted">Each row is a restaurant that built a demo. Demos delete themselves after <?= demo_hours() ?> hours; the contact details stay here for follow-up.</p>
<?php if (!$rows): ?><div class="empty">No demos yet. Share <b><?= e(site_url()) ?>/demo</b>.</div><?php else: ?>
<div class="tblwrap"><table class="tbl stack"><thead><tr><th scope="col">Shop</th><th scope="col">Contact</th><th scope="col">Created</th><th scope="col">Last visit</th><th scope="col">Status</th></tr></thead><tbody>
<?php foreach ($rows as $r): $st = $r['deleted_at'] ? 'Deleted' : (demo_active($r) ? 'Active' : 'Expired'); ?>
<tr><td data-l="Shop"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?= e($r['brand_color']) ?>;vertical-align:middle;margin-right:6px" aria-hidden="true"></span><b><?= e($r['shop_name']) ?></b><div class="muted"><?= e($r['city'] ?? '') ?></div></td>
<td data-l="Contact"><?= e($r['contact_name']) ?><div class="muted"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div></td>
<td data-l="Created"><?= to_local($r['created_at'])->format('M j, g:i A') ?></td>
<td data-l="Last visit"><?= $r['last_seen_at'] ? to_local($r['last_seen_at'])->format('M j, g:i A') : '—' ?></td>
<td data-l="Status"><?= $st ?></td></tr>
<?php endforeach ?></tbody></table></div><?php endif ?>
