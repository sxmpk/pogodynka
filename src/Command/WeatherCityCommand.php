<?php

namespace App\Command;

use App\Entity\Measurement;
use App\Service\WeatherUtil;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'weather:city',
    description: 'Wyświetla prognozę pogody dla kraju i miasta.',
)]
class WeatherCityCommand
{
    public function __construct(private readonly WeatherUtil $weatherUtil)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Kod kraju, np. PL')] string $country,
        #[Argument('Nazwa miasta')] string $city,
    ): int {
        try {
            $measurements = $this->weatherUtil->getWeatherForCountryAndCity($country, $city);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->writeln(sprintf('Location: %s (%s)', $city, $country));
        $io->table(['Date', 'Temperature'], array_map(fn (Measurement $measurement) => [
            $measurement->getDate()->format('Y-m-d'),
            $measurement->getCelsius(),
        ], $measurements));

        return Command::SUCCESS;
    }
}
