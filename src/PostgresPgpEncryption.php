<?php

namespace Anexia\LaravelEncryption;


use Illuminate\Support\Facades\DB;

class PostgresPgpEncryption implements DatabaseEncryptionServiceInterface
{

    /**
     * Returns a encrypt expression
     * @param string $text text to encrypt or field name
     * @param string $encryptionKey
     * @param bool $quote use false if $text is a field name
     * @return string
     */
    public function getEncryptExpression($text, $encryptionKey, $quote = true)
    {
        $quotedText = $quote ? DB::connection()->getPdo()->quote($text) : $text;
        $quotedEncryptKey = DB::connection()->getPdo()->quote($encryptionKey);
        return "pgp_sym_encrypt($quotedText, $quotedEncryptKey)";
    }

    /**
     * Returns a encrypt expression
     * @param string $text text to encrypt or field name
     * @param string $decryptionKey
     * @param bool $quote use false if $text is a field name
     * @return string
     */
    public function getDecryptExpression($text, $decryptionKey, $quote = false)
    {
        $quotedText = $quote ? DB::connection()->getPdo()->quote($text) : $text;
        $quotedDecryptKey = DB::connection()->getPdo()->quote($decryptionKey);
        return "pgp_sym_decrypt({$quotedText}::bytea, $quotedDecryptKey)";
    }
}