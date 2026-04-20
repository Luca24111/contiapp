<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/healthz/deep', name: 'app_health_deep', methods: ['GET'])]
    public function deep(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
            $databaseStatus = 'ok';
        } catch (\Throwable $exception) {
            $databaseStatus = sprintf('error: %s', $exception->getMessage());
        }

        $statusCode = $databaseStatus === 'ok' ? 200 : 503;

        return $this->json([
            'status' => $statusCode === 200 ? 'ok' : 'degraded',
            'database' => $databaseStatus,
            'env' => $this->getParameter('kernel.environment'),
        ], $statusCode);
    }
}
