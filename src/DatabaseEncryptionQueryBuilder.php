<?php

namespace Anexia\LaravelEncryption;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class DatabaseEncryptionQueryBuilder extends Builder
{
    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public $encryptionColumns = [];

    /**
     * @var Model
     */
    public $encryptionModel;

    /**
     * @param Model $encryptionModel
     */
    public function setEncryptionModel(Model $encryptionModel)
    {
        $this->encryptionModel = $encryptionModel;
    }

    /**
     * Add a new select encryption_column to the query.
     *
     * @param array $column; array with [originalColumnName => selectValue]
     * @return $this
     */
    public function addEncryptionSelect($column = [])
    {
        $columnName = (isset($column['alias']) && !empty($column['alias'])) ? $column['alias'] : $column['column'];
        $this->encryptionColumns[$columnName] = $column;

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        // encrypted columns replace their default (original) columns
        if ($this->encryptionModel instanceof Model
            && in_array(DatabaseEncryption::class, class_uses($this->encryptionModel))
        ) {
            $model = $this->encryptionModel;
            $modelEncryptionColumns = $model::getEncryptedFields();
            if (!empty($modelEncryptionColumns)) {
                if ($columns == ['*']) {
                    $this->columns = [];

                    /*
                     * Select all visible columns (avoid select *)
                     */
                    $visibles = array_diff(array_merge($model->getFillable(), $model->getGuarded()), $model->getHidden());
                    $unencryptedVisibles = array_unique(array_diff($visibles, array_column($this->encryptionColumns, 'column')));
                    sort($unencryptedVisibles);

                    $primaryKey = [
                        $model->getKeyName()
                    ];

                    $attributes = array_merge($primaryKey, $unencryptedVisibles);

                    if ($model->usesTimestamps()) {
                        $attributes[] = $model->getCreatedAtColumn();
                        $attributes[] = $model->getUpdatedAtColumn();
                    }

                    // add all visible unencrypted columns
                    foreach ($attributes as $attribute) {
                        $this->columns[] = $attribute;
                    }

                    // add all encrypted columns
                    $this->columns = array_merge($this->columns, array_column($this->encryptionColumns, 'select'));
                } else {
                    $this->columns = [];

                    // add all visible unencrypted columns
                    $encryptionColumnNames = array_column($this->encryptionColumns, 'column');
                    foreach ($columns as $column) {
                        if (!in_array($column, $encryptionColumnNames)) {
                            $this->columns[] = $column;
                        }
                    }
                    // add the encrypted columns (and decrypted, if 'withDecryptionKey' was given)
                    foreach ($this->encryptionColumns as $encryptionColumn){
                        if (in_array($encryptionColumn['column'], $columns)) {
                            $this->columns[] = $encryptionColumn['select'];
                        }
                    }
                }
            }
        }

        $results = $this->processor->processSelect($this, $this->runSelect());

        $this->columns = $original;

        return collect($results);
    }
}