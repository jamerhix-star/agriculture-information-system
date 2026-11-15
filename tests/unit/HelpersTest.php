<?php
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testValidDate()
    {
        $this->assertTrue(valid_date('2026-01-01'));
        $this->assertFalse(valid_date('not-a-date'));
    }
}
