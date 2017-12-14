<?php

namespace Anexia\LaravelEncryption;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class DatabaseEncryptionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('whereDecrypted', function($attribute, $operator = null, $value = null, $decryptKey = null){
            list($value, $operator, $decryptKey) = $this->query->prepareDecryptionValueOperatorAndDecryptKey(
                $value, $operator, $decryptKey, func_num_args() == 3
            );

            $model = $this->getModel();
            $encryptedFields = $model::getEncryptedFields();
            if (in_array($attribute, $encryptedFields)) {
                $encryptionService = $model::getEncryptionService();
                $this->whereRaw($encryptionService->getDecryptExpression($attribute, $decryptKey) . " " . $operator . " ?", [$value]);
            } else {
                $this->where($attribute, $operator, $value);
            }

            return $this;
        });

        Builder::macro('orWhereDecrypted', function($attribute, $operator = null, $value = null, $decryptKey = null){
            list($value, $operator, $decryptKey) = $this->query->prepareDecryptionValueOperatorAndDecryptKey(
                $value, $operator, $decryptKey, func_num_args() == 3
            );
            $model = $this->getModel();
            $encryptedFields = $model::getEncryptedFields();
            if (in_array($attribute, $encryptedFields)) {
                $encryptionService = $model::getEncryptionService();
                $this->orWhereRaw($encryptionService->getDecryptExpression($attribute, $decryptKey) . " " . $operator . " ?", [$value]);
            } else {
                $this->orWhere($attribute, $operator, $value);
            }
            return $this;
        });

        Builder::macro('orderByDecrypted', function($attribute, $direction = 'asc', $decryptKey = null){
            $model = $this->getModel();
            $encryptedFields = $model::getEncryptedFields();
            if (in_array($attribute, $encryptedFields)) {
                $encryptionService = $model::getEncryptionService();
                $this->orderByRaw($encryptionService->getDecryptExpression($attribute, $decryptKey) . " " . $direction);
            } else {
                $this->orderBy($attribute, $direction);
            }
            return $this;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('db.encryption_service', function ($app) {
            return new EncryptionServiceManager($app);
        });
    }
}