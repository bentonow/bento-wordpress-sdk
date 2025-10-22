<?php

defined('ABSPATH') || exit();

class Bento_Mail_Handler implements Mail_Handler_Interface
{
  private static $instance = null;
  private static $initialized = false;

  private $config;
  private $logger;
  private $http_client;
  private $mail_logger;

  public function __construct(
    $config = null,
    $logger = null,
    $http_client = null,
    $mail_logger = null
  ) {
    $this->config = $config ?? new WordPress_Configuration();
    $this->logger = $logger ?? new WordPress_Logger();
    $this->http_client = $http_client ?? new WordPress_Http_Client();
    $this->mail_logger = $mail_logger ?? new Bento_Mail_Logger();
  }

  public static function instance(): self
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public function init(): void
  {
    if (self::$initialized) {
      $this->logger->log('Mail handler already initialized, skipping');
      return;
    }

    if (!$this->is_enabled()) {
      $this->logger->log(
        'Transactional emails not enabled, skipping initialization'
      );
      return;
    }

    add_filter('pre_wp_mail', [$this, 'handle_wp_mail'], 10, 2);
    $this->logger->log('Added pre_wp_mail filter');

    self::$initialized = true;
  }

  public function handle_wp_mail($null, array $atts)
  {
    $this->logger->log('Handle mail called');

    $to = is_array($atts['to']) ? $atts['to'] : explode(',', $atts['to']);
    $to = array_filter(array_map('trim', $to));

    if (empty($to)) {
      $this->logger->error('No valid recipient found; aborting mail send');
      return false;
    }

    $attachments = $atts['attachments'] ?? [];

    if (!empty($attachments)) {
      // Log the fallback using the same pathway as direct calls, then allow
      // WordPress core to continue handling the email.
      $recipient_list = implode(', ', $to);

      $this->handle_mail(
        $recipient_list,
        $atts['subject'] ?? '',
        $atts['message'] ?? '',
        [],
        $attachments
      );

      return $null;
    }

    $headers = is_array($atts['headers'])
      ? $atts['headers']
      : explode("\n", str_replace("\r\n", "\n", $atts['headers'] ?? ''));

    $all_sent = true;

    foreach ($to as $recipient) {
      $result = $this->handle_mail(
        $recipient,
        $atts['subject'] ?? '',
        $atts['message'] ?? '',
        $headers,
        []
      );

      if (!$result) {
        $all_sent = false;
      }
    }

    return $all_sent;
  }

  public function handle_mail(
    $to,
    $subject,
    $message,
    $headers = [],
    $attachments = []
  ): bool {
    $recipients = $this->normalize_recipients($to);

    if (empty($recipients)) {
      $this->logger->error('No valid recipient provided to handle_mail');
      return false;
    }

    $recipient_string = implode(', ', $recipients);
    $mail_id = uniqid('mail_', true);

    $this->mail_logger->log_mail([
      'id' => $mail_id,
      'type' => 'mail_received',
      'to' => $recipient_string,
      'subject' => $subject,
      'success' => true,
    ]);

    if (!empty($attachments)) {
      $this->mail_logger->log_mail([
        'id' => $mail_id,
        'type' => 'wordpress_fallback',
        'reason' => 'attachments',
        'to' => $recipient_string,
        'subject' => $subject,
        'success' => false,
      ]);

      $this->logger->log('Attachments detected; delegating to WordPress mail.');

      return false;
    }

    $hash = md5($recipient_string . $subject . $message);
    if ($this->mail_logger->is_duplicate($hash)) {
      $this->mail_logger->log_mail([
        'id' => $mail_id,
        'type' => 'blocked_duplicate',
        'to' => $recipient_string,
        'subject' => $subject,
        'hash' => $hash,
      ]);
      return true;
    }

    $parsed_headers = $this->parse_headers($headers);
    $reply_to =
      $parsed_headers['Reply-To'] ?? ($parsed_headers['reply-to'] ?? null);

    if ($this->config->get_option('bento_enable_reply_to', '1') !== '1') {
      $reply_to = null;
    }

    $result = $this->send_via_bento([
      'id' => $mail_id,
      'to' => $recipient_string,
      'subject' => $subject,
      'message' => $message,
      'headers' => $parsed_headers,
      'reply_to' => $reply_to,
      'hash' => $hash,
    ]);

    $this->mail_logger->log_mail([
      'id' => $mail_id,
      'type' => $result ? 'bento_sent' : 'bento_failed',
      'to' => $recipient_string,
      'reply_to' => $reply_to,
      'subject' => $subject,
      'hash' => $hash,
      'success' => $result,
    ]);

    return $result;
  }

  private function parse_headers(array $headers): array
  {
    $parsed = [];
    foreach ($headers as $header) {
      if (strpos($header, ':') === false) {
        continue;
      }
      [$name, $value] = explode(':', $header, 2);
      $parsed[trim($name)] = trim($value);
    }
    return $parsed;
  }

  private function send_via_bento(array $data): bool
  {
    $site_key = $this->config->get_option('bento_site_key');
    $publishable_key = $this->config->get_option('bento_publishable_key');
    $secret_key = $this->config->get_option('bento_secret_key');
    $from_email = $this->config->get_option(
      'bento_from_email',
      $this->config->get_option('admin_email'),
    );

    if (empty($site_key) || empty($publishable_key) || empty($secret_key)) {
      $this->logger->error('Missing API credentials');
      return false;
    }

    $auth = base64_encode($publishable_key . ':' . $secret_key);
    $url = "https://app.bentonow.com/api/v1/batch/emails?site_uuid={$site_key}";

    $body = [
      'emails' => [
        [
          'to' => $data['to'],
          'from' => $from_email,
          'subject' => $data['subject'],
          'html_body' => $data['message'],
          'transactional' => true,
        ],
      ],
    ];

    if (!empty($data['reply_to'])) {
      $body['emails'][0]['reply_to'] = $data['reply_to'];
    }

    try {
      $response = $this->http_client->post($url, $body, [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Basic ' . $auth,
        'User-Agent' => 'bento-wordpress-' . $site_key,
      ]);

      return $response['status_code'] === 200;
    } catch (\Exception $e) {
      $this->logger->error('API Error: ' . $e->getMessage());
      return false;
    }
  }

  private function is_enabled(): bool
  {
    return $this->config->get_option('bento_enable_transactional') === '1';
  }

  private function normalize_recipients($recipients): array
  {
    if (is_string($recipients)) {
      $recipients = explode(',', $recipients);
    } elseif (!is_array($recipients)) {
      $recipients = (array) $recipients;
    }

    $recipients = array_filter(array_map('trim', $recipients));

    return array_values($recipients);
  }
}
