<?php

namespace Anexia\EloquentEncryption;


interface DatabaseEncryptionServiceInterface
{
    /**
     * Returns a encrypt expression
     * @param string $text text to encrypt or field name
     * @param string $encryptionKey
     * @param bool $quote use false if $text is a field name
     * @return string
     */
    public function getEncryptExpression($text, $encryptionKey, $quote = true);

    /**
     * Returns a encrypt expression
     * @param string $text text to encrypt or field name
     * @param string $decryptionKey
     * @param bool $quote use false if $text is a field name
     * @return string
     */
    public function getDecryptExpression($text, $decryptionKey, $quote = false);
}