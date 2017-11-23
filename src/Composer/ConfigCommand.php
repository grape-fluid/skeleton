<?php

namespace Grapesc\GrapeFluid\Skeleton\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Nette\Neon\Neon;


/**
 * @author Mira Jakes <jakes@grapesc.cz>
 */
class ConfigCommand
{

	/** @var array */
	private static $dist;

	/** @var array */
	private static $local;

	/** @var IOInterface */
	private static $io;

	/** @var bool */
	private static $changed = false;

	/** @var bool */
	private static $creating = false;


	/**
	 * @param Event $event
	 * @return void
	 */
	public static function generateConfig(Event $event)
	{
		$io        = self::$io = $event->getIO();
		$config    = $event->getComposer()->getConfig();
		$vendorDir = $config->get('vendor-dir');
		$baseDir   = realpath($vendorDir . DIRECTORY_SEPARATOR . "..");
		$configDir = $baseDir . DIRECTORY_SEPARATOR . "config";

		if (!$io->isInteractive()) {
			$io->write('<comment>Install is not interactive, please check your config.local.neon self</comment>');
			return;
		}

		$localConfigNeonPath     = $configDir . DIRECTORY_SEPARATOR . "config.local.neon";
		$distLocalConfigNeonPath = $localConfigNeonPath . ".dist";

		$local = self::$local = Neon::decode(file_get_contents($localConfigNeonPath));
		$dist  = self::$dist = Neon::decode(file_get_contents($distLocalConfigNeonPath));

		if ($local == $dist) {
			self::$creating = true;
		}

		$action = self::$creating ? 'Creating' : 'Updating';
		$io->write(sprintf('<info>%s the "%s" file</info>', $action, $localConfigNeonPath));

		$io->write("");

		self::askForParam($dist);

		if (self::$changed) {
			$io->write("");
			$io->write(sprintf('<info>Writing changes to "%s" file</info>', $localConfigNeonPath));
			file_put_contents($localConfigNeonPath, Neon::encode(self::$local, Neon::BLOCK));
		} else {
			$io->write(sprintf('<info>No change in "%s" file</info>', $localConfigNeonPath));
		}
	}


	/**
	 * @param string $config
	 * @param string|null $path
	 * @return void
	 */
	private static function askForParam($config, $path = null)
	{
		if (is_array($config)) {
			foreach ($config AS $key => $values) {
				if (is_array($values)) {
					self::askForParam($values, $path ? "$path.$key" : $key);
				} else {
					$name = "$path.$key";
					if (self::$creating || is_null(self::getValueInLocal($name))) {
						$value = self::$io->ask(sprintf('<question>%s</question> [<comment>%s</comment>]: ', $name, $values), $values);

						if ($value) {
							self::$changed = true;
							self::writeValueToLocal($name, $value);
						}
					}
				}
			}
		}
	}


	/**
	 * @param string $name
	 * @return bool|float|int|null|string
	 */
	private static function getValueInLocal($name)
	{
		$sections = explode(".", $name);
		$temp     = &self::$local;

		foreach ($sections AS $section) {
			if (is_array($temp) && array_key_exists($section, $temp)) {
				$temp = &$temp[$section];
			} elseif (!is_array($temp)) {
				return null;
			}
		}

		return is_scalar($temp) ? $temp : null;
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	private static function writeValueToLocal($name, $value)
	{
		$sections = explode(".", $name);
		$temp     = &self::$local;

		foreach ($sections AS $section) {
			if (is_array($temp) AND !array_key_exists($section, $temp)) {
				$temp[$section] = [];
			}

			if (is_array($temp) && array_key_exists($section, $temp)) {
				$temp = &$temp[$section];
			}
		}

		$temp = $value;
	}

}
