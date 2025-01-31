<?php defined('ABSPATH') || exit; ?>
<div class="wrap bento-mail-logs">
  <h1><?php _e('Bento Mail Logs', 'bentonow'); ?></h1>

    <?php if (isset($_GET['cleared'])): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php _e('Logs have been cleared successfully.', 'bentonow'); ?></p>
      </div>
    <?php endif; ?>

  <div class="tablenav top">
    <div class="alignleft actions bulkactions">
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
          <?php wp_nonce_field('clear_bento_mail_logs'); ?>
        <input type="hidden" name="action" value="clear_bento_mail_logs">
        <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs?', 'bentonow'); ?>')">
            <?php esc_html_e('Clear Logs', 'bentonow'); ?>
        </button>
      </form>
    </div>
  </div>

  <table class="wp-list-table widefat fixed striped">
    <thead>
    <tr>
      <th scope="col" class="column-id"><?php esc_html_e('ID', 'bentonow'); ?></th>
      <th scope="col" class="column-time"><?php esc_html_e('Time', 'bentonow'); ?></th>
      <th scope="col" class="column-type"><?php esc_html_e('Type', 'bentonow'); ?></th>
      <th scope="col" class="column-to"><?php esc_html_e('To', 'bentonow'); ?></th>
      <th scope="col" class="column-subject"><?php esc_html_e('Subject', 'bentonow'); ?></th>
      <th scope="col" class="column-status"><?php esc_html_e('Status', 'bentonow'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($logs)): ?>
      <tr>
        <td colspan="6"><?php esc_html_e('No logs found.', 'bentonow'); ?></td>
      </tr>
    <?php endif; ?>

    <?php foreach ($logs as $log): ?>
      <tr>
        <td class="column-id">
            <?php echo esc_html($log['id'] ?? ''); ?>
        </td>
        <td class="column-time">
            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['timestamp'])); ?>
        </td>
        <td class="column-type">
            <?php echo esc_html(ucfirst(str_replace('_', ' ', $log['type']))); ?>
        </td>
        <td class="column-to">
            <?php echo esc_html($log['to'] ?? ''); ?>
        </td>
        <td class="column-subject">
            <?php echo esc_html($log['subject'] ?? ''); ?>
        </td>
        <td class="column-status">
            <?php if ($log['type'] === 'blocked_duplicate'): ?>
              <span class="dashicons dashicons-warning" title="<?php esc_attr_e('Duplicate email blocked', 'bentonow'); ?>"></span>
              <span class="status-text"><?php esc_html_e('Blocked', 'bentonow'); ?></span>
            <?php elseif ($log['type'] === 'wordpress_fallback'): ?>
              <span class="dashicons dashicons-backup" title="<?php esc_attr_e('Sent via WordPress', 'bentonow'); ?>"></span>
              <span class="status-text"><?php esc_html_e('WordPress', 'bentonow'); ?></span>
            <?php elseif ($log['type'] === 'mail_received'): ?>
              <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e('Mail received', 'bentonow'); ?>"></span>
              <span class="status-text"><?php esc_html_e('Received', 'bentonow'); ?></span>
            <?php else: ?>
                <?php if (!empty($log['success'])): ?>
                <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e('Sent successfully', 'bentonow'); ?>"></span>
                <span class="status-text"><?php esc_html_e('Sent', 'bentonow'); ?></span>
                <?php else: ?>
                <span class="dashicons dashicons-no-alt" title="<?php esc_attr_e('Failed to send', 'bentonow'); ?>"></span>
                <span class="status-text"><?php esc_html_e('Failed', 'bentonow'); ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<style>
    .bento-mail-logs .column-id { width: 15%; }
    .bento-mail-logs .column-time { width: 15%; }
    .bento-mail-logs .column-type { width: 15%; }
    .bento-mail-logs .column-to { width: 20%; }
    .bento-mail-logs .column-status { width: 10%; }
    .bento-mail-logs .dashicons { vertical-align: middle; margin-right: 5px; }
    .bento-mail-logs .dashicons-yes-alt { color: #46b450; }
    .bento-mail-logs .dashicons-no-alt { color: #dc3232; }
    .bento-mail-logs .dashicons-warning { color: #ffb900; }
    .bento-mail-logs .dashicons-backup { color: #72aee6; }
    .bento-mail-logs .status-text { vertical-align: middle; }
</style>