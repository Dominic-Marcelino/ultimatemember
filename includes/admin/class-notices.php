<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Notices' ) ) {

	/**
	 * Class Notices
	 *
	 * @package um\admin
	 */
	class Notices {

		/**
		 * Notices list
		 *
		 * @var array
		 */
		public $list = array();

		/**
		 * Notices constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( &$this, 'create_languages_folder' ) );
			add_action( 'admin_init', array( &$this, 'force_dismiss_notice' ) );

//			 later than admin_init for checking the $current_screen variable
			add_action( 'current_screen', array( &$this, 'create_list' ), 10, 1 );

			add_action( 'admin_notices', array( &$this, 'render_notices' ), 1 );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1000 );
		}

		/**
		 * @param \WP_Screen $current_screen
		 */
		public function create_list( $current_screen ) {
			$this->old_extensions_notice();
			$this->install_predefined_page_notice();
			$this->exif_extension_notice();
			$this->show_update_messages();
			$this->check_wrong_install_folder();
			$this->need_upgrade();
			$this->check_wrong_licenses();

			$this->lock_registration();

			if ( ! empty( $current_screen ) && 'toplevel_page_ultimatemember' === $current_screen->id ) {
				// add admin notice only on the Settings page
				$this->legacy_enabled_modules();
				$this->legacy_notices_options();
			}

			// removed for now to avoid the bad reviews
			//$this->reviews_notice();

			//$this->future_changed();

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_create_notices
			 * @description Add notices to wp-admin
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_create_notices', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_create_notices', 'my_admin_create_notices', 10 );
			 * function my_admin_create_notices() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_create_notices', $current_screen );
		}

		/**
		 * @return array
		 */
		public function get_admin_notices() {
			return $this->list;
		}

		/**
		 * @param $admin_notices
		 */
		public function set_admin_notices( $admin_notices ) {
			$this->list = $admin_notices;
		}

		/**
		 * @param $a
		 * @param $b
		 *
		 * @return mixed
		 */
		public function notice_priority_sort( $a, $b ) {
			if ( $a['priority'] == $b['priority'] ) {
				return 0;
			}
			return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
		}

		/**
		 * Add notice to UM notices array
		 *
		 * @param string $key
		 * @param array $data
		 * @param int $priority
		 */
		public function add_notice( $key, $data, $priority = 10 ) {
			$admin_notices = $this->get_admin_notices();

			if ( empty( $admin_notices[ $key ] ) ) {
				$admin_notices[ $key ] = array_merge( $data, array( 'priority' => $priority ) );
				$this->set_admin_notices( $admin_notices );
			}
		}

		/**
		 * Remove notice from UM notices array
		 *
		 * @param string $key
		 */
		public function remove_notice( $key ) {
			$admin_notices = $this->get_admin_notices();

			if ( ! empty( $admin_notices[ $key ] ) ) {
				unset( $admin_notices[ $key ] );
				$this->set_admin_notices( $admin_notices );
			}
		}

		/**
		 * Render all admin notices
		 */
		public function render_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$admin_notices = $this->get_admin_notices();

			$hidden = get_option( 'um_hidden_admin_notices', array() );
			if ( ! is_array( $hidden ) ) {
				$hidden = array();
			}

			uasort( $admin_notices, array( &$this, 'notice_priority_sort' ) );

			foreach ( $admin_notices as $key => $admin_notice ) {
				if ( empty( $hidden ) || ! in_array( $key, $hidden ) ) {
					$this->display_notice( $key );
				}
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_after_main_notices
			 * @description Insert some content after main admin notices
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_after_main_notices', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_after_main_notices', 'my_admin_after_main_notices', 10 );
			 * function my_admin_after_main_notices() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_after_main_notices' );
		}

		/**
		 * Display single admin notice
		 *
		 * @param string $key
		 * @param bool $echo
		 *
		 * @return void|string
		 */
		public function display_notice( $key, $echo = true ) {
			$admin_notices = $this->get_admin_notices();

			if ( empty( $admin_notices[ $key ] ) ) {
				return;
			}

			$notice_data = $admin_notices[ $key ];

			$class = ! empty( $notice_data['class'] ) ? $notice_data['class'] : 'updated';

			$dismissible = ! empty( $admin_notices[ $key ]['dismissible'] );

			ob_start(); ?>

			<div class="<?php echo esc_attr( $class ) ?> um-admin-notice notice <?php echo $dismissible ? 'is-dismissible' : '' ?>" data-key="<?php echo esc_attr( $key ) ?>">
				<?php echo ! empty( $notice_data['message'] ) ? $notice_data['message'] : '' ?>
			</div>

			<?php $notice = ob_get_clean();
			if ( $echo ) {
				echo $notice;
				return;
			} else {
				return $notice;
			}
		}

		/**
		 * Checking if the "Membership - Anyone can register" WordPress general setting is active
		 */
		public function lock_registration() {
			$users_can_register = get_option( 'users_can_register' );
			if ( ! $users_can_register ) {
				return;
			}

			$allowed_html = array(
				'a'      => array(
					'href' => array(),
				),
				'strong' => array(),
			);

			$this->add_notice( 'lock_registration', array(
				'class'       => 'info',
				'message'     => '<p>' . wp_kses( sprintf( __( 'The <strong>"Membership - Anyone can register"</strong> option on the general settings <a href="%s">page</a> is enabled. This means users can register via the standard WordPress wp-login.php page. If you do not want users to be able to register via this page and only register via the Ultimate Member registration form, you should deactivate this option. You can dismiss this notice if you wish to keep the wp-login.php registration page open.', 'ultimate-member' ), admin_url( 'options-general.php' ) . '#users_can_register' ), $allowed_html ) . '</p>',
				'dismissible' => true,
			), 10 );
		}

		/**
		 * To store plugin languages
		 */
		public function create_languages_folder() {
			$path = UM()->files()->upload_basedir;
			if ( empty( $path ) ) {
				return;
			}

			$path = str_replace( '/uploads/ultimatemember', '', $path );
			$path = $path . '/languages/plugins/';
			$path = str_replace( '//', '/', $path );

			if ( ! file_exists( $path ) ) {
				$old = umask(0);
				@mkdir( $path, 0777, true );
				umask( $old );
			}
		}

		/**
		 * Show notice for customers with old extension's versions
		 */
		public function old_extensions_notice() {
			$show = false;

			$old_extensions = array(
				'bbpress',
				'followers',
				'friends',
				'instagram',
				'mailchimp',
				'messaging',
				'mycred',
				'notices',
				'notifications',
				'online',
				'private-content',
				'profile-completeness',
				'recaptcha',
				'reviews',
				'social-activity',
				'social-login',
				'terms-conditions',
				'user-tags',
				'verified-users',
				'woocommerce',
			);

			$slugs = array_map( function( $item ) {
				return 'um-' . $item . '/um-' . $item . '.php';
			}, $old_extensions );

			$active_plugins = UM()->dependencies()->get_active_plugins();
			foreach ( $slugs as $slug ) {
				if ( in_array( $slug, $active_plugins ) ) {
					$path = wp_normalize_path( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug );
					if ( ! file_exists( $path ) ) {
						continue;
					}
					$plugin_data = get_plugin_data( $path );
					if ( version_compare( '2.0', $plugin_data['Version'], '>' ) ) {
						$show = true;
						break;
					}
				}
			}

			if ( ! $show ) {
				return;
			}

			$this->add_notice( 'old_extensions', array(
				'class'   => 'error',
				'message' => '<p>' . sprintf( __( '<strong>%s %s</strong> requires 2.0 extensions. You have pre 2.0 extensions installed on your site. <br /> Please update %s extensions to latest versions. For more info see this <a href="%s" target="_blank">doc</a>.', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION, UM_PLUGIN_NAME, 'https://docs.ultimatemember.com/article/201-how-to-update-your-site' ) . '</p>',
			), 0 );
		}

		public function legacy_enabled_modules() {
			if ( ! isset( $_GET['tab'] ) || 'modules' !== sanitize_key( $_GET['tab'] ) ) {
				return;
			}

			if ( ! empty( $_GET['section'] ) ) {
				return;
			}

			if ( ! UM()->is_legacy ) {
				return;
			}

			$this->add_notice( 'modules_when_legacy', array(
				'class'       => 'warning',
				'message'     => '<p>' . __( 'There is the text about unavailable to use modules with legacy design.', 'ultimate-member' ) . '</p>',
				'dismissible' => false,
			), 1 );
		}

		public function legacy_notices_options() {
			if ( isset( $_GET['section'] ) ) {
				return;
			}

			if ( ! isset( $_GET['tab'] ) || 'misc' !== sanitize_key( $_GET['tab'] ) ) {
				return;
			}

			$extension_plugins = UM()->config()->get( 'extension_plugins' );
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}

			// disable option for v3 designs while some of the old extensions are active
			$active_extension_plugins = array_intersect( $active_plugins, $extension_plugins );
			if ( ! empty( $active_extension_plugins ) ) {
				$this->add_notice( 'v3_design_disabled', array(
					'class'       => 'warning',
					'message'     => '<p>' . __( 'There is the text about unavailable to use v3 design because of old extensions are active.', 'ultimate-member' ) . '</p>',
					'dismissible' => false,
				), 1 );
			}

			// disable option for legacy designs while some of the modules are active
			if ( ! UM()->is_legacy ) {
				$modules = UM()->modules()->get_list();
				if ( ! empty( $modules ) ) {
					foreach ( $modules as $slug => $data ) {
						if ( UM()->modules()->is_active( $slug ) ) {
							$this->add_notice( 'v2_design_disabled', array(
								'class'       => 'warning',
								'message'     => '<p>' . __( 'There is the text about unavailable to use the legacy design because of v3 modules are active.', 'ultimate-member' ) . '</p>',
								'dismissible' => false,
							), 1 );
							break;
						}
					}
				}
			}
		}

		/**
		 * Regarding page setup
		 */
		public function install_predefined_page_notice() {
			$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );

			foreach ( $predefined_pages as $slug ) {
				$page_id = um_get_predefined_page_id( $slug );
				if ( $page_id ) {
					$page = get_post( $page_id );
					if ( $page ) {
						continue;
					}
				}

				ob_start();
				?>

				<p>
					<?php printf( __( '%s needs to create several pages (User Profiles, Account, Registration, Login, Password Reset, Logout) to function correctly.', 'ultimate-member' ), UM_PLUGIN_NAME ); ?>
				</p>

				<p>
					<a href="<?php echo esc_url( add_query_arg( 'um_adm_action', 'install_predefined_pages' ) ); ?>" class="button button-primary"><?php _e( 'Create Pages', 'ultimate-member' ) ?></a>
					&nbsp;
					<a href="javascript:void(0);" class="button-secondary um_secondary_dismiss"><?php _e( 'No thanks', 'ultimate-member' ) ?></a>
				</p>

				<?php
				$message = ob_get_clean();

				$this->add_notice(
					'wrong_pages',
					array(
						'class'       => 'updated',
						'message'     => $message,
						'dismissible' => true,
					),
					20
				);

				break;
			}

			$user_page_id    = um_get_predefined_page_id( 'user' );
			$account_page_id = um_get_predefined_page_id( 'account' );

			if ( ! empty( $user_page_id ) ) {
				$test = get_post( $user_page_id );
				if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
					$this->add_notice( 'wrong_user_page', array(
						'class'   => 'updated',
						'message' => '<p>' . wp_kses( __( '<strong>Ultimate Member Setup Error:</strong> User page can not be a child page.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
					), 25 );
				}
			}

			if ( ! empty( $account_page_id ) ) {
				$test = get_post( $account_page_id );
				if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
					$this->add_notice( 'wrong_account_page', array(
						'class'   => 'updated',
						'message' => '<p>' . wp_kses( __( '<strong>Ultimate Member Setup Error:</strong> Account page can not be a child page.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
					), 30 );
				}
			}

			if (  ! empty( $user_page_id ) && ! empty( $account_page_id ) && $user_page_id === $account_page_id ) {
				$this->add_notice( 'wrong_account_user_page', array(
					'class'   => 'error',
					'message' => '<p>' . wp_kses( __( '<strong>Ultimate Member Setup Error:</strong> Account page and User page should be separate pages.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
				), 30 );
			}

			if ( um_get_predefined_page_id( 'logout' ) === (int) get_option( 'page_on_front' ) ) {
				$this->add_notice( 'wrong_logout_page_on_home', array(
					'class'   => 'error',
					'message' => '<p>' . wp_kses( __( '<strong>Ultimate Member Setup Error:</strong> Home page must not be chosen as the Logout page.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
				), 30 );
			}

			// avoid conflicts with WooCommerce
			if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'myaccount' ) === um_get_predefined_page_id( 'account' ) ) {
				$this->add_notice( 'wrong_account_my_account_page', array(
					'class'   => 'error',
					'message' => '<p>' . wp_kses( __( '<strong>Ultimate Member Setup Error:</strong> Account page and WooCommerce "My account" page should be separate pages.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
				), 30 );
			}
		}

		/**
		* EXIF library notice
		*/
		public function exif_extension_notice() {
			$hide_exif_notice = get_option( 'um_hide_exif_notice' );

			if ( ! extension_loaded( 'exif' ) && ! $hide_exif_notice ) {
				$this->add_notice( 'exif_disabled', array(
					'class'   => 'updated',
					'message' => '<p>' . sprintf( __( 'Exif is not enabled on your server. Mobile photo uploads will not be rotated correctly until you enable the exif extension. <a href="%s">Hide this notice</a>', 'ultimate-member' ), add_query_arg( 'um_adm_action', 'um_hide_exif_notice' ) ) . '</p>',
				), 10 );
			}
		}

		/**
		 * Updating users
		 */
		public function show_update_messages() {
			if ( ! isset( $_REQUEST['update'] ) ) {
				return;
			}

			$update = sanitize_key( $_REQUEST['update'] );
			switch( $update ) {

				case 'confirm_delete':
					$request_users = array_map( 'absint', (array) $_REQUEST['user'] );

					$confirm_uri = admin_url( 'users.php?' . http_build_query( array(
						'um_adm_action' => 'delete_users',
						'user'          => $request_users,
						'confirm'       => 1,
					) ) );
					$users = '';

					if ( isset( $request_users ) ) {
						foreach ( $request_users as $user_id ) {
							$user = get_userdata( $user_id );
							$users .= '#' . $user_id . ': ' . $user->user_login . '<br />';
						}
					}

					$ignore = admin_url( 'users.php' );

					$messages[0]['err_content'] = sprintf( __( 'Are you sure you want to delete the selected user(s)? The following users will be deleted: <p>%s</p> <strong>This cannot be undone!</strong>', 'ultimate-member' ), $users );
					$messages[0]['err_content'] .= '<p><a href="'. esc_url( $confirm_uri ) .'" class="button-primary">' . __( 'Remove', 'ultimate-member' ) . '</a>&nbsp;&nbsp;<a href="' . esc_url( $ignore ) . '" class="button">' . __( 'Undo', 'ultimate-member' ) . '</a></p>';

					break;

				case 'language_updated':
					$messages[0]['content'] = __( 'Your translation files have been updated successfully.', 'ultimate-member' );
					break;

				case 'got_updates':
					$messages[0]['content'] = __( 'You have the latest updates.', 'ultimate-member' );
					break;

				case 'often_updates':
					$messages[0]['err_content'] = __( 'Try again later. You can run this action once daily.', 'ultimate-member' );
					break;

				case 'form_duplicated':
					$messages[0]['content'] = __( 'The form has been duplicated successfully.', 'ultimate-member' );
					break;

				case 'settings_updated':
					$messages[0]['content'] = __( 'Settings have been saved successfully.', 'ultimate-member' );
					break;

				case 'version_upgraded':
					if ( ! UM()->admin()->db_upgrade()->need_upgrade() ) {
						$messages[0]['content'] = sprintf( __( '<strong>%s %s</strong> upgraded successfully.', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION );
					}
					break;

				case 'user_updated':
					$messages[0]['content'] = __( 'User has been updated.', 'ultimate-member' );
					break;

				case 'um_approved':
					$approved_count = isset( $_REQUEST['approved_count'] ) ? absint( $_REQUEST['approved_count'] ) : 0;

					$messages[0]['content'] = sprintf( _n( '<strong>%s</strong> user has been approved.', '<strong>%s</strong> users have been approved.', $approved_count, 'ultimate-member' ), $approved_count );
					break;
				case 'um_reactivated':
					$reactivated_count = isset( $_REQUEST['reactivated_count'] ) ? absint( $_REQUEST['reactivated_count'] ) : 0;

					$messages[0]['content'] = sprintf( _n( '<strong>%s</strong> user has been reactivated.', '<strong>%s</strong> users have been reactivated.', $reactivated_count, 'ultimate-member' ), $reactivated_count );
					break;
				case 'um_rejected':
					$rejected_count = isset( $_REQUEST['rejected_count'] ) ? absint( $_REQUEST['rejected_count'] ) : 0;

					$messages[0]['content'] = sprintf( _n( '<strong>%s</strong> user has been rejected.', '<strong>%s</strong> users have been rejected.', $rejected_count, 'ultimate-member' ), $rejected_count );
					break;
				case 'um_deactivate':
					$deactivated_count = isset( $_REQUEST['deactivated_count'] ) ? absint( $_REQUEST['deactivated_count'] ) : 0;

					$messages[0]['content'] = sprintf( _n( '<strong>%s</strong> user has been deactivated.', '<strong>%s</strong> users have been deactivated.', $deactivated_count, 'ultimate-member' ), $deactivated_count );
					break;
				case 'um_pending':
					$pending_count = isset( $_REQUEST['pending_count'] ) ? absint( $_REQUEST['pending_count'] ) : 0;

					$messages[0]['content'] = sprintf( _n( '<strong>%s</strong> user has been set as pending admin review.', '<strong>%s</strong> users have been set as pending admin review.', $pending_count, 'ultimate-member' ), $pending_count );
					break;
				case 'um_resend_activation':
					$resend_activation_count = isset( $_REQUEST['resend_activation_count'] ) ? absint( $_REQUEST['resend_activation_count'] ) : 0;

					$messages[0]['content'] = sprintf( _n( 'Activation email for <strong>%s</strong> user has been sent.', 'Activation emails for <strong>%s</strong> users have been sent.', $resend_activation_count, 'ultimate-member' ), $resend_activation_count );
					break;

				case 'err_users_updated':
					$messages[0]['err_content'] = __( 'Super administrators cannot be modified.', 'ultimate-member' );
					$messages[1]['content'] = __( 'Other users have been updated.', 'ultimate-member' );
					break;
			}

			if ( ! empty( $messages ) ) {
				foreach ( $messages as $message ) {
					if ( isset( $message['err_content'] ) ) {
						$this->add_notice( 'actions', array(
							'class'   => 'error',
							'message' => '<p>' . $message['err_content'] . '</p>',
						), 50 );
					} else {
						$this->add_notice( 'actions', array(
							'class'   => 'updated',
							'message' => '<p>' . $message['content'] . '</p>',
						), 50 );
					}
				}
			}

		}

		/**
		 * Check if plugin is installed with correct folder
		 */
		public function check_wrong_install_folder() {
			$invalid_folder = false;

			$slug_array = explode( '/', UM_PLUGIN );
			if ( $slug_array[0] != 'ultimate-member' ) {
				$invalid_folder = true;
			}

			if ( $invalid_folder ) {
				$this->add_notice( 'invalid_dir', array(
					'class'   => 'error',
					'message' => '<p>' . sprintf( __( 'You have installed <strong>%s</strong> with wrong folder name. Correct folder name is <strong>"ultimate-member"</strong>.', 'ultimate-member' ), UM_PLUGIN_NAME ) . '</p>',
				), 1 );
			}
		}

		public function check_wrong_licenses() {
			$invalid_license = 0;
			$arr_inactive_license_keys = array();

			if ( empty( UM()->admin()->settings()->settings_structure['licenses']['fields'] ) ) {
				return;
			}

			foreach ( UM()->admin()->settings()->settings_structure['licenses']['fields'] as $field_data ) {
				$license = get_option( "{$field_data['id']}_edd_answer" );

				if ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license )
					continue;

				if ( ( is_object( $license ) && 'inactive' == $license->license ) || 'inactive' == $license ) {
					$arr_inactive_license_keys[] = $license->item_name;
				}

				$invalid_license++;
			}

			if ( ! empty( $arr_inactive_license_keys ) ) {
				$this->add_notice( 'license_key', array(
					'class'   => 'error',
					'message' => '<p>' . sprintf( __( 'There are %d inactive %s license keys for this site. This site is not authorized to get plugin updates. You can active this site on <a href="%s">www.ultimatemember.com</a>.', 'ultimate-member' ), count( $arr_inactive_license_keys ) , UM_PLUGIN_NAME, UM()->store_url ) . '</p>',
				), 3 );
			}

			if ( $invalid_license ) {
				$this->add_notice( 'license_key', array(
					'class'   => 'error',
					'message' => '<p>' . sprintf( __( 'You have %d invalid or expired license keys for %s. Please go to the <a href="%s">Licenses page</a> to correct this issue.', 'ultimate-member' ), $invalid_license, UM_PLUGIN_NAME, add_query_arg( array( 'page' => 'ultimatemember', 'tab' => 'modules' ), admin_url( 'admin.php' ) ) ) . '</p>',
				), 3 );
			}
		}


		public function need_upgrade() {
			if ( ! UM()->admin()->db_upgrade()->need_upgrade() ) {
				return;
			}

			$url = add_query_arg( array( 'page' => 'um_upgrade' ), admin_url( 'admin.php' ) );

			ob_start();
			?>
			<p>
				<?php printf( __( '<strong>%s version %s</strong> needs to be updated to work correctly.<br />It is necessary to update the structure of the database and options that are associated with <strong>%s %s</strong>.<br />Please visit <a href="%s">"Upgrade"</a> page and run the upgrade process.', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION, UM_PLUGIN_NAME, UM_VERSION, $url ); ?>
			</p>

			<p>
				<a href="<?php echo esc_url( $url ) ?>" class="button button-primary"><?php _e( 'Visit Upgrade Page', 'ultimate-member' ) ?></a>
				&nbsp;
			</p>
			<?php
			$message = ob_get_clean();

			$this->add_notice(
				'upgrade',
				array(
					'class'   => 'error',
					'message' => $message,
				),
				4
			);
		}

		/**
		 *
		 */
		public function reviews_notice() {
			$first_activation_date = get_option( 'um_first_activation_date', false );

			if ( empty( $first_activation_date ) ) {
				return;
			}

			if ( $first_activation_date + 2 * WEEK_IN_SECONDS > time() ) {
				return;
			}

			ob_start(); ?>

			<div id="um_start_review_notice">
				<p>
					<?php printf( __( 'Hey there! It\'s been one month since you installed %s. How have you found the plugin so far?', 'ultimate-member' ), UM_PLUGIN_NAME ) ?>
				</p>
				<p>
					<a href="javascript:void(0);" id="um_add_review_love"><?php _e( 'I love it!', 'ultimate-member' ) ?></a>&nbsp;|&nbsp;
					<a href="javascript:void(0);" id="um_add_review_good"><?php _e('It\'s good but could be better', 'ultimate-member' ) ?></a>&nbsp;|&nbsp;
					<a href="javascript:void(0);" id="um_add_review_bad"><?php _e('I don\'t like the plugin', 'ultimate-member' ) ?></a>
				</p>
			</div>
			<div class="um-hidden-notice" data-key="love">
				<p>
					<?php printf( __( 'Great! We\'re happy to hear that you love the plugin. It would be amazing if you could let others know why you like %s by leaving a review of the plugin. This will help %s to grow and become more popular and would be massively appreciated by us!' ), UM_PLUGIN_NAME, UM_PLUGIN_NAME ); ?>
				</p>

				<p>
					<a href="https://wordpress.org/support/plugin/ultimate-member/reviews/?rate=5#new-post" target="_blank" class="button button-primary um_review_link"><?php _e( 'Leave Review', 'ultimate-member' ) ?></a>
				</p>
			</div>
			<div class="um-hidden-notice" data-key="good">
				<p>
					<?php _e( 'We\'re glad to hear that you like the plugin but we would love to get your feedback so we can make the plugin better.' ); ?>
				</p>

				<p>
					<a href="https://ultimatemember.com/feedback/" target="_blank" class="button button-primary um_review_link"><?php _e( 'Provide Feedback', 'ultimate-member' ) ?></a>
				</p>
			</div>
			<div class="um-hidden-notice" data-key="bad">
				<p>
					<?php printf( __( 'We\'re sorry to hear that. If you\'re having the issue with the plugin you can create a topic on our <a href="%s" target="_blank">support forum</a> and we will try and help you out with the issue. Alternatively if you have an idea on how we can make the plugin better or want to tell us what you don\'t like about the plugin you can tell us know by giving us feedback.' ), 'https://wordpress.org/support/plugin/ultimate-member' ); ?>
				</p>

				<p>
					<a href="https://ultimatemember.com/feedback/" target="_blank" class="button button-primary um_review_link"><?php _e( 'Provide Feedback', 'ultimate-member' ) ?></a>
				</p>
			</div>

			<?php $message = ob_get_clean();

			$this->add_notice( 'reviews_notice', array(
				'class'       => 'updated',
				'message'     => $message,
				'dismissible' => true,
			), 1 );
		}

		/**
		 * Check Future Changes notice
		 */
		public function future_changed() {
			ob_start();
			?>
			<p>
				<?php printf( __( '<strong>%s</strong> future plans! Detailed future list is <a href="%s" target="_blank">here</a>', 'ultimate-member' ), UM_PLUGIN_NAME, '#' ); ?>
			</p>
			<?php
			$message = ob_get_clean();

			$this->add_notice(
				'future_changes',
				array(
					'class'   => 'updated',
					'message' => $message,
				),
				2
			);
		}


		/**
		 * Callback for listening wp-admin and force dismiss notice
		 * (case when AJAX callback has been break)
		 */
		public function force_dismiss_notice() {
			if ( ! empty( $_REQUEST['um_dismiss_notice'] ) && ! empty( $_REQUEST['um_admin_nonce'] ) ) {
				if ( wp_verify_nonce( $_REQUEST['um_admin_nonce'], 'um-admin-nonce' ) ) {
					$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
					if ( ! is_array( $hidden_notices ) ) {
						$hidden_notices = array();
					}

					$hidden_notices[] = sanitize_key( $_REQUEST['um_dismiss_notice'] );

					update_option( 'um_hidden_admin_notices', $hidden_notices );
				} else {
					wp_die( __( 'Security Check', 'ultimate-member' ) );
				}
			}
		}

		/**
		 * Change the admin footer text on UM admin pages
		 *
		 * @param $footer_text
		 *
		 * @return string
		 */
		public function admin_footer_text( $footer_text ) {
			$current_screen = get_current_screen();

			// Add the dashboard pages
			$um_pages[] = 'toplevel_page_ultimatemember';
			$um_pages[] = 'edit-um_role';

			$um_pages = apply_filters( 'um_admin_footer_text_pages', $um_pages );

			if ( ( isset( $current_screen->id ) && in_array( $current_screen->id, $um_pages ) ) || UM()->admin()->screen()->is_own_post_type() ) {
				// Change the footer text
				if ( ! get_option( 'um_admin_footer_text_rated' ) ) {
					ob_start();
					?>
					<a href="https://wordpress.org/support/plugin/ultimate-member/reviews/?filter=5" target="_blank" class="um-admin-rating-link" data-rated="<?php esc_attr_e( 'Thanks :)', 'ultimate-member' ) ?>">
						&#9733;&#9733;&#9733;&#9733;&#9733;
					</a>
					<?php
					$link = ob_get_clean();

					ob_start();

					printf( __( 'If you like Ultimate Member please consider leaving a %s review. It will help us to grow the plugin and make it more popular. Thank you.', 'ultimate-member' ), $link ) ?>

					<script type="text/javascript">
						jQuery( 'a.um-admin-rating-link' ).click(function() {
							jQuery.ajax({
								url: wp.ajax.settings.url,
								type: 'post',
								data: {
									action: 'um_rated',
									nonce: um_admin_scripts.nonce
								},
								success: function(){

								}
							});
							jQuery(this).parent().text( jQuery( this ).data( 'rated' ) );
						});
					</script>
					<?php
					$footer_text = ob_get_clean();
				}
			}

			return $footer_text;
		}
	}
}
