<?php

namespace App\Tests\Entity;

use App\Entity\Measurement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MeasurementTest extends TestCase
{
    public static function dataGetFahrenheit(): array
    {
        return [
            ['0', '32'],
            ['-100', '-148'],
            ['100', '212'],
            ['0.5', '32.9'],
            ['-0.5', '31.1'],
            ['-40', '-40'],
            ['37', '98.6'],
            ['12.5', '54.5'],
            ['21.25', '70.25'],
            ['-17.25', '0.95'],
        ];
    }

    #[DataProvider('dataGetFahrenheit')]
    public function testGetFahrenheit(string $celsius, string $expectedFahrenheit): void
    {
        $measurement = new Measurement();
        $measurement->setCelsius((float) $celsius);

        self::assertEqualsWithDelta((float) $expectedFahrenheit, $measurement->getFahrenheit(), 0.000001);
    }
}
