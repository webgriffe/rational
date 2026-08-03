<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class RoundingTest extends TestCase
{

    public function testFloor1(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->floor();
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testFloor2(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->floor(1);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([2, 5], $result->getFractionPart());
    }

    public function testFloor3(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->floor(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([21, 50], $result->getFractionPart());
    }

    public function testFloor4(): void
    {
        $r = Rational::fromWholeAndFraction(2, 1, 2);
        $result = $r->floor(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([1, 2], $result->getFractionPart());
    }

    public function testFloor5(): void
    {
        $r = Rational::fromWhole(2);
        $result = $r->floor(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testFloor6(): void
    {
        $r = Rational::fromWholeAndFraction(-2, -3, 7);
        $result = $r->floor();
        $this->assertEquals(-3, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testFloor7(): void
    {
        $r = Rational::fromWholeAndFraction(-2, -3, 7);
        $result = $r->floor(2);
        $this->assertEquals(-2, $result->getWholePart());
        $this->assertEquals([-43, 100], $result->getFractionPart());
    }

    public function testCeil1(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->ceil();
        $this->assertEquals(3, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testCeil2(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->ceil(1);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([1, 2], $result->getFractionPart());
    }

    public function testCeil3(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->ceil(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([43, 100], $result->getFractionPart());
    }

    public function testCeil4(): void
    {
        $r = Rational::fromWholeAndFraction(2, 1, 2);
        $result = $r->ceil(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([1, 2], $result->getFractionPart());
    }

    public function testCeil5(): void
    {
        $r = Rational::fromWhole(2);
        $result = $r->ceil(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testCeil6(): void
    {
        $r = Rational::fromWholeAndFraction(-2, -3, 7);
        $result = $r->ceil();
        $this->assertEquals(-2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testCeil7(): void
    {
        $r = Rational::fromWholeAndFraction(-2, -3, 7);
        $result = $r->ceil(2);
        $this->assertEquals(-2, $result->getWholePart());
        $this->assertEquals([-21, 50], $result->getFractionPart());
    }

    public function testRound1(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->round();
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testRound2(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->round(1);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([2, 5], $result->getFractionPart());
    }

    public function testRound3(): void
    {
        $r = Rational::fromWholeAndFraction(2, 3, 7);
        $result = $r->round(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([43, 100], $result->getFractionPart());
    }

    public function testRound4(): void
    {
        $r = Rational::fromWholeAndFraction(2, 1, 2);
        $result = $r->round(0);
        $this->assertEquals(3, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testRound5(): void
    {
        $r = Rational::fromWholeAndFraction(2, 1, 2);
        $result = $r->round(1);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([1, 2], $result->getFractionPart());
    }

    public function testRound6(): void
    {
        $r = Rational::fromWholeAndFraction(2, 1, 2);
        $result = $r->round(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([1, 2], $result->getFractionPart());
    }

    public function testRound7(): void
    {
        $r = Rational::fromWhole(2);
        $result = $r->round(2);
        $this->assertEquals(2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testRound8(): void
    {
        $r = Rational::fromWholeAndFraction(-2, -3, 7);
        $result = $r->round();
        $this->assertEquals(-2, $result->getWholePart());
        $this->assertEquals([0, 1], $result->getFractionPart());
    }

    public function testRound9(): void
    {
        $r = Rational::fromWholeAndFraction(-2, -3, 7);
        $result = $r->round(2);
        $this->assertEquals(-2, $result->getWholePart());
        $this->assertEquals([-43, 100], $result->getFractionPart());
    }
}
