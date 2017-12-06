<?php

namespace Anexia\LaravelEncryption;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class DatabaseEncryptionScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = ['WithDecryptKey', 'WhereDecrypted'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $encryptedFields = $model::getEncryptedFields();
        if (!empty($encryptedFields)) {
            $builder->setEncryptionModel($model);

            foreach ($encryptedFields as $encryptedField) {
                $builder->addEncryptionSelect([
                    'column'    => $encryptedField,
                    'alias'     => "{$encryptedField}_encrypted",
                    'select'    => DB::raw("$encryptedField as {$encryptedField}_encrypted")
                ]);
            }
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * Add the with-decrypt-key extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    protected function addWithDecryptKey(Builder $builder)
    {
        $builder->macro('withDecryptKey', function (Builder $builder, $decryptKey) {
            $model = $builder->getModel();
            $encryptedFields = $model::getEncryptedFields();
            if (!empty($encryptedFields)) {
                $builder->setEncryptionModel($model);

                /** @var DatabaseEncryptionServiceInterface $encryptionService */
                $encryptionService = $model::getEncryptionService();
                foreach ($encryptedFields as $encryptedField) {
                    $decryptStmt = $encryptionService->getDecryptExpression($encryptedField, $decryptKey);
                    $builder->addEncryptionSelect([
                        'column' => $encryptedField,
                        'select' => DB::raw("$decryptStmt as $encryptedField")
                    ]);
                }
            }
            return $builder;
        });
    }

    /**
     * Add the where-decrypted extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    protected function addWhereDecrypted(Builder $builder)
    {
        $builder->macro('whereDecrypted', function (Builder $builder, $attribute, $value, $decryptKey) {
            $model = $builder->getModel();
            $encryptedFields = $model::getEncryptedFields();
            if (in_array($attribute, $encryptedFields)) {
                $encryptionService = $model::getEncryptionService();
                $builder->whereRaw($encryptionService->getDecryptExpression($attribute, $decryptKey) . " = :value", ['value' => $value]);
            }
            return $builder;
        });
    }
}