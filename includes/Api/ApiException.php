<?php
/**
 * Exception thrown by the API client.
 *
 * Carries an ErrorCode so callers can match on failure mode without parsing
 * the message string. Optional `context` array holds the raw HTTP response
 * for debugging (never shown to end users — only logged).
 *
 * @package GEO_Forge
 */

namespace GEO_Forge\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ApiException extends \RuntimeException {

	private ErrorCode $code_enum;
	private array     $context;

	public function __construct( ErrorCode $code, string $message = '', array $context = array(), ?\Throwable $previous = null ) {
		$this->code_enum = $code;
		$this->context   = $context;

		parent::__construct( $message ?: $code->label(), 0, $previous );
	}

	public function getCodeEnum(): ErrorCode {
		return $this->code_enum;
	}

	/**
	 * Debug context (raw response, etc). Never shown to end users.
	 */
	public function getContext(): array {
		return $this->context;
	}
}
