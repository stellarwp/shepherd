<?php
/**
 * Admin provider for Pigeon.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Admin
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Admin;

use StellarWP\Pigeon\Abstracts\Provider_Abstract;
use StellarWP\Pigeon\Action_Scheduler_Methods;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Tables\Tasks;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Log;
use ActionScheduler_Action;
use ActionScheduler_CanceledAction;
use ActionScheduler_FinishedAction;
use ActionScheduler_NullAction;

/**
 * Admin provider.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Admin
 */
class Provider extends Provider_Abstract {
	/**
	 * Whether the provider has been registered.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private static bool $has_registered = false;

	/**
	 * Registers the admin functionality.
	 *
	 * @since TBD
	 */
	public function register(): void {
		if ( self::is_registered() ) {
			return;
		}

		if ( ! Config::get_render_admin_ui() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

		self::$has_registered = true;
	}

	/**
	 * Registers the admin menu.
	 *
	 * @since TBD
	 */
	public function register_admin_menu(): void {
		$page_hook = add_management_page(
			Config::get_admin_page_title(),
			Config::get_admin_menu_title(),
			Config::get_admin_page_capability(),
			'pigeon-' . Config::get_hook_prefix(),
			[ $this, 'render_admin_page' ]
		);

		add_action( "admin_print_styles-{$page_hook}", [ $this, 'enqueue_admin_page_assets' ] );
	}

	/**
	 * Renders the admin page.
	 *
	 * @since TBD
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1>
				<?php echo esc_html( Config::get_admin_page_in_page_title() ); ?>
			</h1>
			<div id="shepherd-app"></div>
		</div>
		<?php
	}

	/**
	 * Checks if Pigeon is registered.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function is_registered(): bool {
		return self::$has_registered;
	}

	/**
	 * Enqueues the admin page assets.
	 *
	 * @since TBD
	 */
	public function enqueue_admin_page_assets(): void {
		$asset_data = require Config::get_package_path( 'build/main.asset.php' );
		wp_enqueue_script( 'shepherd-admin-script', Config::get_package_url( 'build/main.js' ), $asset_data['dependencies'], $asset_data['version'], true );
		wp_localize_script( 'shepherd-admin-script', 'shepherdData', $this->get_localized_data() );

		wp_enqueue_style( 'shepherd-admin-style', Config::get_package_url( 'build/style-main.css' ), [], $asset_data['version'] );
	}

	/**
	 * Gets the localized data.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	private function get_localized_data(): array {
		$args = [
			'orderby' => 'id',
			'order'   => 'DESC',
		];

		$per_page = 10;

		$page = 1;

		$task_array = Tasks::paginate( $args, $per_page, $page );

		$tasks = [];
		foreach ( $task_array as $task ) {
			$action_id = $task->action_id;

			$action = Action_Scheduler_Methods::get_action_by_id( (int) $action_id );

			$schedule = $action->get_schedule();

			$logs = Config::get_container()->get( Logger::class )->retrieve_logs( (int) $task->id );

			$last_log = end( $logs );

			$tasks[] = [
				'id'           => (int) $task->id,
				'action_id'    => $action instanceof ActionScheduler_NullAction ? 0 : (int) $task->action_id,
				'data'         => json_decode( $task->data, true ),
				'current_try'  => (int) $task->current_try,
				'status'       => $this->get_task_status( $action, $last_log ? $last_log : null ),
				'scheduled_at' => $action instanceof ActionScheduler_NullAction ? null : $schedule->get_date()->setTimezone( wp_timezone() ),
				'logs'         => array_map( fn( Log $log ) => $log->to_array(), $logs ),
			];
		}

		$total_items = Tasks::get_total_items( $args );

		return [
			'tasks'      => $tasks,
			'totalItems' => $total_items,
			'totalPages' => ceil( $total_items / $per_page ),
		];
	}

	/**
	 * Gets the task status.
	 *
	 * @since TBD
	 *
	 * @param ActionScheduler_Action $action The action.
	 * @param ?Log                   $log    The log.
	 *
	 * @return array
	 */
	protected function get_task_status( ActionScheduler_Action $action, ?Log $log ): array {
		if ( $action instanceof ActionScheduler_CanceledAction ) {
			return [
				'slug'  => 'cancelled',
				'label' => __( 'Cancelled', 'stellarwp-pigeon' ),
			];
		}

		if ( $action instanceof ActionScheduler_FinishedAction ) {
			return [
				'slug'  => 'success',
				'label' => __( 'Success', 'stellarwp-pigeon' ),
			];
		}

		if ( null === $log ) {
			return [
				'slug'  => 'pending',
				'label' => __( 'Pending', 'stellarwp-pigeon' ),
			];
		}

		if ( in_array( $log->get_type(), [ Log::TYPE_STARTED, Log::TYPE_RETRYING ] ) ) {
			return [
				'slug'  => 'running',
				'label' => __( 'Running', 'stellarwp-pigeon' ),
			];
		}

		if ( Log::TYPE_FAILED === $log->get_type() ) {
			return [
				'slug'  => 'failed',
				'label' => __( 'Failed', 'stellarwp-pigeon' ),
			];
		}

		return [
			'slug'  => 'pending',
			'label' => __( 'Pending', 'stellarwp-pigeon' ),
		];
	}
}
