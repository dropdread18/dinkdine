<?php

namespace App\Exceptions;

/**
 * Thrown when a stock movement would leave a product's stock_quantity
 * negative, which is never a meaningful state in this app. Message is
 * written to be shown directly to the admin.
 */
class InventoryAdjustmentException extends \RuntimeException {}
