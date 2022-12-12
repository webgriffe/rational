<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class FormatTest extends TestCase
{
    public function testNegativeNumberOfDecimalsIsNotAllowed()
    {
        $r = Rational::fromWhole(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of decimals cannot be negative');
        $r->format(1, -1);
    }

    public function testFormatChecksThatFormatIsSensible()
    {
        $r = Rational::fromWhole(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The minimum number of decimals cannot be larger than the maximum number of decimals');
        $r->format(1, 2);
    }

    public function testFormat1()
    {
        $r = Rational::fromWhole(5);
        $this->assertSame('5', $r->format(3));
    }

    public function testFormat2()
    {
        $r = Rational::fromWhole(-15);
        $this->assertSame('-15', $r->format(0));
    }

    public function testFormat3()
    {
        $r = Rational::fromWhole(1234567);
        $this->assertSame('1,234,567', $r->format(0));
    }

    public function testFormat4()
    {
        $r = Rational::fromWhole(-1234567);
        $this->assertSame('-1,234,567', $r->format(0));
    }

    public function testFormat5()
    {
        $r = Rational::fromWhole(1234567);
        $this->assertSame('1 234 567', $r->format(0, 0, '.', ' '));
    }

    public function testFormat6()
    {
        $r = Rational::fromWholeAndFraction(0, 2, 3);
        $this->assertSame('1', $r->format(0));
    }

    public function testFormat7()
    {
        $r = Rational::fromWholeAndFraction(0, -2, 3);
        $this->assertSame('-1', $r->format(0));
    }

    public function testFormat8()
    {
        $r = Rational::fromWholeAndFraction(0, 2, 3);
        $this->assertSame('0.7', $r->format(1));
    }

    public function testFormat9()
    {
        $r = Rational::fromWholeAndFraction(0, 2, 3);
        $this->assertSame('0.6666667', $r->format(7));
    }

    public function testFormat10()
    {
        $r = Rational::fromWholeAndFraction(0, -2, 3);
        $this->assertSame('-0.6666667', $r->format(7));
    }

    public function testFormat11()
    {
        $r = Rational::fromWholeAndFraction(1234567, 2, 3);
        $this->assertSame('1,234,567.6666667', $r->format(7));
    }

    public function testFormat12()
    {
        $r = Rational::fromWholeAndFraction(1234567, 2, 3);
        $this->assertSame('1,234,568', $r->format(0));
    }

    public function testFormat13()
    {
        $r = Rational::fromWholeAndFraction(-1234567, -2, 3);
        $this->assertSame('-1,234,567.6666667', $r->format(7));
    }

    public function testFormat14()
    {
        $r = Rational::fromWholeAndFraction(-1234567, -2, 3);
        $this->assertSame('-1,234,568', $r->format(0));
    }

    public function testFormat15()
    {
        $r = Rational::fromFraction(167, 185);
        $this->assertSame('0,903', $r->format(3, 0, ',', ''));
    }

    public function testFormatIntegerWithForcedNumberOfDecimals()
    {
        $r = Rational::fromWhole(5);
        $this->assertSame('5.00', $r->format(2, 2));
    }

    public function testFormatDecimalWithForcedNumberOfDecimals()
    {
        $r = Rational::fromWholeAndFraction(5, 1, 10);
        $this->assertSame('5.10', $r->format(2, 2));
    }

    public function testFormatRationalWithMinAndMaxDecimalValues()
    {
        $r = Rational::fromWholeAndFraction(5, 2, 3);
        $this->assertSame('5.667', $r->format(3, 2));
    }

    public function testFormatCurrency()
    {
        $r = Rational::fromWholeAndFraction(5, 2, 3);
        $this->assertSame('5,67 €', $r->formatCurrencyByNumberFormatter(new \NumberFormatter('it_IT', \NumberFormatter::CURRENCY), 'EUR'));
    }
}
