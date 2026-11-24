<?php $f = $edit ?? []; $show = $edit || $new; $catOpts = array_map(fn($c) => [$c['id'], $c['name']], $cats); ?>
<div class="sh"><div><h1>Menu</h1><p class="muted" style="margin:0">Changes show on the website straight away. “Sold out” hides the Add button but keeps the item visible.</p></div>
  <div class="acts"><a class="btn primary" href="/admin/menu?new=1#item-form">+ Add item</a><a class="btn ghost" href="/menu" target="_blank" rel="noopener">View menu ↗</a></div></div>
<?php if ($show): ?>
<form method="post" action="/admin/menu" enctype="multipart/form-data" class="card form" id="item-form" style="margin-bottom:22px"><?= csrf_field() ?><input type="hidden" name="action" value="item_save"><input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
  <h2><?= $edit ? 'Edit ' . e($f['name']) : 'New menu item' ?></h2>
  <?php if (!$cats): ?><div class="alert bad">Add a category first (below).</div><?php endif ?>
  <div class="grid g3">
    <div class="field"><label for="m-name">Name</label><input id="m-name" name="name" required value="<?= e($f['name'] ?? '') ?>"></div>
    <div class="field"><label for="m-cat">Category</label><select id="m-cat" name="category_id"><?php foreach ($catOpts as [$cid, $cn]): ?><option value="<?= (int)$cid ?>"<?= (int)($f['category_id'] ?? 0) === (int)$cid ? ' selected' : '' ?>><?= e($cn) ?></option><?php endforeach ?></select></div>
    <div class="field"><label for="m-price">Price ($)</label><input id="m-price" name="price" inputmode="decimal" required value="<?= e($f['price'] ?? '') ?>"></div>
  </div>
  <div class="field"><label for="m-desc">Description</label><textarea id="m-desc" name="description" maxlength="400"><?= e($f['description'] ?? '') ?></textarea></div>
  <fieldset style="border:0;padding:0;margin:0"><legend style="font-weight:600;font-size:14px;margin-bottom:6px">Tags</legend><div class="checks"><?php foreach (ITEM_TAGS as $k => $l): ?><label class="check"><input type="checkbox" name="tags[]" value="<?= $k ?>"<?= in_array($k, item_tags($f['tags'] ?? ''), true) ? ' checked' : '' ?>><?= e($l) ?></label><?php endforeach ?></div></fieldset>
  <div class="checks">
    <label class="check"><input type="checkbox" name="available" value="1"<?= ($f['available'] ?? 1) ? ' checked' : '' ?>>Available (untick = sold out)</label>
    <label class="check"><input type="checkbox" name="orderable" value="1"<?= ($f['orderable'] ?? 1) ? ' checked' : '' ?>>Can be ordered online</label>
    <label class="check"><input type="checkbox" name="featured" value="1"<?= ($f['featured'] ?? 0) ? ' checked' : '' ?>>Feature on the home page</label>
  </div>
  <div class="grid g2" style="align-items:end">
    <div class="field"><label for="m-photo">Photo <span class="hint">(JPG, PNG or WebP)</span></label><input id="m-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><?php if (!empty($f['photo'])): ?><label class="check"><input type="checkbox" name="remove_photo" value="1">Remove current photo</label><?php endif ?></div>
    <div class="field"><label for="m-pos">Order in its category</label><input id="m-pos" name="position" type="number" value="<?= (int)($f['position'] ?? 0) ?>"></div>
  </div>
  <div class="acts"><button class="btn primary">Save item</button><a class="btn ghost" href="/admin/menu">Cancel</a></div>
</form>
<?php endif ?>
<?php foreach ($cats as $c): ?>
<section class="card" style="margin-bottom:18px">
  <div class="sh" style="margin-bottom:8px"><h2 style="margin:0"><?= e($c['name']) ?><?= $c['active'] ? '' : ' <span class="tag">Hidden</span>' ?></h2><a class="btn ghost sm" href="/admin/menu?cat=<?= (int)$c['id'] ?>#cat-form">Edit category</a></div>
  <?php if (!$c['items']): ?><p class="muted" style="margin:0">No items yet.</p><?php else: ?>
  <div class="tblwrap"><table class="tbl"><thead><tr><th></th><th>Item</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($c['items'] as $i): $img = media_url('menu', $i['photo']); ?><tr>
    <td><?php if ($img): ?><img class="thumb" src="<?= e($img) ?>" alt=""><?php endif ?></td>
    <td><b><?= e($i['name']) ?></b><div class="muted"><?= e(implode(', ', array_map(fn($t) => ITEM_TAGS[$t], item_tags($i['tags'])))) ?><?= $i['featured'] ? ' · on home page' : '' ?><?= $i['orderable'] ? '' : ' · not online' ?></div></td>
    <td><?= money($i['price']) ?></td>
    <td><?= $i['available'] ? '<span class="tag o-ready">Available</span>' : '<span class="tag o-cancelled">Sold out</span>' ?></td>
    <td><div class="acts"><form method="post" action="/admin/menu"><?= csrf_field() ?><input type="hidden" name="action" value="item_toggle"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>"><button class="btn ghost sm"><?= $i['available'] ? 'Mark sold out' : 'Back on' ?></button></form>
      <a class="btn ghost sm" href="/admin/menu?edit=<?= (int)$i['id'] ?>#item-form">Edit</a>
      <form method="post" action="/admin/menu" data-confirm="Delete <?= e($i['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="item_delete"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>"><button class="btn danger sm">Delete</button></form></div></td>
  </tr><?php endforeach ?></tbody></table></div><?php endif ?>
</section>
<?php endforeach ?>
<?php $ec = $editCat ?? []; ?>
<form method="post" action="/admin/menu" class="card form" id="cat-form"><?= csrf_field() ?><input type="hidden" name="action" value="cat_save"><input type="hidden" name="id" value="<?= (int)($ec['id'] ?? 0) ?>">
  <h2><?= $ec ? 'Edit category' : 'Add a category' ?></h2>
  <div class="grid g3"><div class="field"><label for="c-name">Name</label><input id="c-name" name="name" value="<?= e($ec['name'] ?? '') ?>" placeholder="e.g. Starters"></div>
    <div class="field"><label for="c-desc">Description <span class="hint">(optional)</span></label><input id="c-desc" name="description" value="<?= e($ec['description'] ?? '') ?>"></div>
    <div class="field"><label for="c-pos">Order on the menu</label><input id="c-pos" name="position" type="number" value="<?= (int)($ec['position'] ?? 0) ?>"></div></div>
  <label class="check"><input type="checkbox" name="active" value="1"<?= ($ec['active'] ?? 1) ? ' checked' : '' ?>>Show on the website</label>
  <div class="acts"><button class="btn primary">Save category</button><?php if ($ec): ?><a class="btn ghost" href="/admin/menu">Cancel</a></div></form>
  <form method="post" action="/admin/menu" data-confirm="Delete this category?" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="cat_delete"><input type="hidden" name="id" value="<?= (int)$ec['id'] ?>"><button class="btn danger sm">Delete category</button></form>
  <?php else: ?></div></form><?php endif ?>
