<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ApiKeyGuard;
use App\Core\Request;
use PHPUnit\Framework\TestCase;

class ApiKeyGuardTest extends TestCase
{
    public function testValidKey_completesWithoutException(): void
    {
        $_ENV['API_KEY']            = 'test-key';
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['REQUEST_URI']     = '/api/customers';
        $_SERVER['HTTP_X_API_KEY']  = 'test-key';
        $_GET                       = [];

        ApiKeyGuard::check(new Request());

        $this->assertTrue(true); // pas d'exit() = succès
    }
}
