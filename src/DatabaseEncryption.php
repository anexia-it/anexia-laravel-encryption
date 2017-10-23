<?php

namespace Anexia\LaravelEncryption;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait DatabaseEncryption
{
    /**
     * @var DatabaseEncryptionServiceInterface
     */
    private static $encryptionService;

    /**
     * Initialize global scope for decryption
     * @return void
     */
    public static function bootDatabaseEncryption()
    {
        static::addGlobalScope(new DatabaseEncryptionScope());
    }

    /**
     * Returns the encrypted fields
     * @return array
     */
    public static function getEncryptedFields()
    {
        return [];
    }

    /**
     * Returns the encrypt key
     * @return string
     */
    abstract protected function getEncryptKey();

    /**
     * @return DatabaseEncryptionServiceInterface
     */
    public static function getEncryptionService()
    {
        if (null === static::$encryptionService) {
            /** @var EncryptionServiceManager $esm */
            $esm = app('db.encryption_service');
            $className = get_called_class();
            static::$encryptionService = $esm->getEncryptionServiceForModel(new $className());
        }

        return static::$encryptionService;
    }

    /**
     * Perform insert with encryption
     * @param Builder $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        $encryptedFields = static::getEncryptedFields();
        if (count($encryptedFields) && !$this->getEncryptKey()) {
            throw new \RuntimeException("No encryption key specified");
        }
        foreach ($encryptedFields as $encryptedField) {
            $this->attributes[$encryptedField] = DB::raw(static::getEncryptionService()->getEncryptExpression($this->attributes[$encryptedField], static::getEncryptKey()));
        }
        return parent::performInsert($query);
    }

    /**
     * Perform update with encryption
     * @param Builder $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        $encryptedFields = static::getEncryptedFields();
        if (count($encryptedFields) && !$this->getEncryptKey()) {
            throw new \RuntimeException("No encryption key specified");
        }
        foreach ($encryptedFields as $encryptedField) {
            $this->attributes[$encryptedField] = DB::raw(static::getEncryptionService()->getEncryptExpression($this->attributes[$encryptedField], static::getEncryptKey()));
        }
        return parent::performUpdate($query);
    }
}