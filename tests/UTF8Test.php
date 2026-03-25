<?php

declare(strict_types=1);

namespace B1rdex\Text\Tests;

use B1rdex\Text\Util\UTF8;
use PHPUnit\Framework\TestCase;

/**
 * @covers \B1rdex\Text\Util\UTF8
 */
class UTF8Test extends TestCase
{
    private const WORD = "вдох\xc2\xadно\xc2\xadве\xcc\x81\xc2\xadние";

    public function testDiactricalRemoveKeepsSoftHyphens(): void
    {
        $result = UTF8::diactrical_remove(self::WORD);

        self::assertSame("вдох\xc2\xadно\xc2\xadве\xc2\xadние", $result);
    }

    public function testDiactricalRemoveStripsExtraChars(): void
    {
        $result = UTF8::diactrical_remove(self::WORD, ["\xc2\xad"]);

        self::assertSame('вдохновение', $result);
    }

    public function testDiactricalRemoveBuildRestoreTable(): void
    {
        $restore_table = null;
        $result = UTF8::diactrical_remove(self::WORD, ["\xc2\xad"], true, $restore_table);

        self::assertSame('вдохновение', $result);
        self::assertNotNull($restore_table);
    }

    public function testDiactricalRestore(): void
    {
        $restore_table = [];
        UTF8::diactrical_remove(self::WORD, ["\xc2\xad"], true, $restore_table);
        $result = UTF8::diactrical_restore('вдохновение', $restore_table);

        self::assertSame(self::WORD, $result);
    }
}
