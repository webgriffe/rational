<?php

declare(strict_types=1);

namespace Webgriffe\Rational\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Webgriffe\Rational\Rational;
use Webgriffe\Rational\Tests\Unit\ExtensionTest\ExtendingClass;

final class ExtensionTest extends TestCase
{
    public function testExtend(): void
    {
        //Use one of the static methods to get an instance of that class
        $c = ExtendingClass::one();

        //Do an operation on that class that extends Rational
        $one = Rational::one();
        $result = $c->add($one);

        //Check that the result is the same type as the initial object
        $this->assertInstanceOf(Rational::class, $result);
        $this->assertEquals(get_class($c), get_class($result));
        $this->assertNotEquals(get_class($one), get_class($result));
    }
}