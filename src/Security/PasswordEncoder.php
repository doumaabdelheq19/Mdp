<?php

namespace App\Security;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 25/09/2019
 * Time: 19:21
 */

class PasswordEncoder implements PasswordEncoderInterface
{

    /**
     * Encodes the raw password.
     *
     * @param string $raw The password to encode
     * @param string $salt The salt
     *
     * @return string The encoded password
     */
    public function encodePassword($raw, $salt)
    {
        // TODO: Implement encodePassword() method.

        $options = [
            'cost' => 12,
        ];
        return password_hash($raw.'{'.$salt.'}', PASSWORD_BCRYPT, $options);
    }

    public function needsRehash($encoded): bool
    {
        // Implement the logic for checking if the encoded password needs rehashing.
        // This method is required in Symfony 5.4.
        // You might return true or false based on your specific needs.
        // For example:
        return false;
    }

    /**
     * Checks a raw password against an encoded password.
     *
     * @param string $encoded An encoded password
     * @param string $raw A raw password
     * @param string $salt The salt
     *
     * @return bool true if the password is valid, false otherwise
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        // TODO: Implement isPasswordValid() method.
        return password_verify($raw.'{'.$salt.'}', $encoded);
    }
}