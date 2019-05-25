<?php

namespace OCA\Passwords\Encryption\Keychain;

use OCA\Passwords\Db\Keychain;

/**
 * Interface KeychainEncryptionInterface
 *
 * @package OCA\Passwords\Encryption\Keychain
 */
interface KeychainEncryptionInterface {

    /**
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * @return string
     */
    public function getType(): string ;

    /**
     * @param Keychain $object
     *
     * @return Keychain
     */
    public function encryptKeychain(Keychain $object): Keychain;

    /**
     * @param Keychain $object
     *
     * @return Keychain
     */
    public function decryptKeychain(Keychain $object): Keychain;

}