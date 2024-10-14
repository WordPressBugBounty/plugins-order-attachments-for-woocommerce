<?php

namespace DirectSoftware\WCOA\Utils;

use JsonSerializable;

/**
 * @since 2.5.0
 * @author d.gubala
 */
class JsonResponse implements JsonSerializable
{
	private const STATUS_SUCCESS = "success";
	private const STATUS_ERROR = "error";

	private string $message;
	private bool $isSuccess;
	private ?JsonSerializable $data;

	public function __construct(
		string $message,
		bool $isSuccess = true,
		?JsonSerializable $data = null
	)
	{
		$this->message = $message;
		$this->isSuccess = $isSuccess;
		$this->data = $data;
	}

	public function setMessage(string $message): void
	{
		$this->message = $message;
	}

	public function setSuccess(bool $isSuccess): void
	{
		$this->isSuccess = $isSuccess;
	}

	/**
	 * @return array<string,int|string|array>
	 */
	public function jsonSerialize() : array
	{
		return [
			'message' => $this->message,
			'code' => $this->isSuccess ? 0 : -1,
			'status' => $this->isSuccess ? self::STATUS_SUCCESS : self::STATUS_ERROR,
			'data' => $this->data,
		];
	}

	/**
	 * @return array<string,int|string>
	 */
	public function toArray(): array
	{
		return [
			'code' => $this->isSuccess ? 0 : -1,
			'status' => $this->isSuccess ? self::STATUS_SUCCESS : self::STATUS_ERROR,
		];
	}

	public function __toString(): string
	{
		return json_encode($this);
	}

	public static function pure(bool $isSuccess, string $message = ''): string
	{
		return sprintf( "{\"status\": \"%s\", \"code\": %d, \"message\": \"%s\"}",
			$isSuccess ? self::STATUS_SUCCESS : self::STATUS_ERROR, $isSuccess ? 0 : -1, $message
		);
	}
}