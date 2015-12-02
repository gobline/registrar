# Registrar component

The Registrar component takes a Gobline container and allows you to add services to it defined in one or more configuration files.
It loads the right configuration files based on the current environment (dev, prod, ...).

```php
$container = new Gobline\Container();

$registrar = new Gobline\Registrar\Registrar($container, 'dev');
$registrar->register(getcwd().'/config/services.php');
```

In the above code, we set the environment to "dev", and the Registrar will attempt to add the services defined in the following files to the container:
* services.php (if exists)
* services.dev.php (if exists)

The services listed in the configuration file are defined in a PHP array, which its structure follows a convention.
Below is an example of a configuration file:

```php
return [
    Pdo::class => [
        'construct' => [
            'arguments' => ['sqlite:db.sqlite'],
        ],
    ],
    Swift_SmtpTransport::class => [
        'alias' => Swift_Transport::class,
        'construct' => [
            'arguments' => ['smtp.gmail.com', 465, 'ssl'],
        ],
        'configure' => [
            'data' => [
                'username' => 'mdecaffmeyer@gmail.com',
                'password' => '***',
            ],
        ],
    ],
    Gobline\Auth\Authenticator\Db\DbAuthenticator::class => [
        'alias' => Gobline\Auth\Authenticator\AuthenticatorInterface::class,
    ],
    Gobline\Auth\CurrentUser::class => [
        'alias' => Gobline\Auth\CurrentUserInterface::class,
        'configure' => [
            'configurator' => Gobline\Auth\Provider\Gobline\CurrentUserConfigurator::class,
            'data' => [
                'persistence' => 'session',
                'roleUnauthenticated' => 'unauthenticated',
            ],
        ],
    ],
    Doctrine\ORM\EntityManager::class => [
        'construct' => [
            'factory' => App\Provider\EntityManagerFactory::class,
        ],
    ],

];
```
