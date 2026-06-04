<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Padosoft\Rebel\Bridge\Laragear2fa\Testing\FakeTwoFactorValidator;

/**
 * Minimal user model for the bridge tests.
 *
 * This is a plain Authenticatable without the laragear TwoFactorAuthentication trait
 * so that tests exercise the bridge through the {@see FakeTwoFactorValidator}
 * seam, running fully offline without laragear installed.
 */
class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
