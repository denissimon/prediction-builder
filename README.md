PredictionBuilder
=================

[![Latest Stable Version](https://poser.pugx.org/denissimon/prediction-builder/v/stable.svg)](https://packagist.org/packages/denissimon/prediction-builder)
[![Total Downloads](https://poser.pugx.org/denissimon/prediction-builder/downloads)](https://packagist.org/packages/denissimon/prediction-builder)
[![License](https://poser.pugx.org/denissimon/prediction-builder/license.svg)](https://github.com/denissimon/prediction-builder/blob/master/LICENSE)

PredictionBuilder is a library for machine learning that builds predictions using a linear regression.

Requirements
------------

This project requires [PHP 5.4 or higher](http://php.net) because makes use of trait and short array syntax.

Installation
------------

You can install the library by [Composer](https://getcomposer.org). Add this to your project's composer.json:

``` json
"require": {
    "denissimon/prediction-builder": "*"
}
```

Then run `php composer.phar install` (or `composer install`).

Example
-------

``` php
use PredictionBuilder\PredictionBuilder;

require_once __DIR__ . '/vendor/autoload.php';

$data = [[1,20],[2,70],[2,45],[3,81],[5,73],[6,80],[7,110]];
$x = 4.5;

// What is the expected y value for a given x value?
try {
    $prediction = new PredictionBuilder($x, $data);
    $result = $prediction->build(); // y = 76.65
} catch (\Exception $e) {
    echo $e->getMessage(), "\n";
}
```

The returned object has the following properties:

`$result->ln_model` linear model that fits the data: "29.56362+10.46364x"

`$result->cor` correlation coefficient: 0.8348

`$result->x` given x value: 4.5

`$result->y` predicted y value: 76.65

License
-------

Licensed under the [MIT License](https://github.com/denissimon/prediction-builder/blob/master/LICENSE)
