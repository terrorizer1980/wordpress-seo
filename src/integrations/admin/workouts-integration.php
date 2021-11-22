<?php

namespace Yoast\WP\SEO\Integrations\Admin;

use WPSEO_Addon_Manager;
use WPSEO_Admin_Asset_Manager;
use Yoast\WP\SEO\Conditionals\Admin_Conditional;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Helpers\Product_Helper;
use Yoast\WP\SEO\Integrations\Integration_Interface;
use Yoast\WP\SEO\Presenters\Admin\Notice_Presenter;

/**
 * WorkoutsIntegration class
 */
class Workouts_Integration implements Integration_Interface {

	/**
	 * The admin asset manager.
	 *
	 * @var WPSEO_Admin_Asset_Manager
	 */
	private $admin_asset_manager;

	/**
	 * The addon manager.
	 *
	 * @var WPSEO_Addon_Manager
	 */
	private $addon_manager;

	/**
	 * The options helper.
	 *
	 * @var Options_Helper
	 */
	private $options_helper;

	/**
	 * The product helper.
	 *
	 * @var Product_Helper
	 */
	private $product_helper;

	/**
	 * {@inheritDoc}
	 */
	public static function get_conditionals() {
		return [ Admin_Conditional::class ];
	}

	/**
	 * Workouts_Integration constructor.
	 *
	 * @param WPSEO_Addon_Manager       $addon_manager       The addon manager.
	 * @param WPSEO_Admin_Asset_Manager $admin_asset_manager The admin asset manager.
	 * @param Options_Helper            $options_helper      The options helper.
	 * @param Product_Helper            $product_helper      The product helper.
	 */
	public function __construct(
		WPSEO_Addon_Manager $addon_manager,
		WPSEO_Admin_Asset_Manager $admin_asset_manager,
		Options_Helper $options_helper,
		Product_Helper $product_helper
	) {
		$this->addon_manager       = $addon_manager;
		$this->admin_asset_manager = $admin_asset_manager;
		$this->options_helper      = $options_helper;
		$this->product_helper      = $product_helper;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks() {
		\add_filter( 'wpseo_submenu_pages', [ $this, 'add_submenu_page' ], 9 );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ], 11 );
		\add_action( 'admin_notices', [ $this, 'configuration_workout_notice' ] );
	}

	/**
	 * Adds the workouts submenu page.
	 *
	 * @param array $submenu_pages The Yoast SEO submenu pages.
	 *
	 * @return array the filtered submenu pages.
	 */
	public function add_submenu_page( $submenu_pages ) {
		// If Premium has an outdated version, which also adds a 'workouts' submenu, don't show the Premium submenu.
		if ( $this->should_update_premium() ) {
			$submenu_pages = array_filter(
				$submenu_pages,
				function ( $item ) {
					return $item[4] !== 'wpseo_workouts';
				}
			);
		}

		// This inserts the workouts menu page at the correct place in the array without overriding that position.
		$submenu_pages[] = [
			'wpseo_dashboard',
			'',
			\__( 'Workouts', 'wordpress-seo' ),
			'edit_others_posts',
			'wpseo_workouts',
			[ $this, 'render_target' ],
		];

		return $submenu_pages;
	}

	/**
	 * Enqueue the workouts app.
	 */
	public function enqueue_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Date is not processed or saved.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wpseo_workouts' ) {
			return;
		}

		if ( $this->should_update_premium() ) {
			\wp_dequeue_script( 'yoast-seo-premium-workouts' );
		}

		$this->admin_asset_manager->enqueue_style( 'workouts' );

		$workouts_option = $this->get_workouts_option();

		$this->admin_asset_manager->enqueue_script( 'workouts' );
		$this->admin_asset_manager->localize_script(
			'workouts',
			'wpseoWorkoutsData',
			[
				'workouts'                  => $workouts_option,
				'homeUrl'                   => \home_url(),
				'pluginUrl'                 => \esc_url( \plugins_url( '', WPSEO_FILE ) ),
				'toolsPageUrl'              => \esc_url( \admin_url( 'admin.php?page=wpseo_tools' ) ),
				'usersPageUrl'              => \esc_url( \admin_url( 'users.php' ) ),
				'isPremium'                 => $this->product_helper->is_premium(),
				'isPremiumUnactivated'      => $this->has_premium_subscription_unactivated(),
				'canDoConfigurationWorkout' => $this->user_can_do_configuration_workout(),
			]
		);
	}

	/**
	 * Renders the target for the React to mount to.
	 */
	public function render_target() {
		if ( $this->should_update_premium() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in get_update_premium_notice.
			echo $this->get_update_premium_notice();
		}

		echo '<div id="wpseo-workouts-container-free" class="yoast"></div>';
	}

	/**
	 * Determines whether and where the "First-time SEO Configuration workout" admin notice should be displayed.
	 *
	 * @return bool Whether the "First-time SEO Configuration workout" admin notice should be displayed.
	 */
	public function should_display_configuration_workout_notice() {
		if ( ! $this->options_helper->get( 'dismiss_configuration_workout_notice', false ) === false ) {
			return false;
		}

		if ( ! $this->user_can_do_configuration_workout() ) {
			return false;
		}

		$workouts_option = $this->options_helper->get( 'workouts_data', [] );
		$finished_steps  = $workouts_option['configuration']['finishedSteps'];

		return count( $finished_steps ) < 5 &&
			$this->on_wpseo_admin_page_or_dashboard();
	}

	/**
	 * Displays an admin notice when the configuration workout has not been finished yet.
	 *
	 * @return void
	 */
	public function configuration_workout_notice() {
		if ( ! $this->should_display_configuration_workout_notice() ) {
			return;
		}

		$this->admin_asset_manager->enqueue_style( 'monorepo' );

		$notice = new Notice_Presenter(
			\__( 'First-time SEO configuration', 'wordpress-seo' ),
			\sprintf(
			/* translators: 1: Link start tag to the configuration workout, 2: Yoast SEO, 3: Link closing tag. */
				__( 'Get started quickly with the %1$s%2$s Configuration workout%3$s and configure Yoast SEO with the optimal SEO settings for your site!', 'wordpress-seo' ),
				'<a href="' . \esc_url( \self_admin_url( 'admin.php?page=wpseo_workouts' ) ) . '">',
				'Yoast SEO',
				'</a>'
			),
			'mirrored_fit_bubble_woman_1_optim.svg',
			null,
			true,
			'yoast-configuration-workout-notice'
		);

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output from present() is considered safe.
		echo $notice->present();

		// Enable permanently dismissing the notice.
		echo "<script>
			function dismiss_configuration_workout_notice(){
				var data = {
				'action': 'dismiss_configuration_workout_notice',
				};

				jQuery.post( ajaxurl, data, function( response ) {
					jQuery( '#yoast-configuration-workout-notice' ).hide();
				});
			}

			jQuery( document ).ready( function() {
				jQuery( 'body' ).on( 'click', '.notice-dismiss', function() {
					dismiss_configuration_workout_notice();
				} );
			} );
			</script>";
	}

	/**
	 * Gets the workouts option.
	 *
	 * @return mixed|null Returns workouts option if found, null if not.
	 */
	private function get_workouts_option() {
		$workouts_option = $this->options_helper->get( 'workouts_data' );

		// This filter is documented in src/routes/workouts-route.php.
		return \apply_filters( 'Yoast\WP\SEO\workouts_options', $workouts_option );
	}

	/**
	 * Returns the notification to show when Premium needs to be updated.
	 *
	 * @return string The notification to update Premium.
	 */
	private function get_update_premium_notice() {
		if ( $this->has_premium_subscription_expired() ) {
			$title  = \__( 'Renew your subscription of Yoast SEO Premium', 'wordpress-seo' );
			$url    = 'https://yoa.st/workout-renew-notice';
			$copy   = \esc_html__( 'Accessing the latest workouts requires an updated version of Yoast SEO Premium, but it looks like your subscription has expired. Please renew your subscription to update and gain access to all the latest features.', 'wordpress-seo' );
			$button = '<a class="yoast-button yoast-button-upsell yoast-button--small" href="' . \esc_url( $url ) . '" target="_blank">'
					. esc_html__( 'Renew your subscription', 'wordpress-seo' )
					. '<span class="screen-reader-text">' . __( '(Opens in a new browser tab)', 'wordpress-seo' ) . '</span>'
					. '<span aria-hidden="true" class="yoast-button-upsell__caret"></span>'
					. '</a>';
		}
		elseif ( $this->has_premium_subscription_unactivated() ) {
			$title      = \__( 'Activate your subscription of Yoast SEO Premium', 'wordpress-seo' );
			$url_button = 'https://yoa.st/workouts-activate-notice-help';
			$url        = 'https://yoa.st/workouts-activate-notice-myyoast';
			$copy       = \sprintf(
				/* translators: 1: Link start tag to the page to MyYoast, 2: Link closing tag. */
				\esc_html__( 'It looks like you’re running an outdated and unactivated version of Yoast SEO Premium, please activate your subscription in %1$sMyYoast%2$s and update to the latest version to gain access to our updated workouts section.', 'wordpress-seo' ),
				'<a href="' . \esc_url( $url ) . '">',
				'</a>'
			);
			$button = '<a class="yoast-button yoast-button--primary yoast-button--small" href="' . \esc_url( $url_button ) . '" target="_blank">'
					. esc_html__( 'Get help activating your subscription', 'wordpress-seo' )
					. '<span class="screen-reader-text">' . __( '(Opens in a new browser tab)', 'wordpress-seo' ) . '</span>'
					. '</a>';
		}
		else {
			$title = \__( 'Update to the latest version of Yoast SEO Premium', 'wordpress-seo' );
			$url   = \wp_nonce_url( \self_admin_url( 'update.php?action=upgrade-plugin&plugin=wordpress-seo-premium/wp-seo-premium.php' ), 'upgrade-plugin_wordpress-seo-premium/wp-seo-premium.php' );
			$copy  = \sprintf(
				/* translators: 1: Link start tag to the page to update Premium, 2: Link closing tag. */
				__( 'It looks like you\'re running an outdated version of Yoast SEO Premium, please %1$supdate to the latest version%2$s to gain access to our updated workouts section.', 'wordpress-seo' ),
				'<a href="' . \esc_url( $url ) . '">',
				'</a>'
			);
			$button = null;
		}

		$notice = new Notice_Presenter(
			$title,
			$copy,
			'Assistent_Time_bubble_500x570.png',
			$button
		);

		return $notice->present();
	}

	/**
	 * Check whether Premium should be updated.
	 *
	 * @return bool Returns true when Premium is enabled and the version is below 17.7.
	 */
	private function should_update_premium() {
		$premium_version = $this->product_helper->get_premium_version();
		return $premium_version !== null && version_compare( $premium_version, '17.7-RC1', '<' );
	}

	/**
	 * Check whether the Premium subscription has expired.
	 *
	 * @return bool Returns true when Premium subscription has expired.
	 */
	private function has_premium_subscription_expired() {
		$subscription = $this->addon_manager->get_subscription( WPSEO_Addon_Manager::PREMIUM_SLUG );

		return ( isset( $subscription->expiry_date ) && ( strtotime( $subscription->expiry_date ) - time() ) < 0 );
	}

	/**
	 * Check whether the Premium subscription is unactivated.
	 *
	 * @return bool Returns true when Premium subscription is unactivated.
	 */
	private function has_premium_subscription_unactivated() {
		return ! $this->addon_manager->has_valid_subscription( WPSEO_Addon_Manager::PREMIUM_SLUG ) && ! $this->has_premium_subscription_expired();
	}

	/**
	 * Whether the user can do the configuration workout.
	 *
	 * @return bool Whether the current user can do the configuration workout.
	 */
	private function user_can_do_configuration_workout() {
		return \current_user_can( 'wpseo_manage_options' );
	}

	/**
	 * Whether the user is currently visiting one of our admin pages or the WordPress dashboard.
	 *
	 * @return bool Whether the current page is a Yoast SEO admin page
	 */
	private function on_wpseo_admin_page_or_dashboard() {
		$pagenow = $GLOBALS['pagenow'];

		// Show on the WP Dashboard.
		if ( $pagenow === 'index.php' ) {
			return true;
		}

		$page_from_get = filter_input( INPUT_GET, 'page' );

		// Show on Yoast SEO pages, with some exceptions.
		if ( $pagenow === 'admin.php' && strpos( $page_from_get, 'wpseo' ) === 0 ) {
			$exceptions = [
				'wpseo_workouts',
				'wpseo_installation_successful',
			];

			if ( ! \in_array( $page_from_get, $exceptions, true ) ) {
				return true;
			}
		}

		return false;
	}
}
