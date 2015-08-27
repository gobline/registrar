<?php

/*
 * Mendo Framework
 *
 * (c) Mathieu Decaffmeyer <mdecaffmeyer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mendo\Registrar;

use Pimple\Container;

/**
 * @author Mathieu Decaffmeyer <mdecaffmeyer@gmail.com>
 */
class Registrar
{
    private $environment;
    private $container;

    public function __construct(Container $container, $environment = null)
    {
        $environment = (string) $environment;
        $this->environment = $environment ?: ($this->isLocalHost() ? 'dev' : 'prod');

        $this->container = $container;
        $this->container['environment'] = $this->environment;
    }

    private function isLocalHost()
    {
        return $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1';
    }

    public function register($file)
    {
        $services = [];

        if (is_file($file.'.base.php')) {
            $services = array_merge($services, include $file.'.base.php');
        }

        if (is_file($file.'.'.$this->environment.'.php')) {
            $services = array_merge($services, include $file.'.'.$this->environment.'.php');
        }

        foreach ($services as $service => $config) {
            if (empty($config['serviceProvider'])) {
                throw new \Exception('Service provider not specified');
            }

            $dependencies = isset($config['dependencies']) ? $config['dependencies'] : [];
            $parameters = isset($config['parameters']) ? $config['parameters'] : [];

            $parameters = array_merge($dependencies, $parameters);
            $serviceParameters = [];

            foreach ($parameters as $key => $value) {
                $serviceParameters[$service.'.'.$key] = $value;
            }

            $serviceProvider = $config['serviceProvider'];
            $this->container->register(new $serviceProvider($service), $serviceParameters);
        }

        return $this;
    }

    public function getContainer() 
    {
        return $this->container;
    }
}
