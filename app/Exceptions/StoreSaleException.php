<?php

namespace App\Exceptions;

/**
 * Thrown when a POS checkout can't complete as requested (empty cart,
 * insufficient stock, amount paid short of the total). Message is
 * written to be shown directly to the cashier.
 */
class StoreSaleException extends \RuntimeException {}
