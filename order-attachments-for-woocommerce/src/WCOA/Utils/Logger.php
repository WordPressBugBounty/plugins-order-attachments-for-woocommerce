<?php

namespace DirectSoftware\WCOA\Utils;

use DirectSoftware\WCOA\Common\Options;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * @author d.gubala
 */
class Logger extends \Monolog\Logger
{
	private static ?Logger $instance = null;

	private bool $logging_enabled = false;

	public static function getInstance(): Logger
	{
		if (self::$instance === null)
		{
			self::$instance = new Logger();
		}

		return self::$instance;
	}

	private function __construct()
	{
		parent::__construct( 'wcoa_logger');
		$this->initialize();
	}

	private function initialize(): void
	{
		$this->logging_enabled = Options::get(Options::OPTION_ENABLE_LOGGING);

		$directory = __DIR__ . '/../../../var/log/app.log';

		$formatter = new LineFormatter("%datetime% %level_name%\t %message% %context% %extra%\n");
		$formatter->setDateFormat("d/m/Y\tH:i:s.u");

		$handler = new StreamHandler($directory);
		$handler->setFormatter($formatter);

		$this->pushHandler($handler);
	}

	public function log($level, mixed $message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::log($level, $message, $context);
		}
	}

	public function debug($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::debug($message, $context);
		}
	}

	public function info($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::info($message, $context);
		}
	}

	public function notice($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::notice($message, $context);
		}
	}

	public function warning($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::warning($message, $context);
		}
	}

	public function error($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::error($message, $context);
		}
	}

	public function critical($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::critical($message, $context);
		}
	}

	public function alert($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::alert($message, $context);
		}
	}

	public function emergency($message, array $context = []): void
	{
		if ($this->logging_enabled)
		{
			parent::emergency($message, $context);
		}
	}
}