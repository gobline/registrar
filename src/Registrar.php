<?php

/*
 * Gobline Framework
 *
 * (c) Mathieu Decaffmeyer <mdecaffmeyer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gobline\Registrar;

use Gobline\Container\ContainerInterface;

/**
 * @author Mathieu Decaffmeyer <mdecaffmeyer@gmail.com>
 */
class Registrar
{
    private $environment;
    private $container;

    public function __construct(ContainerInterface $container, $environment = null)
    {
        $environment = (string) $environment;
        $this->environment = $environment ?: ($this->isLocalHost() ? 'dev' : 'prod');

        $this->container = $container;
    }

    private function isLocalHost()
    {
        return $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1';
    }

    public function register($file)
    {
        $services = [];

        if (!is_file($file)) {
            throw new \RuntimeException('Configuration file "'.$file.'" not found');
        }

        $services = array_merge($services, include $file);

        $file = preg_replace('/\.php$/', '', $file).'.'.$this->environment.'.php';

        if (is_file($file)) {
            $services = array_merge($services, include $file);
        }

        foreach ($services as $serviceClassName => $config) {
            $alias = isset($config['alias']) ? $config['alias'] : null;

            if ($alias) {
                $this->container->alias($alias, $serviceClassName);
            }

            $register = !$this->container->has($serviceClassName) || isset($config['construct']);

            if ($register) {
                $construct = isset($config['construct']) ? $config['construct'] : [];
                $shared = isset($construct['shared']) ? $construct['shared'] : true;
                $factory = isset($construct['factory']) ? $construct['factory'] : null;
                $arguments = isset($construct['arguments']) ? $construct['arguments'] : null;

                if ($factory) {
                    $this->container->delegate($serviceClassName, $factory, $arguments, $shared);
                } else {
                    $this->container->set($serviceClassName, $arguments, $shared);
                }
            }

            $configure = isset($config['configure']) ? $config['configure'] : null;

            if ($configure) {
                $configurator = isset($configure['configurator']) ? $configure['configurator'] : null;
                $data = isset($configure['data']) ? $configure['data'] : [];

                $this->container->configure($serviceClassName, $configurator, $data);
            }
        }

        return $this;
    }
}
