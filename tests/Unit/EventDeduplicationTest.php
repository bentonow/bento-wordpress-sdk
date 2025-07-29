<?php

use PHPUnit\Framework\TestCase;

class EventDeduplicationTest extends TestCase
{
    private $events_controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Include the class file
        require_once __DIR__ . '/../../inc/class-bento-events-controller.php';
        
        // Use reflection to access private methods
        $this->events_controller = new ReflectionClass('Bento_Events_Controller');
    }

    public function test_deduplicates_course_events_with_same_email_and_course_id()
    {
        $events = [
            [
                'user_id' => 2,
                'type' => 'learndash_user_enrolled_in_course',
                'email' => 'test@example.com',
                'details' => [
                    'course_id' => 1208,
                    'course_name' => 'Kickstart Your Journey As 3D Creator'
                ]
            ],
            [
                'user_id' => 2,
                'type' => 'learndash_user_enrolled_in_course',
                'email' => 'test@example.com',
                'details' => [
                    'course_id' => 1208,
                    'course_name' => '3D Character Creation & Animation Course'
                ]
            ]
        ];

        $deduplicate_method = $this->events_controller->getMethod('deduplicate_events');
        $deduplicate_method->setAccessible(true);

        $result = $deduplicate_method->invoke(null, $events);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals(1208, $result[0]['details']['course_id']);
    }

    public function test_keeps_different_course_events()
    {
        $events = [
            [
                'user_id' => 2,
                'type' => 'learndash_user_enrolled_in_course',
                'email' => 'test@example.com',
                'details' => [
                    'course_id' => 1208,
                    'course_name' => 'Course 1'
                ]
            ],
            [
                'user_id' => 2,
                'type' => 'learndash_user_enrolled_in_course',
                'email' => 'test@example.com',
                'details' => [
                    'course_id' => 1209,
                    'course_name' => 'Course 2'
                ]
            ]
        ];

        $deduplicate_method = $this->events_controller->getMethod('deduplicate_events');
        $deduplicate_method->setAccessible(true);

        $result = $deduplicate_method->invoke(null, $events);

        $this->assertCount(2, $result);
    }

    public function test_keeps_different_email_events()
    {
        $events = [
            [
                'user_id' => 2,
                'type' => 'learndash_user_enrolled_in_course',
                'email' => 'user1@example.com',
                'details' => [
                    'course_id' => 1208,
                    'course_name' => 'Course 1'
                ]
            ],
            [
                'user_id' => 3,
                'type' => 'learndash_user_enrolled_in_course',
                'email' => 'user2@example.com',
                'details' => [
                    'course_id' => 1208,
                    'course_name' => 'Course 1'
                ]
            ]
        ];

        $deduplicate_method = $this->events_controller->getMethod('deduplicate_events');
        $deduplicate_method->setAccessible(true);

        $result = $deduplicate_method->invoke(null, $events);

        $this->assertCount(2, $result);
    }

    public function test_generates_correct_dedup_key()
    {
        $event = [
            'user_id' => 2,
            'type' => 'learndash_user_enrolled_in_course',
            'email' => 'test@example.com',
            'details' => [
                'course_id' => 1208,
                'course_name' => 'Test Course'
            ]
        ];

        $generate_key_method = $this->events_controller->getMethod('generate_dedup_key');
        $generate_key_method->setAccessible(true);

        $key = $generate_key_method->invoke(null, $event);

        $this->assertIsString($key);
        $this->assertEquals(32, strlen($key)); // MD5 hash length
    }
}