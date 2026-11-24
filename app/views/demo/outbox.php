<?php /** Emails the demo would have sent. In a demo nothing is actually sent. */ ?>
<div class="card demo-outbox">
  <h1>Demo outbox</h1>
  <p class="hint">In a real shop these emails go to customers and staff. In this demo nothing leaves the server — they’re shown here instead.</p>
  <?php if (!$mails): ?><p>No emails yet. Place an order or request a table on the website, then confirm it in the staff side, and they’ll appear here.</p><?php endif ?>
  <?php foreach ($mails as $mm): ?>
    <details class="demo-mail"><summary><b><?= e($mm['subject']) ?></b> <span class="hint">to <?= e($mm['to']) ?> · <?= e(substr($mm['at'], 0, 16)) ?></span></summary><pre><?= e($mm['body']) ?></pre></details>
  <?php endforeach ?>
  <p><a href="/admin">Back to the staff side</a></p>
</div>
