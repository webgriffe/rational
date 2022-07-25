# Rational - A simple rational number implementation

## Features
This implements a numeric data type that represents a rational number, that is a number that is the result of the division of two integer numbers. In order to reduce the possibility of large values encountering overflow issues, an integer "whole" part is added to the fraction. This means that values are stored as mixed numbers of the form `a + b/c`, where a, b and c are all integers.

This library is fundamentally similar to https://github.com/markrogoyski/math-php/blob/master/src/Number/Rational.php, with the main exception being that this implementation uses the GMP extension internally to detect and report overflow issues.

## Setup
Add the library to your `composer.json` file in your project:
```
{
  "require": {
      "webgriffe/rational": "^1.0"
  }
}
```

Use composer to install the library:

```
$ php composer.phar install
```

Composer will install the library inside your vendor folder. If you don't already use Composer in your project, you may need to explicitly include its autoload file in order to allow PHP to find the library class(es):

```
require_once __DIR__ . '/vendor/autoload.php';
```

## Minimum Requirements
* PHP 8.1 with the gmp-extension installed

## Usage
```php
use Webgriffe\Rational;

//Creates a zero value
$r0 = Rational::zero();

//Creates a one value
$r1 = Rational::one();

//Creates a whole number
$r2 = Rational::fromWhole(-2);

//Creates a variable that stores exactly ⅔ (two thirds), roughly 0.666666...
$r3 = Rational::fromFraction(2, 3);

//Creates a variable that stores exactly 4 + ⅑ (one ninth), roughly 7.111111...
$r4 = Rational::fromWholeAndFraction(4, 1, 9);

//Adds $r1 and $r2 so that $r5 equals -1
$r5 = $r1->add($r2);

//Adds $r3 to $r5: -1 + ⅔ = -⅓
$r6 = $r5->add($r3);

//Subtracts $r6 from $r2: -2 - (-⅓) = -2 + ⅓ = -1 - ⅔
$r7 = $r2->sub($r6);

//Multiply $r7 by $r4: (-1 - ⅔) * (4 + ⅑)
//= -4 - 1/9 - 8/3 - 2/27
//= -4 - 3/27 - 72/27 - 2/27
//= -4 - 77/27
//= -4 - 2 - 23/27
//= -6 - 23/27
$r8 = $r7->mul($r4);

//Divide $r8 by $r3: (-6 - 23/27) / (2/3)
//= (-6 - 23/27) * (3/2)
//= -9 - 23/18
//= -9 - 1 - 5/18
//= -10 - 5/18
$r9 = $r8->div($r3);

//Compute the reciprocal of $r9: 1/(-10 - 5/18)
//= 1/((-180 - 5)/18)
//= 1/(-185/18)
//= 18/-185
//= -18/185
$r10 = $r9->recip();

//$r11 = $r10 + $r1: -18/185 + 1
//= -18/185 + 185/185
//= 167/185
$r11 = $r10->add($r1);

//$r12 = $r11 - $r10: 167/185 - (-18/185)
//= 167/185 + 18/185
//= 185/185
//= 1
$r12 = $r11->sub($r10);
```

## Internal working
The library stores all components of the rational number as PHP integers. This is to make it easier to store these values to databases and other media where arbitrary length integers may be problematic.
Intermediate values are handled through the PHP GMP library in order to avoid overflow issues until the final results are computed. If, however, the final result of each operation exceeds the range of PHP integers, the library reports an overflow error.

Immediately after creation and after every operation, each value is normalized. The purpose of this is to reduce the magnitude of the values stored internally and to make it easier to compare rational numbers and to extract other useful information.

In the context of this library which stores values as `a + b/c`, a normalized value is one where `c > 0`, where `a * b >= 0` (they do not disagree in sign, though one or both can be zero), where `GCD(|b|, c) == 1` (i.e. the fraction `b/c` is simplified) and `|a| < b` (i.e. it is a proper fraction).

## Overflow
At the end of every operation the library converts the intermediate GMP values back to integers. If these values are too large or too small to fit into an integer, a OverflowException is thrown. It is the user's responsibility to catch the exception and act accordingly.

## License
Webgriffe/Rational is licensed under the MIT License.
