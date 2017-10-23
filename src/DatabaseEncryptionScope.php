<?php

namespace Anexia\EloquentEncryption;


use Illuminate\Database\Eloquent\Builder;
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
    protected $extensions = ['WithDecryptKey'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        foreach ($model::getEncryptedFields() as $encryptedField) {
            $builder->addSelect(DB::raw("$encryptedField as {$encryptedField}_encrypted"));
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
            /** @var DatabaseEncryptionServiceInterface $encryptionService */
            $encryptionService = $model::getEncryptionService();
            foreach ($model::getEncryptedFields() as $encryptedField) {
                $decryptStmt = $encryptionService->getDecryptExpression($encryptedField, $decryptKey);
                $builder->addSelect(DB::raw("$decryptStmt as $encryptedField"));
            }
            return $builder;
        });
    }
}