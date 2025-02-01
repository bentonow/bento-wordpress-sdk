<?php

namespace Tests\Unit;

use Mockery as m;
use Mail_Logger_Interface;

test('logs mail when logging is enabled', function () {
    $logger = m::mock(Mail_Logger_Interface::class);

    $data = [
        'id' => 'test_id',
        'type' => 'mail_received',
        'to' => 'test@example.com',
        'subject' => 'Test Subject'
    ];

    $logger->shouldReceive('log_mail')
        ->once()
        ->with(m::subset($data));

    $logger->shouldReceive('read_logs')
        ->once()
        ->andReturn([$data]);

    $logger->log_mail($data);
    $logs = $logger->read_logs();

    expect($logs)->toHaveCount(1);
    expect($logs[0]['id'])->toBe('test_id');
    expect($logs[0]['type'])->toBe('mail_received');
});

test('does not log when logging is disabled', function () {
    $logger = m::mock(Mail_Logger_Interface::class);

    $data = [
        'id' => 'test_id',
        'type' => 'mail_received'
    ];

    $logger->shouldReceive('log_mail')
        ->with(m::subset($data))
        ->once();

    $logger->shouldReceive('read_logs')
        ->once()
        ->andReturn([]);

    $logger->log_mail($data);
    $logs = $logger->read_logs();
    expect($logs)->toBeEmpty();
});

test('clears logs', function () {
    $logger = m::mock(Mail_Logger_Interface::class);

    $logger->shouldReceive('clear_logs')
        ->once();

    $logger->shouldReceive('read_logs')
        ->once()
        ->andReturn([]);

    $logger->clear_logs();
    $logs = $logger->read_logs();
    expect($logs)->toBeEmpty();
});

test('respects log limit', function () {
    $logger = m::mock(Mail_Logger_Interface::class);

    $logEntries = [
        ['id' => 'test_1', 'timestamp' => time()],
        ['id' => 'test_2', 'timestamp' => time()],
        ['id' => 'test_3', 'timestamp' => time()]
    ];

    $logger->shouldReceive('read_logs')
        ->with(2)
        ->once()
        ->andReturn(array_slice($logEntries, 0, 2));

    $logs = $logger->read_logs(2);
    expect($logs)->toHaveCount(2);
    expect($logs[0]['id'])->toBe('test_1');
});
