<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\EventValidator;
use PHPUnit\Framework\TestCase;

class EventValidatorTest extends TestCase
{
    public function testValidPayload_returnsCleanData(): void
    {
        $validator = new EventValidator();

        $result = $validator->validate([
            'customer'  => ['email' => 'Alice@Example.COM', 'name' => 'Alice'],
            'event'     => 'purchase',
            'timestamp' => '2026-01-15T10:30:00Z',
        ]);

        $this->assertSame('alice@example.com', $result['customer']['email']);
        $this->assertSame('purchase', $result['event']);
    }
}
