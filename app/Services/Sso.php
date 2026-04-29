<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * Sso
 */
class Sso
{
    /**
     * Decodes and validates a JWT token from Keycloak.
     *
     * - Verifies the signature using public keys (JWK)
     * - Validates the token's basic structure
     * - Validates business rules (issuer, required fields)
     *
     * @param string $token JWT token in string format
     * @return object       Decoded token payload
     * 
     * @throws \RuntimeException  If no keys are configured
     * @throws \DomainException   If the token does not comply with business rules
     * @throws \Throwable         If the token is invalid (signature, expiration, etc.)
     */
    public function decodeAndValidate(string $token): object
    {

        /**
         * Retrieves the public key JSON (JWK) from the configuration.
         * These keys are used to validate the token's signature.
         */
        $keysJson = config('keycloak.keys');

        if (empty($keysJson)) {
            throw new \RuntimeException('La clave pública no está configurada');
        }

        //Constructs the expected token issuer.
        $issuer = config('keycloak.base_url') . 'realms/' . config('keycloak.realm');

        //Convert the JSON keys to an associative array
        $jwkArray = json_decode($keysJson, true);

        //Parse the keys into a format that the JWT library can use
        $keys = JWK::parseKeySet($jwkArray);

        /**
         * Decodes the token:
         * - Automatically validates the signature
         * - Validates the expiration date (exp)
         * - Validates “nbf” (not before)
         *
         * May throw exceptions such as:
         * - ExpiredException
         * - SignatureInvalidException
         * - BeforeValidException
         */
        $decoded = JWT::decode($token, $keys);

        //Verify that the token contains the required minimum fields
        if (!isset($decoded->email) || !isset($decoded->iss)) {
            throw new \DomainException('Token malformado');
        }

        /**
         * Verifies that the token issuer matches the expected one
         * This prevents tokens issued by other realms or environments
         */
        if ($decoded->iss !== $issuer) {
            throw new \DomainException('Issuer inválido');
        }

        /**
         * Returns the decoded payload of the token
         */
        return $decoded;
    }
}
