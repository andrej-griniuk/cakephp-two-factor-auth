<?php
declare(strict_types=1);

namespace TwoFactorAuth\Authenticator;

use Authentication\Authenticator\Result as BaseResult;

/**
 * Authentication result object
 */
class Result extends BaseResult
{
    /**
     * General failure due to any other circumstances.
     */
    public const TWO_FACTOR_AUTH_REQUIRED = 'TWO_FACTOR_REQUIRED';

    /**
     * General failure due to any other circumstances.
     */
    public const TWO_FACTOR_AUTH_FAILED = 'TWO_FACTOR_FAILED';
}
