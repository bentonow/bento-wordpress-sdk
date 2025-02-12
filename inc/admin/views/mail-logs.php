<?php defined('ABSPATH') || exit; ?>
<div class="wrap">
    <?php if (isset($_GET['cleared'])): ?>
      <script>
        window.mailLogCleared = true;
      </script>
    <?php endif; ?>

  <!-- Add a visible container title for debugging -->
  <div id="bento-mail-logs">Loading mail logs...</div>
</div>

<script>

  window.bentoMailLogs = {
    logs: <?php echo wp_json_encode($logs); ?>,
    nonce: <?php echo wp_json_encode(wp_create_nonce('clear_bento_mail_logs')); ?>,
    adminUrl: <?php echo wp_json_encode(admin_url('admin-post.php')); ?>
  };
</script>