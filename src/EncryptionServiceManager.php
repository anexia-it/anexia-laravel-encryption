<?php

namespace Anexia\LaravelEncryption;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;

class EncryptionServiceManager
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $services = [];

    /**
     * EncryptionServiceManager constructor.
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @param Model $model
     * @return DatabaseEncryptionServiceInterface
     */
    public function getEncryptionServiceForModel(Model $model)
    {
        $connectionName = $model->getConnectionName();
        return $this->getEncryptionService($connectionName);
    }

    /**
     * @param string $connectionName
     * @return DatabaseEncryptionServiceInterface
     */
    public function getEncryptionService($connectionName)
    {
        if (!isset($this->services[$connectionName])) {
            $config = $this->configuration($connectionName);

            if (!isset($config['cipher'])) {
                throw new \RuntimeException("No cipher set for connection '$connectionName'");
            }

            $className = '\\Anexia\\LaravelEncryption\\';
            switch ($config['driver']) {
                case 'pgsql':
                    $className .= 'Postgres';
                    break;
                default:
                    throw new \InvalidArgumentException("No supported cipher for driver '{$config['driver']}'");
            }

            switch ($config['cipher']) {
                case 'pgp':
                    $className .= 'Pgp';
                    break;
                default:
                    throw new \InvalidArgumentException("Cipher '{$config['cipher']}' not supported by driver '{$config['driver']}'");
            }

            $className .= 'Encryption';

            if (!class_exists($className)) {
                throw new \RuntimeException("Class '$className' not found");
            }

            $this->services[$connectionName] = new $className();
        }

        return $this->services[$connectionName];
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new \InvalidArgumentException("Database [$name] not configured.");
        }

        return $config;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['database.default'];
    }
}