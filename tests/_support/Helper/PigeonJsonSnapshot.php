<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests;

use tad\Codeception\SnapshotAssertions\JsonSnapshot;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use tad\Codeception\SnapshotAssertions\Configuration;
use ReflectionMethod;
use Codeception\Snapshot;
use StellarWP\Pigeon\Tests\Traits\With_Log_Snapshot;

class PigeonJsonSnapshot extends JsonSnapshot {

	protected static string $traitClass = With_Log_Snapshot::class;

	protected function getFileName()
    {
        if (empty($this->fileName)) {
            $traitMethods = static::getTraitMethods();
            $backtrace = array_values(array_filter(
                debug_backtrace(
                    DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT,
                    6
                ),
                static function (array $backtraceEntry) use ($traitMethods) {
                    return isset($backtraceEntry['class']) && !in_array(
                        $backtraceEntry['class'],
                        [Snapshot::class, static::class, self::class, static::$traitClass],
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

    /**
     * Returns an array of the trait method names.
     *
     * @return array<string> An array of the trait method names.
     *
     * @throws ReflectionException If a reflection cannot be done on a trait method.
     */
    protected static function getTraitMethods()
    {
        if (!empty(static::$traitMethods)) {
            return static::$traitMethods;
        }

        $reflection = new ReflectionClass(static::$traitClass);
        static::$traitMethods = array_map(function (ReflectionMethod $method) {
            return $method->name;
        }, $reflection->getMethods());

        return static::$traitMethods;
    }
}
