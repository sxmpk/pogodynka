<?php

namespace App\Controller;

use App\Entity\Measurement;
use App\Service\WeatherUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherApiController extends AbstractController
{
    #[Route('/api/v1/weather', name: 'app_weather_api', methods: ['GET'])]
    public function index(
        #[MapQueryParameter] string $country,
        #[MapQueryParameter] string $city,
        WeatherUtil $weatherUtil,
        #[MapQueryParameter] string $format = 'json',
        #[MapQueryParameter] bool $twig = false,
    ): Response {
        if (!in_array($format, ['json', 'csv'], true)) {
            return $this->json(['error' => 'Dozwolone formaty to json i csv.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $measurements = $weatherUtil->getWeatherForCountryAndCity($country, $city);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="weather.csv"',
            ];
            $locationColumns = array_map(
                static fn (string $value): string => strpbrk($value, ",\"\r\n") === false
                    ? $value
                    : '"'.str_replace('"', '""', $value).'"',
                [$city, $country],
            );
            if ($twig) {
                return $this->render('weather_api/index.csv.twig', [
                    'locationColumns' => $locationColumns,
                    'measurements' => $measurements,
                ], new Response(headers: $headers));
            }

            $rows = ['city,country,date,celsius,fahrenheit'];
            foreach ($measurements as $measurement) {
                $rows[] = implode(',', [
                    ...$locationColumns,
                    $measurement->getDate()->format('Y-m-d'),
                    $measurement->getCelsius(),
                    $measurement->getFahrenheit(),
                ]);
            }

            return new Response(implode("\n", $rows)."\n", Response::HTTP_OK, $headers);
        }

        if ($twig) {
            return $this->render('weather_api/index.json.twig', [
                'city' => $city,
                'country' => $country,
                'measurements' => $measurements,
            ], new Response(headers: ['Content-Type' => 'application/json']));
        }

        return $this->json([
            'city' => $city,
            'country' => $country,
            'measurements' => array_map(static fn (Measurement $measurement) => [
                'date' => $measurement->getDate()->format('Y-m-d'),
                'celsius' => $measurement->getCelsius(),
                'fahrenheit' => $measurement->getFahrenheit(),
            ], $measurements),
        ]);
    }
}
