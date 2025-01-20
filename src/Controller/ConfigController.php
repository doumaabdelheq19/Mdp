<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ConfigController extends AbstractController
{
    public function getGlobalConfig(): array
    {
        // Load the config.php file
        $config = include __DIR__ . '/../../config/config.php';

        // Return the global variables (add more as needed)
        return [
            'main_color' => $config['main_color'] ?? '#3498db',
            'logo_image' => $config['logo_image'] ?? '/assets/img/default-logo.png',
        ];
    }
}
