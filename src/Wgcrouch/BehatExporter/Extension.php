<?php

namespace Wgcrouch\BehatExporter;

use Symfony\Component\Config\FileLocator;
use Behat\Behat\Extension\Extension as BaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Extension extends BaseExtension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load('core.yml');

    }
}
