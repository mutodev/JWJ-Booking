<?php

use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

/**
 * Verificación de token
 *
 * @param string $token
 */
function verify_token(string $token)
{
    try {
        $decode = (array)JWT::decode($token, new Key(env('JWT_KEY'), 'HS256'));
        if (isset($decode['exp']) && $decode['exp'] < time())
            throw new HTTPException(lang('Auth.tokenExpired'), Response::HTTP_UNAUTHORIZED);
        return $decode;
    } catch (ExpiredException $e) {
        throw new HTTPException(lang('Auth.tokenExpired'), Response::HTTP_UNAUTHORIZED);
    } catch (SignatureInvalidException $e) {
        throw new HTTPException(lang('Auth.invalidToken'), Response::HTTP_UNAUTHORIZED);
    } catch (BeforeValidException $e) {
        throw new HTTPException(lang('Auth.invalidToken'), Response::HTTP_UNAUTHORIZED);
    } catch (Exception $e) {
        throw new HTTPException(lang('Auth.invalidToken'), Response::HTTP_UNAUTHORIZED);
    }
}
