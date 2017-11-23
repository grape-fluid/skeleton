<?php

namespace Grapesc\GrapeFluid\Skeleton\Composer;

use Composer\Script\Event;
use Nette\Utils\Finder;


/**
 * @author Mira Jakes <jakes@grapesc.cz>
 */
class InstallCommand
{

	const CHMOD = 0755;


	/**
	 * @param Event $event
	 * @return void
	 */
	public static function createSkeleton(Event $event)
	{
		$skeletonDirectory    = self::getSkeletonDirectory();
		$applicationDirectory = self::getApplicationDirectory();
		$copiedDistFiles      = [];

		foreach (Finder::findFiles('*.dist')->from($skeletonDirectory) AS $file) {
			$relativeDirectoryFromApplicationDirectory = trim(str_replace($skeletonDirectory, '', $file->getPath()), DIRECTORY_SEPARATOR);
			$event->getIO()->write("Copying $relativeDirectoryFromApplicationDirectory" . DIRECTORY_SEPARATOR . "{$file->getBasename()} to application directory ... ", false);

			if (self::copyAndCreateDestination($file->getPathname(), $applicationDirectory . DIRECTORY_SEPARATOR . $relativeDirectoryFromApplicationDirectory, $file->getBasename())) {
				$copiedDistFiles[$relativeDirectoryFromApplicationDirectory . DIRECTORY_SEPARATOR . $file->getBasename()] = $applicationDirectory . DIRECTORY_SEPARATOR . $relativeDirectoryFromApplicationDirectory . DIRECTORY_SEPARATOR . $file->getBasename();
				$event->getIO()->write("OK");
			} else {
				$event->getIO()->writeError("ERROR");
			}
		}

		foreach ($copiedDistFiles AS $name => $distFile) {
			$nonDistFile = substr($distFile, 0, -5);
			if (!is_file($nonDistFile)) {
				$event->getIO()->write("Creating non dist file from $name ...", false);
				if (@copy($distFile, $nonDistFile)) {
					$event->getIO()->write("OK");
				} else {
					$event->getIO()->writeError("ERROR");
				}
			}
		}

	}


	/**
	 * @param string $file
	 * @param string $destination
	 * @param string $filename
	 * @return bool
	 */
	private static function copyAndCreateDestination($file, $destination, $filename)
	{
		if (!is_dir($destination)) {
			mkdir($destination, self::CHMOD, true);
		}

		return (bool) @copy($file, $destination . DIRECTORY_SEPARATOR . $filename);
	}


	/**
	 * @return bool|string
	 */
	private static function getSkeletonDirectory()
	{
		return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'skeleton');
	}


	/**
	 * @return bool|string
	 */
	private static function getApplicationDirectory()
	{
		return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
	}

}
