<?php

namespace App\Controller;

use App\Form\ExternalAPIType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExternalAPIController extends AbstractController
{
    #[Route('/externalapi', name: 'app_external_api', methods: ['GET', 'POST'])]
    public function index(Request $request, HttpClientInterface $httpClient): Response
    {
        $form = $this->createForm(ExternalAPIType::class, []);
        $form->handleRequest($request);
        $forecast = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $coordinates = $form->getData();

            try {
                $response = $httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                    'query' => [
                        'latitude' => $coordinates['latitude'],
                        'longitude' => $coordinates['longitude'],
                        'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
                        'timezone' => 'UTC',
                    ],
                    'timeout' => 5,
                    'max_duration' => 10,
                ]);
                $daily = $response->toArray()['daily'] ?? [];
                if (is_array($daily['time'] ?? null) && $daily['time'] !== []) {
                    $forecast = $daily;
                } else {
                    $form->addError(new FormError('Open-Meteo nie zwróciło prognozy dla podanych współrzędnych.'));
                }
            } catch (ExceptionInterface) {
                $form->addError(new FormError('Nie udało się pobrać prognozy z Open-Meteo. Spróbuj ponownie.'));
            }
        }

        return $this->render('external_api/index.html.twig', [
            'form' => $form,
            'forecast' => $forecast,
        ]);
    }
}
