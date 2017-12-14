<?php

namespace Anexia\LaravelEncryption;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait DatabaseEncryption
{
    /**
     * @var DatabaseEncryptionServiceInterface
     */
    protected static $encryptionService;

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
        $originalAttributes = $this->attributes;
        foreach ($encryptedFields as $encryptedField) {
            if (isset($this->attributes[$encryptedField])) {
                $this->attributes[$encryptedField] = DB::raw(static::getEncryptionService()->getEncryptExpression($this->attributes[$encryptedField], static::getEncryptKey()));
            }
        }
        $inserted = parent::performInsert($query);

        // reset the attributes to the decrypted values
        foreach ($encryptedFields as $encryptedField) {
            if (isset($this->attributes[$encryptedField])) {
                $this->attributes[$encryptedField] = $originalAttributes[$encryptedField];
            }
        }
        return $inserted;
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
        $originalAttributes = $this->attributes;
        foreach ($encryptedFields as $encryptedField) {
            if (isset($this->attributes[$encryptedField])) {
                $this->attributes[$encryptedField] = DB::raw(static::getEncryptionService()->getEncryptExpression($this->attributes[$encryptedField], static::getEncryptKey()));
            }
        }
        $updated = parent::performUpdate($query);

        // reset the attributes to the decrypted values
        foreach ($encryptedFields as $encryptedField) {
            if (isset($this->attributes[$encryptedField])) {
                $this->attributes[$encryptedField] = $originalAttributes[$encryptedField];
            }
        }
        return $updated;
    }

    /**
     * Get a new query builder instance for the connection.
     * Use the package's DatabaseEncryptionQueryBuilder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new DatabaseEncryptionQueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        $encryptedFields = static::getEncryptedFields();

        if (count($this->getVisible()) > 0) {
            $visibles = $this->getVisible();
            $intValues = array_intersect_key($values, array_flip($visibles));

            foreach ($encryptedFields as $encryptedField) {
                if (in_array($encryptedField, $visibles) && isset($values[$encryptedField])) {
                    $intValues["{$encryptedField}_encrypted"] = $values[$encryptedField];
                }
                if (in_array($encryptedField, $visibles) && isset($values["{$encryptedField}_encrypted"])) {
                    $intValues["{$encryptedField}_encrypted"] = $values["{$encryptedField}_encrypted"];
                }
            }

            $values = $intValues;
        }

        if (count($this->getHidden()) > 0) {
            $hiddenFields = $this->getHidden();
            $values = array_diff_key($values, array_flip($hiddenFields));

            foreach ($encryptedFields as $encryptedField) {
                if (in_array($encryptedField, $hiddenFields)) {
                    unset($values["{$encryptedField}_encrypted"]);
                }
            }
        }

        return $values;
    }
}