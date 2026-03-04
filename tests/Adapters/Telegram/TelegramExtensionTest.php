<?php
declare(strict_types=1);
namespace App\Tests\Adapters\Telegram;

use App\Adapters\Telegram\TelegramExtension;
use PHPUnit\Framework\TestCase;

final class TelegramExtensionTest extends TestCase
{
    private TelegramExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TelegramExtension();
    }

    public function testEscapesMarkdownV2SpecialChars(): void
    {
        $filters = $this->extension->getFilters();
        self::assertCount(1, $filters);

        $callable = $filters[0]->getCallable();
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $input = implode('', $specialChars);
        $result = $callable($input);

        foreach ($specialChars as $char) {
            self::assertStringContainsString('\\' . $char, $result);
        }
    }

    public function testPlainTextPassesThroughUnchanged(): void
    {
        $filters = $this->extension->getFilters();
        $callable = $filters[0]->getCallable();
        $input = 'Hello world 123';

        self::assertSame($input, $callable($input));
    }
}
