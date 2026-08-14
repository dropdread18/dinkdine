<?php

namespace App\Exceptions;

/**
 * Thrown whenever a requested slot can't actually be booked - already
 * taken, outside the booking window, or the court/slot no longer exists.
 * The message is written to be shown directly to the customer
 * (Requirements.md §49: clear, non-technical error messages).
 */
class BookingUnavailableException extends \RuntimeException {}
