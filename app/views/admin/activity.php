<h1>Activity</h1><p class="muted">The latest 300 changes: orders, reservations, menu, settings and staff.</p>
<?php if (!$rows): ?><div class="empty">Nothing yet.</div><?php else: ?><div class="tblwrap"><table class="tbl"><thead><tr><th>When</th><th>Who</th><th>What</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e(to_local($r['created_at'])->format('M j, g:i A')) ?></td><td><?= e($r['user_name']) ?></td><td><?= e($r['detail'] ?: $r['action']) ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?>
