<?php

namespace DirectSoftware\WCOA\Utils;

use DirectSoftware\WCOA\Exception\InvalidFileTypeException;

/**
 * @since 2.5.0
 * @author d.gubala
 */
class FileUtils
{
	/**
	 * @param array $fileType
	 * @return void
	 * @throws InvalidFileTypeException
	 */
	public static function checkType(array $fileType): void
	{
		if (empty($fileType['type']))
		{
			throw new InvalidFileTypeException();
		}
	}
}