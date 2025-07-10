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
use StellarWP\Pigeon\Tables\AS_Actions;
use StellarWP\Pigeon\Tables\Tasks;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Log;
use ActionScheduler_SimpleSchedule;

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
	 * The default arguments for the tasks query.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected const DEFAULT_ARGS = [
		'perPage' => 10,
		'page'    => 1,
		'order'   => 'desc',
		'orderby' => 'id',
		'search'  => '',
		'filters' => '[]',
	];

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
		add_action( 'wp_ajax_shepherd_get_tasks', [ $this, 'ajax_get_tasks' ] );

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
			'orderby' => self::DEFAULT_ARGS['orderby'],
			'order'   => self::DEFAULT_ARGS['order'],
		];

		[
			'tasks'      => $tasks,
			'totalItems' => $total_items,
			'totalPages' => $total_pages,
		] = $this->get_tasks( $args, self::DEFAULT_ARGS['perPage'], self::DEFAULT_ARGS['page'] );

		return [
			'tasks'       => $tasks,
			'totalItems'  => $total_items,
			'totalPages'  => $total_pages,
			'defaultArgs' => self::DEFAULT_ARGS,
			'nonce'       => wp_create_nonce( 'shepherd_get_tasks' ),
		];
	}

	/**
	 * Gets the tasks via AJAX.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_get_tasks(): void {
		check_ajax_referer( 'shepherd_get_tasks', 'nonce' );

		if ( ! current_user_can( Config::get_admin_page_capability() ) ) {
			wp_send_json_error( __( 'You are not authorized to access this page.', 'stellarwp-pigeon' ) );
			/** @phpstan-ignore deadCode.unreachable */
			return;
		}

		$data = wp_unslash( $_POST );

		$per_page = absint( $data['perPage'] ?? self::DEFAULT_ARGS['perPage'] );
		$page     = absint( $data['page'] ?? self::DEFAULT_ARGS['page'] );

		$args = [
			'orderby' => $data['orderby'] ?? self::DEFAULT_ARGS['orderby'],
			'order'   => $data['order'] ?? self::DEFAULT_ARGS['order'],
		];

		$search = $data['search'] ?? self::DEFAULT_ARGS['search'];
		if ( $search ) {
			$args['term'] = $search;
		}

		$filters = json_decode( $data['filters'] ?? self::DEFAULT_ARGS['filters'], true );

		foreach ( $filters as $filter ) {
			$args[] = [
				'column'   => 'task_type' === $filter['field'] ? 'class_hash' : $filter['field'],
				'value'    => 'task_type' === $filter['field'] ? md5( $filter['value'] ) : $filter['value'],
				'operator' => 'isNot' === $filter['operator'] ? '!=' : '=',
			];
		}

		wp_send_json_success( $this->get_tasks( $args, $per_page, $page ), 200 );
	}

	/**
	 * Gets the tasks.
	 *
	 * @since TBD
	 *
	 * @param array $args The arguments.
	 * @param int   $per_page The number of items per page.
	 * @param int   $page The page number.
	 *
	 * @return array{'tasks': array, 'totalItems': int, 'totalPages': int}
	 */
	protected function get_tasks( array $args, int $per_page = 10, int $page = 1 ): array {
		$task_array = Tasks::paginate( $args, $per_page, $page, AS_Actions::class, 'action_id=action_id', [ 'status' ] );

		if ( empty( $task_array ) ) {
			return [
				'tasks'      => [],
				'totalItems' => 0,
				'totalPages' => 0,
			];
		}

		$tasks = [];
		foreach ( $task_array as $task ) {
			$action_id = $task->action_id;

			$action = Action_Scheduler_Methods::get_action_by_id( (int) $action_id );

			/** @var ActionScheduler_SimpleSchedule $schedule */
			$schedule = $action->get_schedule();

			$logs = Config::get_container()->get( Logger::class )->retrieve_logs( (int) $task->id );

			$tasks[] = [
				'id'           => (int) $task->id,
				'action_id'    => (int) $task->action_id,
				'data'         => json_decode( $task->data, true ),
				'current_try'  => (int) $task->current_try,
				'status'       => $task->status,
				'scheduled_at' => $schedule->get_date()->setTimezone( wp_timezone() ),
				'logs'         => array_map( fn( Log $log ) => $log->to_array(), $logs ),
			];
		}

		$total_items = Tasks::get_total_items( $args, AS_Actions::class, 'action_id=action_id' );

		return [
			'tasks'      => $tasks,
			'totalItems' => $total_items,
			'totalPages' => (int) ceil( $total_items / $per_page ),
		];
	}
}
