<?php

namespace Tests;

class TestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }
}