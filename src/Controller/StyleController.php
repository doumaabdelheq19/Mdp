<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StyleController
{
    #[Route('/assets/css/theme.css', name: 'theme_css')]
    public function themeCss(): Response
    {
        $config = include __DIR__ . '/../../config/config.php';

        $mainColor = $config['main_color'] ?? '#3498db';
        $logoImage = $config['logo_image'] ?? '/assets/img/default-logo.png';

        $css = <<<CSS
        :root {
            --main-color: $mainColor;
            --logo-image: url('$logoImage');
        }
        CSS;

        $response = new Response($css);
        $response->headers->set('Content-Type', 'text/css');

        return $response;
    }
}
