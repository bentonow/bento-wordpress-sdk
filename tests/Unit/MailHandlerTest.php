<?php

namespace Tests\Unit;

use Mockery as m;
use Bento_Mail_Handler;
use Configuration_Interface;
use Logger_Interface;
use Http_Client_Interface;
use Mail_Logger_Interface;

beforeEach(function() {
    $this->config = m::mock(Configuration_Interface::class);
    $this->logger = m::mock(Logger_Interface::class);
    $this->httpClient = m::mock(Http_Client_Interface::class);
    $this->mailLogger = m::mock(Mail_Logger_Interface::class);
    $this->config->shouldReceive('get_option')->with('bento_enable_transactional')->andReturn('1');
    $this->logger->shouldReceive('log');

    $this->handler = new Bento_Mail_Handler(
        $this->config,
        $this->logger,
        $this->httpClient,
        $this->mailLogger
    );
});

test('rejects and logs emails with attachments', function () {
    $this->mailLogger->shouldReceive('log_mail')
        ->once()
        ->withArgs(function($data) {
            return $data['type'] === 'mail_received' &&
                $data['to'] === 'test@example.com' &&
                $data['subject'] === 'Test Subject';
        });

    $this->mailLogger->shouldReceive('log_mail')
        ->once()
        ->withArgs(function($data) {
            return $data['type'] === 'wordpress_fallback' &&
                $data['reason'] === 'attachments' &&
                $data['to'] === 'test@example.com' &&
                $data['subject'] === 'Test Subject';
        });

    $result = $this->handler->handle_mail(
        'test@example.com',
        'Test Subject',
        'Test Message',
        [],
        ['attachment.pdf']
    );

    expect($result)->toBeFalse();
});

test('sends mail via bento api', function () {
    $this->config->shouldReceive('get_option')
        ->with('bento_site_key')
        ->andReturn('site_key');
    $this->config->shouldReceive('get_option')
        ->with('bento_publishable_key')
        ->andReturn('pub_key');
    $this->config->shouldReceive('get_option')
        ->with('bento_secret_key')
        ->andReturn('secret_key');
    $this->config->shouldReceive('get_option')
        ->with('bento_from_email', m::any())
        ->andReturn('from@example.com');
    $this->config->shouldReceive('get_option')
        ->with('admin_email')
        ->andReturn('admin@example.com');

    $this->httpClient->shouldReceive('post')
        ->once()
        ->andReturn(['status_code' => 200]);

    $this->mailLogger->shouldReceive('log_mail')->twice();
    $this->mailLogger->shouldReceive('is_duplicate')->andReturn(false);

    $result = $this->handler->handle_mail(
        'test@example.com',
        'Test Subject',
        'Test Message'
    );

    expect($result)->toBeTrue();
});

test('blocks duplicate emails', function () {
    $hash = md5('test@example.com' . 'Test Subject' . 'Test Message');

    $this->mailLogger->shouldReceive('log_mail')
        ->once()
        ->withArgs(function($data) {
            return $data['type'] === 'mail_received';
        });

    $this->mailLogger->shouldReceive('log_mail')
        ->once()
        ->withArgs(function($data) use ($hash) {
            return $data['type'] === 'blocked_duplicate' &&
                $data['hash'] === $hash;
        });

    $this->mailLogger->shouldReceive('is_duplicate')
        ->with($hash)
        ->andReturn(true);

    $result = $this->handler->handle_mail(
        'test@example.com',
        'Test Subject',
        'Test Message'
    );

    expect($result)->toBeTrue();
});