<?php
declare(strict_types=1);

namespace LotGD\Core;

use Composer\Composer;
use Monolog\Logger;

use LotGD\Core\Exceptions\LibraryDoesNotExistException;

/**
 * Helps perform tasks with the composer configuration.
 */
class ComposerManager
{
    private $logger;
    private $composer;

    /**
     * Creates a new ComposerManager.
     * @param Monlog\Logger $logger A logger instance for messaging.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns a Composer instance to perform underlying operations on. Be careful.
     * @return Composer An instance of Composer.
     */
    public function getComposer(): Composer
    {
        if ($this->composer === null) {
            $this->composer = \Composer\Factory::create(new \Composer\IO\NullIO());
        }
        return $this->composer;
    }

    /**
     * Return the Composer package for the corresponding library, in vendor/module format.
     * @return PackageInterface Package corresponding to this library.
     * @throws LibraryDoesNotExistException
     */
    public function getPackageForLibrary(string $library): PackageInterface
    {
        // TODO: should probably do something better than O(n) here.
        $packages = $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($packages as $p) {
            if ($p->getName() === $library) {
               return $p;
            }
        }
        throw new LibraryDoesNotExistException();
    }

    /**
     * Return all the packages installed in the current setup.
     * @return array<Composer\PackageInterface>
     */
    public function getPackages(): array
    {
        return $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
    }

    /**
     * Return a list of the configured packages which are LotGD modules (type = 'lotgd-module').
     * @return array Array of \Composer\PackageInterface.
     */
    public function getModulePackages(): array
    {
        $result = array();
        $packages = $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($packages as $p) {
            if ($p->getType() === 'lotgd-module') {
                array_push($result, $p);
            }
        }

        return $result;
    }

    /**
     * Find the filesystem path where the code for a namespace can be found.
     * @param string $namespace The namespace to translate.
     * @return string|null Path representing $namespace or null if $namespace
     * cannot be found or if the path does not exist.
     */
    public function translateNamespaceToPath(string $namespace)
    {
        // Find the directory for this namespace by using the autoloader's
        // classmap.
        $autoloader = require(ComposerManager::findAutoloader());
        $prefixes = $autoloader->getPrefixesPsr4();

        // Standardize the namespace to remove any leading \ and add a trailing \
        $n = $namespace;
        if ('\\' == $n[0]) {
            $n = substr($n, 1);
        }
        if (strlen($n) > 0 && '\\' != $n[strlen($n) - 1]) {
            $n .= '\\';
        }

        $split = explode('\\', $n);
        $suffix = array_splice($split, -1, 1); // starts with ['']
        $path = null;
        while (!empty($split)) {
            $key = join('\\', $split) . '\\';
            $dir = join(DIRECTORY_SEPARATOR, $suffix);
            // Prefix to directory mappings are arrays in Composer's
            // ClassLoader object. Not sure why. This might break in
            // some unforseen case.
            if (isset($prefixes[$key]) && is_dir($prefixes[$key][0] .  DIRECTORY_SEPARATOR . $dir)) {
                $path = $prefixes[$key][0] .  DIRECTORY_SEPARATOR . $dir;
                break;
            }
            $suffix = array_merge($suffix, array_splice($split, -1, 1));
        }

        if ($path == null) {
            return null;
        }
        $path = realpath($path);
        if ($path == false) {
            return null;
        }
        return $path;
    }

    /**
     * Returns a path (could be relative) to the proper autoload.php file in
     * the current setup.
     */
    public static function findAutoloader(): string
    {
        // Dance to find the autoloader.
        // TOOD: change this to open up the Composer config and use $c['config']['vendor-dir'] instead of "vendor"
        $order = [
            getcwd() . '/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../autoload.php',
        ];
        foreach ($order as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        throw new Exception("Cannot find autoload.php in any of its usual locations.");
    }
}
