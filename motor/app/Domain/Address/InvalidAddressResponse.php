<?php

namespace App\Domain\Address;

use RuntimeException;

/**
 * The model answered, but the reply is not valid JSON or does not match the
 * published schema. Such a reply must never be saved on the order.
 */
class InvalidAddressResponse extends RuntimeException {}
