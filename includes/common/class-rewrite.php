<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Rewrite' ) ) {

	/**
	 * Class Rewrite
	 * @package um\common
	 */
	class Rewrite {

		/**
		 * Rewrite constructor.
		 */
		public function __construct() {
			if ( ! defined( 'DOING_AJAX' ) ) {
				add_action( 'wp_loaded', array( $this, 'maybe_flush_rewrite_rules' ) );
			}

			//add rewrite rules
			add_filter( 'query_vars', array( &$this, 'query_vars' ), 10, 1 );
			add_filter( 'rewrite_rules_array', array( &$this, '_add_rewrite_rules' ), 10, 1 );

			add_action( 'wp_insert_post', array( $this, 'flush_post_rewrite_rules' ), 10, 3 );

			add_action( 'template_redirect', array( &$this, 'redirect_author_page' ), 9999 );
			add_action( 'template_redirect', array( &$this, 'locate_user_profile' ), 9999 );
		}

		/**
		 * Reset rewrite rules if predefined page has been updated
		 *
		 * @since 3.0
		 *
		 * @param int $post_ID
		 * @param \WP_Post $post
		 * @param bool $update
		 */
		public function flush_post_rewrite_rules( $post_ID, $post, $update ) {
			foreach ( UM()->config()->get( 'predefined_pages' ) as $slug => $data ) {
				if ( um_is_predefined_page( $slug, $post ) ) {
					$this->reset_rules();
					return;
				}
			}
		}

		/**
		 * Update "flush" option for reset rules on wp_loaded hook
		 */
		public function reset_rules() {
			update_option( 'um_flush_rewrite_rules', 1 );
		}

		/**
		 * Reset Rewrite rules if need it.
		 *
		 * @return void
		 */
		public function maybe_flush_rewrite_rules() {
			if ( get_option( 'um_flush_rewrite_rules' ) ) {
				flush_rewrite_rules( false );
				delete_option( 'um_flush_rewrite_rules' );
			}
		}

		/**
		 * Modify global query vars
		 *
		 * @param $public_query_vars
		 *
		 * @return array
		 */
		public function query_vars( $public_query_vars ) {
			$public_query_vars[] = 'um_user';
			$public_query_vars[] = 'um_tab';
			$public_query_vars[] = 'profiletab';
			$public_query_vars[] = 'subnav';

			$public_query_vars[] = 'um_page';
			$public_query_vars[] = 'um_action';
			$public_query_vars[] = 'um_field';
			$public_query_vars[] = 'um_form';
			$public_query_vars[] = 'um_verify';

			return $public_query_vars;
		}

		/**
		 * Add UM rewrite rules
		 *
		 * @param $rules
		 *
		 * @return array
		 */
		public function _add_rewrite_rules( $rules ) {
			$newrules = array();

			$newrules['um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?um_action=download&um_form=$matches[1]&um_field=$matches[2]&um_user=$matches[3]&um_verify=$matches[4]';

			$user_page_id = um_get_predefined_page_id( 'user' );
			if ( $user_page_id ) {
				$user = get_post( $user_page_id );

				if ( isset( $user->post_name ) ) {

					$user_slug = $user->post_name;
					$newrules[ $user_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $user_page_id . '&um_user=$matches[1]';
				}
			}

			$account_page_id = um_get_predefined_page_id( 'account' );
			if ( $account_page_id ) {

				$account = get_post( $account_page_id );
				if ( isset( $account->post_name ) ) {
					$account_slug = $account->post_name;
					$newrules[ $account_slug . '/([^/]+)?$' ] = 'index.php?page_id=' . $account_page_id . '&um_tab=$matches[1]';
				}
			}

			return $newrules + $rules;
		}

		/**
		 * Author page to user profile redirect
		 */
		public function redirect_author_page() {
			if ( UM()->options()->get( 'author_redirect' ) && is_author() ) {
				$id = get_query_var( 'author' );
				um_fetch_user( $id );
				exit( wp_redirect( um_user_profile_url() ) );
			}
		}

		/**
		 * Locate/display a profile
		 */
		public function locate_user_profile() {
			if ( um_queried_user() && um_is_predefined_page( 'user' ) ) {
				if ( UM()->options()->get( 'permalink_base' ) == 'user_login' ) {
					$user_id = username_exists( um_queried_user() );

					//Try
					if ( ! $user_id ) {
						$permalink_base = UM()->options()->get( 'permalink_base' );

						// Search by Profile Slug
						$args = array(
							'fields'     => 'ids',
							'meta_query' => array(
								array(
									'key'     =>  'um_user_profile_url_slug_' . $permalink_base,
									'value'   => strtolower( um_queried_user() ),
									'compare' => '=',
								),
							),
							'number'     => 1,
						);

						$ids = new \WP_User_Query( $args );
						if ( $ids->total_users > 0 ) {
							$user_id = current( $ids->get_results() );
						}
					}

					// Try nice name
					if ( ! $user_id ) {
						$slug = um_queried_user();
						$slug = str_replace( '.', '-', $slug );
						$the_user = get_user_by( 'slug', $slug );
						if ( isset( $the_user->ID ) ){
							$user_id = $the_user->ID;
						}

						if ( ! $user_id ) {
							$user_id = UM()->user()->user_exists_by_email_as_username( um_queried_user() );
						}

						if ( ! $user_id ) {
							$user_id = UM()->user()->user_exists_by_email_as_username( $slug );
						}
					}
				}

				if ( UM()->options()->get( 'permalink_base' ) == 'user_id' ) {
					$user_id = UM()->user()->user_exists_by_id( um_queried_user() );
				}

				if ( in_array( UM()->options()->get( 'permalink_base' ), array( 'name', 'name_dash', 'name_dot', 'name_plus' ) ) ) {
					$user_id = UM()->user()->user_exists_by_name( um_queried_user() );
				}

				/** USER EXISTS SET USER AND CONTINUE **/
				if ( $user_id ) {
					um_set_requested_user( $user_id );
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_access_profile
					 * @description Action on user access profile
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_access_profile', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_access_profile', 'my_access_profile', 10, 1 );
					 * function my_access_profile( $user_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_access_profile', $user_id );
				} else {
					exit( wp_redirect( um_get_predefined_page_url( 'user' ) ) );
				}
			} elseif ( um_is_predefined_page( 'user' ) ) {
				if ( is_user_logged_in() ) { // just redirect to their profile
					$query = UM()->permalinks()->get_query_array();
					$url   = um_user_profile_url( um_user( 'ID' ) );

					if ( $query ) {
						foreach ( $query as $key => $val ) {
							$url = add_query_arg( $key, $val, $url );
						}
					}

					exit( wp_redirect( $url ) );
				} else {
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_locate_user_profile_not_loggedin__redirect
					 * @description Change redirect URL from user profile for not logged in user
					 * @input_vars
					 * [{"var":"$url","type":"string","desc":"Redirect URL"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_locate_user_profile_not_loggedin__redirect', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_locate_user_profile_not_loggedin__redirect', 'my_user_profile_not_loggedin__redirect', 10, 1 );
					 * function my_user_profile_not_loggedin__redirect( $url ) {
					 *     // your code here
					 *     return $url;
					 * }
					 * ?>
					 */
					$redirect_to = apply_filters( 'um_locate_user_profile_not_loggedin__redirect', home_url() );
					if ( ! empty( $redirect_to ) ) {
						exit( wp_redirect( $redirect_to ) );
					}
				}
			}
		}
	}
}
