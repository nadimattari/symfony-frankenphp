<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the Sakai demo fixtures through a local API so the typed frontend
 * services never fetch static files or remote demo URLs directly.
 *
 * The fixture names are an explicit whitelist: user input never reaches the
 * filesystem path.
 */
final class DemoDataController extends AbstractController
{
    private const FIXTURES = [
        'products'               => 'products.json',
        'products-small'         => 'products-small.json',
        'products-orders-small'  => 'products-orders-small.json',
        'customers'              => 'customers-large.json',
        'customers-small'        => 'customers-small.json',
        'customers-medium'       => 'customers-medium.json',
        'customers-large'        => 'customers-large.json',
        'customers-xlarge'       => 'customers-xlarge.json',
        'countries'              => 'countries.json',
        'tree-nodes'             => 'treenodes.json',
        'tree-table-nodes'       => 'treetablenodes.json',
        'photos'                 => 'photos.json',
    ];

    #[Route(
        '/api/demo/{fixture}',
        name: 'api_demo_data',
        requirements: ['fixture' => 'products|products-small|products-orders-small|customers|customers-small|customers-medium|customers-large|customers-xlarge|countries|tree-nodes|tree-table-nodes|photos'],
        methods: ['GET']
    )]
    public function __invoke(string $fixture): JsonResponse
    {
        if (!isset(self::FIXTURES[$fixture])) {
            throw $this->createNotFoundException('Unknown demo fixture.');
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/demo/data/' . self::FIXTURES[$fixture];
        if (!is_file($path)) {
            throw $this->createNotFoundException('Demo fixture file is missing.');
        }

        $payload = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $items = $payload['data'] ?? $payload['root'] ?? [];

        return $this->json(['data' => $items]);
    }
}
