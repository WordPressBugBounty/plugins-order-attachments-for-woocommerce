<?php

namespace DirectSoftware\WCOA\Attachments\Data;

/**
 * @since 2.5.0
 * @author d.gubala
 */
class BaseFile
{
	private array $original;
	private ?string $name;
	private ?string $basename;
	private string $temporaryName;

	private function __construct(array $original)
	{
		$this->original = $original;
		$this->name = $original['name'] ?? null;
		$this->basename = $this->name !== null ? basename($original['name']) : null;
		$this->temporaryName = $original['tmp_name'] ?? null;
	}

	public static function getInstance(array $src): BaseFile
	{
		return new BaseFile($src);
	}

	public function getOriginal(): array
	{
		return $this->original;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBasename(): string
	{
		return $this->basename;
	}

	public function getTemporaryName(): string
	{
		return $this->temporaryName;
	}

	public function isEmpty(): bool
	{
		return empty($this->name);
	}

	public function setPrefix(string $prefix): void
	{
		$this->name = sprintf("%s%s", $prefix, $this->name);
	}
}