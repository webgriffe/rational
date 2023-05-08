<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;

final class ToDecimalStringTest extends TestCase
{
    public function testNegativeNumberOfDecimalsIsNotAllowed()
    {
        $r = Rational::fromWhole(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of decimals cannot be negative');
        $r->toDecimalString(1, -1);
    }

    public function testToDecimalStringChecksThatFormatIsSensible()
    {
        $r = Rational::fromWhole(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The minimum number of decimals cannot be larger than the maximum number of decimals');
        $r->toDecimalString(1, 2);
    }

    public function testToDecimalString1()
    {
        $r = Rational::fromWhole(10);
        $this->assertEquals('10', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('10', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('10', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('10', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString2()
    {
        $r = Rational::fromWhole(-10);
        $this->assertEquals('-10', $r->toDecimalString(3, 0,  Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('-10', $r->toDecimalString(3, 0,  Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('-10', $r->toDecimalString(3, 0,  Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-10', $r->toDecimalString(3, 0,  Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString3()
    {
        $r = Rational::fromWhole(-10);
        $this->assertEquals('-10.00', $r->toDecimalString(2, 2,  Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('-10.00', $r->toDecimalString(2, 2, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('-10.00', $r->toDecimalString(2, 2, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-10.00', $r->toDecimalString(2, 2, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString4()
    {
        $r = Rational::fromWholeAndFraction(2, 1, 4);
        $this->assertEquals('3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('2', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('2', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('2', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString5()
    {
        $r = Rational::fromWholeAndFraction(2, 3, 4);
        $this->assertEquals('3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('2', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString6()
    {
        $r = Rational::fromWholeAndFraction(2, 1, 4);
        $this->assertEquals('2.3', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('2.3', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('2.2', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('2.2', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString7()
    {
        $r = Rational::fromWholeAndFraction(2, 3, 4);
        $this->assertEquals('2.8', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('2.8', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('2.7', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('2.7', $r->toDecimalString(1, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString8()
    {
        $r = Rational::fromWholeAndFraction(2, 3, 4);
        $this->assertEquals('2.75', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('2.75', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('2.75', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('2.75', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString9()
    {
        $r = Rational::fromFraction(10, 3);
        $this->assertEquals('3.334', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('3.333', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('3.333', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('3.333', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString10()
    {
        $r = Rational::fromWholeAndFraction(3, 999, 1000);
        $this->assertEquals('4.0', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('4.0', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('4.0', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('3.99', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString11()
    {
        $r = Rational::fromWholeAndFraction(-3, -999, 1000);
        $this->assertEquals('-3.99', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('-4.0', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('-4.0', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-4.0', $r->toDecimalString(2, 1, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString12()
    {
        $r = Rational::fromWholeAndFraction(-3, -1, 3);
        $this->assertEquals('-3.333', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('-3.333', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('-3.333', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-3.334', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString13()
    {
        $r = Rational::zero();
        $this->assertEquals('0.000', $r->toDecimalString(3, 3, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('0.000', $r->toDecimalString(3, 3, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('0.000', $r->toDecimalString(3, 3, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('0.000', $r->toDecimalString(3, 3, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString14()
    {
        $r = Rational::fromWholeAndFraction(3, 1, 2);
        $this->assertEquals('4', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('4', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString15()
    {
        $r = Rational::fromWholeAndFraction(-3, -1, 2);
        $this->assertEquals('-3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('-4', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('-3', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-4', $r->toDecimalString(0, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString16()
    {
        $r = Rational::fromFraction(-1, 1000);
        $this->assertEquals('-0.001', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('-0.001', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('-0.001', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-0.001', $r->toDecimalString(3, 0, Rational::TO_DECIMAL_FLOOR));
    }

    public function testToDecimalString17()
    {
        $r = Rational::fromFraction(-1, 1000);
        $this->assertEquals('0', $r->toDecimalString(2, 0, Rational::TO_DECIMAL_CEIL));
        $this->assertEquals('0', $r->toDecimalString(2, 0, Rational::TO_DECIMAL_ROUND_HALF_UP));
        $this->assertEquals('0', $r->toDecimalString(2, 0, Rational::TO_DECIMAL_ROUND_HALF_DOWN));
        $this->assertEquals('-0.01', $r->toDecimalString(2, 0, Rational::TO_DECIMAL_FLOOR));
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
