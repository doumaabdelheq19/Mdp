<?php

namespace App\Twig;

use App\Controller\ConfigController;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    private $configController;

    public function __construct(ConfigController $configController)
    {
        $this->configController = $configController;
    }

    public function getFunctions(): array
    {
        return [
            // This makes the global config accessible in Twig as `get_config`
            new TwigFunction('get_config', [$this, 'getConfig']),
        ];
    }

    public function getConfig(string $key)
    {
        $config = $this->configController->getGlobalConfig();
        return $config[$key] ?? null;
    }
}

