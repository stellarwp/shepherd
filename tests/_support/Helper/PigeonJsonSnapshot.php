<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests;

use tad\Codeception\SnapshotAssertions\JsonSnapshot;
use StellarWP\Pigeon\Tests\Traits\With_Log_Snapshot;


class PigeonJsonSnapshot extends JsonSnapshot {

	protected static int $backtraceLimit = 6;

	protected static string $traitClass = With_Log_Snapshot::class;
}
