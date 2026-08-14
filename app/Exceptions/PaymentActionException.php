<?php

namespace App\Exceptions;

/**
 * Thrown when a payment status transition doesn't make sense (e.g.
 * marking an already-paid payment as failed, or refunding something
 * that was never paid). Message is written to be shown directly to
 * staff/admin (Requirements.md §49).
 */
class PaymentActionException extends \RuntimeException {}
