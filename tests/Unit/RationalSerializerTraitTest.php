<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;
use Webgriffe\Rational\RationalSerializerTrait;

final class RationalSerializerTraitTest extends TestCase
{
    /** @var RationalSerializerTrait */
    private object $impl;

    protected function setUp(): void
    {
        $this->impl = new class {
            use RationalSerializerTrait {
                serialize as public;
                unserialize as public;
            }
        };
    }

    public function testSerializeNull(): void
    {
        $this->assertNull($this->impl->serialize(null));
    }

    public function testUnserializeNull(): void
    {
        $this->assertNull($this->impl->unserialize(null));
    }

    public function testRoundTrip(): void
    {
        $original = Rational::fromWholeAndFraction(3, 1, 4);
        $this->assertTrue($original->equals($this->impl->unserialize($this->impl->serialize($original))));
    }

    public function testRoundTripNegative(): void
    {
        $original = Rational::fromWholeAndFraction(-7, -2, 5);
        $this->assertTrue($original->equals($this->impl->unserialize($this->impl->serialize($original))));
    }

    public function testRoundTripZero(): void
    {
        $original = Rational::zero();
        $this->assertTrue($original->equals($this->impl->unserialize($this->impl->serialize($original))));
    }

    public function testUnserializeWithTooManyPartsThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->impl->unserialize('1:2:3:4');
    }

    public function testUnserializeWithTooFewPartsThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->impl->unserialize('1:2');
    }

    public function testUnserializeWithNoSeparatorThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->impl->unserialize('42');
    }
}
