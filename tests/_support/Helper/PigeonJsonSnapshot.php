<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests;

use tad\Codeception\SnapshotAssertions\JsonSnapshot;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Codeception\Snapshot;
use tad\Codeception\SnapshotAssertions\Configuration;

class PigeonJsonSnapshot extends JsonSnapshot {

	/**
     * A list of method names provided by the SnapshotAssertions trait.
     *
     * @var array<string>
     */
    protected static $traitMethods = [
		'assertMatchesStringSnapshot',
		'assertMatchesHtmlSnapshot',
		'assertMatchesJsonSnapshot',
		'assertMatchesCodeSnapshot',
		'assertMatchesDirectorySnapshot',
		'assertMatchesLogSnapshot',
	];

	/**
     * Returns the path to the snapshot file that will be, or has been generated, including the file extension.
     *
     * @return string The snapshot file name, including the file extension.
     * @throws ReflectionException If the class that called the class cannot be reflected.
     */
    protected function getFileName()
    {
        if (empty($this->fileName)) {
            $traitMethods = static::getTraitMethods();
            $backtrace = array_values(array_filter(
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS
                    | DEBUG_BACKTRACE_PROVIDE_OBJECT, 10),
                static function (array $backtraceEntry) use ($traitMethods) {
                    return isset($backtraceEntry['class']) && !in_array(
                        $backtraceEntry['class'],
                        [Snapshot::class, static::class, self::class, SnapshotAssertions::class],
                        true
                    ) && !in_array($backtraceEntry['function'], $traitMethods, true);
                }
            ));
            $class = isset($backtrace[0]['class']) ? $backtrace[0]['class'] : '';

            if (empty($class)) {
                throw new RuntimeException('Cannot get ithe class name.');
            }

            $classFrags = explode('\\', $class);
            $classBasename = array_pop($classFrags);
            $classFile = (new ReflectionClass($class))->getFileName();

            if ($classFile === false) {
                throw new RuntimeException('Cannot get the filename of the class ' . $class);
            }

            $classDir = dirname($classFile);
            $function = $backtrace[0]['function'];
            $dataSetFrag = '';
            if (isset($backtrace[0]['object']) && $backtrace[0]['object'] instanceof TestCase) {
                /** @var TestCase $testCase */
                $testCase = $backtrace[0]['object'];
                $dataName = $this->getDataName($testCase);
                if ($dataName !== '') {
                    $dataSetFrag = '__'.$dataName;
                }
            }
            $fileName = sprintf(
                '%s__%s%s%s__%d.%s',
                $classBasename,
                Configuration::getVersion(),
                $function,
                $dataSetFrag,
                $this->getCounterFor($class, $function, $dataSetFrag),
                $this->fileExtension()
            );
            $this->fileName = $classDir.'/__snapshots__/'.$fileName;
        }

        return $this->fileName;
    }
}