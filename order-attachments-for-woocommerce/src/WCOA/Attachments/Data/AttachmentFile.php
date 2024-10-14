<?php

namespace DirectSoftware\WCOA\Attachments\Data;

use JsonSerializable;

/**
 * @since 2.5.0
 * @author d.gubala
 */
class AttachmentFile implements JsonSerializable
{
	private int $id;
	private string $title;
	private string $url;

	public function __construct(
		int $id,
		string $title,
		string $url
	)
	{
		$this->id = $id;
		$this->title = $title;
		$this->url = $url;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getTitle(): int
	{
		return $this->title;
	}

	public function getUrl(): int
	{
		return $this->url;
	}

	/**
	 * @return array<string,int|string>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'url' => $this->url,
		];
	}

	/**
	 * @return array<string,int|string>
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}