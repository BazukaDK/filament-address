<?php

namespace Bazuka\FilamentAddress\Tests;

use Bazuka\FilamentAddress\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Model;

/**
 * A model with addresses, used by the tests.
 */
class Place extends Model
{
    use HasAddresses;
}
