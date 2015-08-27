# Registrar Component - Mendo Framework

The Mendo Registrar component takes a [Pimple container](http://pimple.sensiolabs.org/) and allows you to add services to it defined in one or more configuration files.
It loads the right configuration files based on the current environment (dev, prod, ...).

```php
$container = new Pimple\Container();

$registrar = new Mendo\Registrar\Registrar($container, 'dev');
$registrar->register(getcwd().'/config/services');
```

In the above code, we set the environment to "dev", and the Registrar will attempt to add the services defined in the following files to the container:
* services.base.php (if exists)
* services.dev.php (if exists)

The services listed in the configuration file are defined in a PHP array, which its structure follows a convention.
Below is an example of a configuration file:

```php
return [
    'pdo' => [
        'serviceProvider' => '\\Mendo\\Pdo\\Provider\\Pimple\\PdoServiceProvider',
        'parameters' => [
            'dsn' => 'sqlite:db.sqlite',
        ],
    ],
    'auth.adapter' => [
        'serviceProvider' => '\\Mendo\\Auth\\Provider\\Pimple\\DbAuthenticatorServiceProvider',
        'dependencies' => [
            'pdo' => 'pdo',
        ],
        'parameters' => [
            'table' => 'users',
        ],
    ],
    'mailer' => [
        'serviceProvider' => '\\Mendo\\Mailer\\Provider\\Pimple\\SwiftMailerServiceProvider',
        'parameters' => [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'secure' => 'ssl',
            'user' => 'example@gmail.com',
            'password' => '123456',
            'from.default' => ['example@gmail.com' => 'Example'],
        ],
    ],
];
```

In the configuration file above, we define a service called [pdo](https://github.com/mendoframework/pdo). We specify the class that will create the service and the parameters it needs.
```Mendo\Pdo\Provider\Pimple\PdoServiceProvider``` is the class responsible to create the service (I call it a *service provider*).
The service provider requires a few parameters like the dsn, username, password, etc. to connect to the database.
As I use sqlite for this example, just the dsn is sufficient.

The following service defined in the file allows to [authenticate](https://github.com/mendoframework/auth) a user against a database.
It depends on a PDO instance, thus we add our previous defined pdo service as a dependency to the authenticator service.

Below is an example of a service provider:
```php
namespace Mendo\Pdo\Provider\Pimple;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Mendo\Pdo\LazyPdo;

class PdoServiceProvider implements ServiceProviderInterface
{
    private $reference;

    public function __construct($reference = 'pdo')
    {
        $this->reference = $reference;
    }

    public function register(Container $container)
    {
        $reference = $this->reference;

        $container[$reference.'.lazy'] = true;
        $container[$reference.'.username'] = null;
        $container[$reference.'.password'] = null;
        $container[$reference.'.options'] = array();

        $container[$reference] = function ($c) use ($reference) {
            if (empty($c[$reference.'.dsn'])) {
                throw new \Exception('dsn not specified');
            }

            $dsn = $c[$reference.'.dsn'];
            $username = $c[$reference.'.username'];
            $password = $c[$reference.'.password'];
            $options = $c[$reference.'.options'];

            if ($c[$reference.'.lazy']) {
                return new LazyPdo($dsn, $username, $password, $options);
            }

            return new \PDO($dsn, $username, $password, $options);
        };
    }
}
```

The services are registered through the Registrar, and can then be accessed from the container:
```php
$db = $container['pdo'];
```

If the environment has not explicitly been specified when instantiating the Registrar, the Registrar will set it automatically to "dev" if the server is running locally, "prod" otherwise.