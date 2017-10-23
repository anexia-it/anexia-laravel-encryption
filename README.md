# Laravel Database Encryption

A Laravel package that adds database encryption support to eloquent models.

## 1. Installation and configuration

### 1. Install via composer

Install the module via composer, therefore adapt the require part of your composer.json:
```
"require": {
    "anexia/laravel-encryption": "1.0.0"
}
```
Now run
```
composer update [-o]
```
to add the packages source code to your /vendor directory and update the autoloading.

### 2. Add service provider to app config

```
'providers' => [
    /*
     * Package Service Providers...
     */
    \Anexia\LaravelEncryption\DatabaseEncryptionServiceProvider::class,
]
```

### 3. Add cipher to database config

Currently only Postgres and PGP is supported.

```
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'schema' => 'public',
    'sslmode' => 'prefer',
    'cipher' => 'pgp'
],
```

# 2. Usage

## 2.1 Models

Add the DatabaseEncryption Trait to your eloquent model.

```
<?php

namespace App;

use Anexia\LaravelEncryption\DatabaseEncryption;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use Notifiable, DatabaseEncryption;
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];
    

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    

    /**
     * @return array
     */
    protected static function getEncryptedFields()
    {
        return [
            'password'
        ];
    }
    

    /**
     * @return string
     */
    protected function getEncryptKey()
    {
        return 'thisismysupersecretencryptionkey';
    }
}
```

## 2.2 Updates

Just call the save() method on the model. Fields will be encrypted automatically.

## 2.3 Queries

Use the macro `withDecryptKey` for automatic decryption.

```
$user = User::withDecryptKey('thisismysupersecretencryptionkey')->find(1);
```

In the example above $user will have two properties:
* password: the decrypted password
* password_encrypted: the encrypted value from the database