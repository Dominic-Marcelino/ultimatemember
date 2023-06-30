<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error handling: blocked emails
 *
 * @param $submitted_data
 */
function um_submit_form_errors_hook__blockedemails( $submitted_data ) {
	$emails = UM()->options()->get( 'blocked_emails' );
	if ( ! $emails ) {
		return;
	}

	$emails = strtolower( $emails );
	$emails = array_map( 'rtrim', explode( "\n", $emails ) );

	if ( isset( $submitted_data['user_email'] ) && is_email( $submitted_data['user_email'] ) ) {
		if ( in_array( strtolower( $submitted_data['user_email'] ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_email' ) ) ) );
		}

		$domain       = explode( '@', $submitted_data['user_email'] );
		$check_domain = str_replace( $domain[0], '*', $submitted_data['user_email'] );

		if ( in_array( strtolower( $check_domain ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_domain' ) ) ) );
		}
	}

	if ( isset( $submitted_data['username'] ) && is_email( $submitted_data['username'] ) ) {
		if ( in_array( strtolower( $submitted_data['username'] ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_email' ) ) ) );
		}

		$domain       = explode( '@', $submitted_data['username'] );
		$check_domain = str_replace( $domain[0], '*', $submitted_data['username'] );

		if ( in_array( strtolower( $check_domain ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_domain' ) ) ) );
		}
	}
}
add_action( 'um_submit_form_errors_hook__blockedemails', 'um_submit_form_errors_hook__blockedemails' );


/**
 * Error handling: blocked IPs.
 */
function um_submit_form_errors_hook__blockedips() {
	$ips = UM()->options()->get( 'blocked_ips' );
	if ( ! $ips ) {
		return;
	}

	$ips = array_map( 'rtrim', explode( "\n", $ips ) );
	$user_ip = um_user_ip();

	foreach ( $ips as $ip ) {
		$ip = str_replace( '*', '', $ip );
		if ( ! empty( $ip ) && strpos( $user_ip, $ip ) === 0 ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_ip' ) ) ) );
		}
	}
}
add_action( 'um_submit_form_errors_hook__blockedips', 'um_submit_form_errors_hook__blockedips' );


/**
 * Error handling: blocked words during sign up
 *
 * @param $args
 */
function um_submit_form_errors_hook__blockedwords( $args ) {
	$words = UM()->options()->get( 'blocked_words' );
	if ( empty( $words ) ) {
		return;
	}

	$fields = unserialize( $args['custom_fields'] );

	$words = strtolower( $words );
	$words = array_map( 'rtrim', explode( "\n", $words ) );
	if ( ! empty( $fields ) && is_array( $fields ) ) {
		foreach ( $fields as $key => $array ) {
			if ( isset( $array['validate'] ) && in_array( $array['validate'], array( 'unique_username', 'unique_email', 'unique_username_or_email' ) ) ) {
				if ( ! UM()->form()->has_error( $key ) && isset( $args[ $key ] ) && in_array( strtolower( $args[ $key ] ), $words ) ) {
					UM()->form()->add_error( $key, __( 'You are not allowed to use this word as your username.', 'ultimate-member' ) );
				}
			}
		}
	}
}
add_action( 'um_submit_form_errors_hook__blockedwords', 'um_submit_form_errors_hook__blockedwords', 10 );


/**
 * UM login|register|profile form error handling.
 *
 * @param array $submitted_data
 * @param array $form_data
 */
function um_submit_form_errors_hook( $submitted_data, $form_data ) {
	$mode = $form_data['mode'];

	/**
	 * Fires for validation blocked IPs when UM login, registration or profile form has been submitted.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 10 - `um_submit_form_errors_hook__blockedips()` Native validation handlers.
	 *
	 * @since 1.3.x
	 * @hook um_submit_form_errors_hook__blockedips
	 *
	 * @param {array} $submitted_data $_POST Submission array.
	 * @param {array} $form_data      UM form data. Since 2.6.7
	 *
	 * @example <caption>Make any common validation action here.</caption>
	 * function my_submit_form_errors_hook__blockedips( $post, $form_data ) {
	 *     // your code here
	 * }
	 * add_action( 'um_submit_form_errors_hook__blockedips', 'my_submit_form_errors_hook__blockedips', 10, 2 );
	 */
	do_action( 'um_submit_form_errors_hook__blockedips', $submitted_data, $form_data );
	/**
	 * Fires for validation blocked email addresses when UM login, registration or profile form has been submitted.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 10 - `um_submit_form_errors_hook__blockedemails()` Native validation handlers.
	 *
	 * @since 1.3.x
	 * @hook um_submit_form_errors_hook__blockedemails
	 *
	 * @param {array} $submitted_data $_POST Submission array.
	 * @param {array} $form_data      UM form data. Since 2.6.7
	 *
	 * @example <caption>Make any common validation action here.</caption>
	 * function my_submit_form_errors_hook__blockedemails( $post, $form_data ) {
	 *     // your code here
	 * }
	 * add_action( 'um_submit_form_errors_hook__blockedemails', 'my_submit_form_errors_hook__blockedemails', 10, 2 );
	 */
	do_action( 'um_submit_form_errors_hook__blockedemails', $submitted_data, $form_data );

	if ( 'login' === $mode ) {
		/**
		 * Fires for login form validation when it has been submitted.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 10 - `um_submit_form_errors_hook_login()` Native login validation handlers.
		 *
		 * @since 1.3.x
		 * @hook um_submit_form_errors_hook_login
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any common validation action here.</caption>
		 * function my_submit_form_errors_hook_login( $post, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_submit_form_errors_hook_login', 'my_submit_form_errors_hook_login', 10, 2 );
		 */
		do_action( 'um_submit_form_errors_hook_login', $submitted_data, $form_data );
		/**
		 * Fires for login form validation when it has been submitted.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 9999 - `um_submit_form_errors_hook_logincheck()` Native login validation handlers.
		 *
		 * @since 1.3.x
		 * @hook um_submit_form_errors_hook_logincheck
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any common validation action here.</caption>
		 * function my_submit_form_errors_hook_logincheck( $post, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_submit_form_errors_hook_logincheck', 'my_submit_form_errors_hook_logincheck', 10, 2 );
		 */
		do_action( 'um_submit_form_errors_hook_logincheck', $submitted_data, $form_data );
		// ------ Reviewed. --------- //
	} else {
		if ( 'register' === $mode ) {
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_submit_form_errors_hook__registration
			 * @description Submit registration form validation
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_submit_form_errors_hook__registration', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_submit_form_errors_hook__registration', 'my_submit_form_errors_registration', 10, 1 );
			 * function my_submit_form_errors_registration( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_submit_form_errors_hook__registration', $submitted_data, $form_data );
		} elseif ( 'profile' === $mode ) {
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_submit_form_errors_hook__registration
			 * @description Submit registration form validation
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_submit_form_errors_hook__registration', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_submit_form_errors_hook__profile', 'my_submit_form_errors_hook__profile', 10, 1 );
			 * function my_submit_form_errors_registration( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_submit_form_errors_hook__profile', $submitted_data, $form_data );
		}

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_submit_form_errors_hook__blockedwords
		 * @description Submit form validation
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_submit_form_errors_hook__blockedwords', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_submit_form_errors_hook__blockedwords', 'my_submit_form_errors_hook__blockedwords', 10, 1 );
		 * function my_submit_form_errors_hook__blockedwords( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_submit_form_errors_hook__blockedwords', $submitted_data, $form_data );
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_submit_form_errors_hook_
		 * @description Submit form validation
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_submit_form_errors_hook_', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_submit_form_errors_hook_', 'my_submit_form_errors_hook', 10, 1 );
		 * function my_submit_form_errors_hook( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_submit_form_errors_hook_', $submitted_data, $form_data );
	}
}
add_action( 'um_submit_form_errors_hook', 'um_submit_form_errors_hook', 10, 2 );


/**
 * Error processing: Conditions
 * @staticvar int     $counter
 * @param     array   $condition
 * @param     array   $fields
 * @param     array   $args
 * @param     boolean $reset
 * @return    boolean
 * @throws    Exception
 */
function um_check_conditions_on_submit( $condition, $fields, $args, $reset = false ) {
	static $counter = 0;
	if ( $reset ) {
		$counter = 0;
	}
	$continue = false;

	list( $visibility, $parent_key, $op, $parent_value ) = $condition;

	if ( ! isset( $args[ $parent_key ] ) ) {
		$continue = true;
		return $continue;
	}

	if ( ! empty( $fields[ $parent_key ]['conditions'] ) ) {
		foreach ( $fields[ $parent_key ]['conditions'] as $parent_condition ) {
			if ( 64 > $counter++ ) {
				$continue = um_check_conditions_on_submit( $parent_condition, $fields, $args );
			} else {
				throw new Exception( 'Endless recursion in the function ' . __FUNCTION__, 512 );
			}
			if ( ! empty( $continue ) ) {
				return $continue;
			}
		}
	}

	$cond_value = ( $fields[ $parent_key ]['type'] == 'radio' ) ? $args[ $parent_key ][0] : $args[ $parent_key ];

	if ( $visibility == 'hide' ) {
		if ( $op == 'empty' ) {
			if ( empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'not empty' ) {
			if ( ! empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'equals to' ) {
			if ( $cond_value == $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'not equals' ) {
			if ( $cond_value != $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'greater than' ) {
			if ( $cond_value > $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'less than' ) {
			if ( $cond_value < $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'contains' ) {
			if ( is_string( $cond_value ) && strstr( $cond_value, $parent_value ) ) {
				$continue = true;
			}
			if( is_array( $cond_value ) && in_array( $parent_value, $cond_value ) ) {
				$continue = true;
			}
		}
	} elseif ( $visibility == 'show' ) {
		if ( $op == 'empty' ) {
			if ( ! empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'not empty' ) {
			if ( empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'equals to' ) {
			if ( $cond_value != $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'not equals' ) {
			if ( $cond_value == $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'greater than' ) {
			if ( $cond_value <= $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'less than' ) {
			if ( $cond_value >= $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'contains' ) {
			if ( is_string( $cond_value ) && ! strstr( $cond_value, $parent_value ) ) {
				$continue = true;
			}
			if( is_array( $cond_value ) && !in_array( $parent_value, $cond_value ) ) {
				$continue = true;
			}
		}
	}

	return $continue;
}


/**
 * Error processing hook : standard
 *
 * @param $args
 */
function um_submit_form_errors_hook_( $args ) {
	$form_id = $args['form_id'];
	$mode    = $args['mode'];
	$fields  = unserialize( $args['custom_fields'] );

	$um_profile_photo = um_profile('profile_photo');
	if ( get_post_meta( $form_id, '_um_profile_photo_required', true ) && ( empty( $args['profile_photo'] ) && empty( $um_profile_photo ) ) ) {
		UM()->form()->add_error('profile_photo', __( 'Profile Photo is required.', 'ultimate-member' ) );
	}

	if ( ! empty( $fields ) ) {
		$can_edit           = false;
		$current_user_roles = array();
		if ( is_user_logged_in() ) {
			if ( array_key_exists( 'user_id', $args ) ) {
				$can_edit = UM()->roles()->um_current_user_can( 'edit', $args['user_id'] );
			}

			um_fetch_user( get_current_user_id() );
			$current_user_roles = um_user( 'roles' );
			um_reset_user();
		}

		foreach ( $fields as $key => $array ) {

			if ( 'profile' === $mode ) {
				$restricted_fields = UM()->fields()->get_restricted_fields_for_edit();
				if ( is_array( $restricted_fields ) && in_array( $key, $restricted_fields ) ) {
					continue;
				}
			}

			$can_view = true;
			if ( isset( $array['public'] ) && 'register' !== $mode ) {

				switch ( $array['public'] ) {
					case '1': // Everyone
						break;
					case '2': // Members
						if ( ! is_user_logged_in() ) {
							$can_view = false;
						}
						break;
					case '-1': // Only visible to profile owner and admins
						if ( ! is_user_logged_in() ) {
							$can_view = false;
						} elseif ( $args['user_id'] != get_current_user_id() && ! $can_edit ) {
							$can_view = false;
						}
						break;
					case '-2': // Only specific member roles
						if ( ! is_user_logged_in() ) {
							$can_view = false;
						} elseif ( ! empty( $array['roles'] ) && count( array_intersect( $current_user_roles, $array['roles'] ) ) <= 0 ) {
							$can_view = false;
						}
						break;
					case '-3': // Only visible to profile owner and specific roles
						if ( ! is_user_logged_in() ) {
							$can_view = false;
						} elseif ( $args['user_id'] != get_current_user_id() && ! empty( $array['roles'] ) && count( array_intersect( $current_user_roles, $array['roles'] ) ) <= 0 ) {
							$can_view = false;
						}
						break;
					default:
						$can_view = apply_filters( 'um_can_view_field_custom', $can_view, $array );
						break;
				}

			}

			$can_view = apply_filters( 'um_can_view_field', $can_view, $array );

			if ( ! $can_view ) {
				continue;
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_get_custom_field_array
			 * @description Extend custom field data on submit form error
			 * @input_vars
			 * [{"var":"$array","type":"array","desc":"Field data"},
			 * {"var":"$fields","type":"array","desc":"All fields"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_get_custom_field_array', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_get_custom_field_array', 'my_get_custom_field_array', 10, 2 );
			 * function my_get_custom_field_array( $array, $fields ) {
			 *     // your code here
			 *     return $array;
			 * }
			 * ?>
			 */
			$array = apply_filters( 'um_get_custom_field_array', $array, $fields );

			if ( ! empty( $array['conditions'] ) ) {
				try {
					foreach ( $array['conditions'] as $condition ) {
						$continue = um_check_conditions_on_submit( $condition, $fields, $args, true );
						if ( $continue === true ) {
							continue 2;
						}
					}
				} catch ( Exception $e ) {
					UM()->form()->add_error( $key, sprintf( __( '%s - wrong conditions.', 'ultimate-member' ), $array['title'] ) );
					$notice = '<div class="um-field-error">' . sprintf( __( '%s - wrong conditions.', 'ultimate-member' ), $array['title'] ) . '</div><!-- ' . $e->getMessage() . ' -->';
					add_action( 'um_after_profile_fields', function() use ( $notice ) {
						echo $notice;
					}, 900 );
				}
			}

			if ( isset( $array['type'] ) && $array['type'] == 'checkbox' && isset( $array['required'] ) && $array['required'] == 1 && ! isset( $args[ $key ] ) ) {
				UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member' ), $array['title'] ) );
			}

			if ( isset( $array['type'] ) && $array['type'] == 'radio' && isset( $array['required'] ) && $array['required'] == 1 && ! isset( $args[ $key ] ) && ! in_array( $key, array( 'role_radio', 'role_select' ) ) ) {
				UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member'), $array['title'] ) );
			}

			if ( isset( $array['type'] ) && $array['type'] == 'multiselect' && isset( $array['required'] ) && $array['required'] == 1 && ! isset( $args[ $key ] ) && ! in_array( $key, array( 'role_radio', 'role_select' ) ) ) {
				UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member' ), $array['title'] ) );
			}

			/* WordPress uses the default user role if the role wasn't chosen in the registration form. That is why we should use submitted data to validate fields Roles (Radio) and Roles (Dropdown). */
			if ( in_array( $key, array( 'role_radio', 'role_select' ) ) && isset( $array['required'] ) && $array['required'] == 1 && empty( UM()->form()->post_form['submitted']['role'] ) ) {
				UM()->form()->add_error( 'role', __( 'Please specify account type.', 'ultimate-member' ) );
				UM()->form()->post_form[ $key ] = '';
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_add_error_on_form_submit_validation
			 * @description Submit form validation
			 * @input_vars
			 * [{"var":"$field","type":"array","desc":"Field Data"},
			 * {"var":"$key","type":"string","desc":"Field Key"},
			 * {"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_add_error_on_form_submit_validation', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_action( 'um_add_error_on_form_submit_validation', 'my_add_error_on_form_submit_validation', 10, 3 );
			 * function my_add_error_on_form_submit_validation( $field, $key, $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_add_error_on_form_submit_validation', $array, $key, $args );

			if ( ! empty( $array['required'] ) ) {
				if ( ! isset( $args[ $key ] ) || $args[ $key ] == '' || $args[ $key ] == 'empty_file' ) {
					if ( empty( $array['label'] ) ) {
						UM()->form()->add_error( $key, __( 'This field is required', 'ultimate-member' ) );
					} else {
						UM()->form()->add_error( $key, sprintf( __( '%s is required', 'ultimate-member' ), $array['label'] ) );
					}
				}
			}

			if ( isset( $args[ $key ] ) ) {

				if ( isset( $array['max_words'] ) && $array['max_words'] > 0 ) {
					if ( str_word_count( $args[ $key ], 0, "éèàôù" ) > $array['max_words'] ) {
						UM()->form()->add_error( $key, sprintf( __( 'You are only allowed to enter a maximum of %s words', 'ultimate-member' ), $array['max_words'] ) );
					}
				}

				if ( isset( $array['min_chars'] ) && $array['min_chars'] > 0 ) {
					if ( $args[ $key ] && mb_strlen( $args[ $key ] ) < $array['min_chars'] ) {
						if ( empty( $array['label'] ) ) {
							UM()->form()->add_error( $key, sprintf( __( 'This field must contain at least %s characters', 'ultimate-member' ), $array['min_chars'] ) );
						} else {
							UM()->form()->add_error( $key, sprintf( __( 'Your %s must contain at least %s characters', 'ultimate-member' ), $array['label'], $array['min_chars'] ) );
						}
					}
				}

				if ( isset( $array['max_chars'] ) && $array['max_chars'] > 0 ) {
					if ( $args[ $key ] && mb_strlen( $args[ $key ] ) > $array['max_chars'] ) {
						if ( empty( $array['label'] ) ) {
							UM()->form()->add_error( $key, sprintf( __( 'This field must contain less than %s characters', 'ultimate-member' ), $array['max_chars'] ) );
						} else {
							UM()->form()->add_error( $key, sprintf( __( 'Your %s must contain less than %s characters', 'ultimate-member' ), $array['label'], $array['max_chars'] ) );
						}
					}
				}

				if ( isset( $array['type'] ) && $array['type'] == 'textarea' && UM()->profile()->get_show_bio_key( $args ) !== $key ) {
					if ( ! isset( $array['html'] ) || $array['html'] == 0 ) {
						if ( wp_strip_all_tags( $args[ $key ] ) != trim( $args[ $key ] ) ) {
							UM()->form()->add_error( $key, __( 'You can not use HTML tags here', 'ultimate-member' ) );
						}
					}
				}

				if ( isset( $array['force_good_pass'] ) && $array['force_good_pass'] && ! empty( $args['user_password'] ) ) {
					if ( isset( $args['user_login'] ) && strpos( strtolower( $args['user_login'] ), strtolower( $args['user_password'] )  ) > -1 ) {
						UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your username', 'ultimate-member' ));
					}

					if ( isset( $args['user_email'] ) && strpos( strtolower( $args['user_email'] ), strtolower( $args['user_password'] )  ) > -1 ) {
						UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your email address', 'ultimate-member' ));
					}

					if ( ! UM()->validation()->strong_pass( $args[ $key ] ) ) {
						UM()->form()->add_error( $key, __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
					}
				}

				if ( ! empty( $array['force_confirm_pass'] ) ) {
					if ( ! array_key_exists( 'confirm_' . $key, $args ) && ! UM()->form()->has_error( $key ) ) {
						UM()->form()->add_error( 'confirm_' . $key, __( 'Please confirm your password', 'ultimate-member' ) );
					} else {
						if ( '' === $args[ 'confirm_' . $key ] && ! UM()->form()->has_error( $key ) ) {
							UM()->form()->add_error( 'confirm_' . $key, __( 'Please confirm your password', 'ultimate-member' ) );
						}
						if ( $args[ 'confirm_' . $key ] !== $args[ $key ] && ! UM()->form()->has_error( $key ) ) {
							UM()->form()->add_error( 'confirm_' . $key, __( 'Your passwords do not match', 'ultimate-member' ) );
						}
					}
				}

				if ( isset( $array['min_selections'] ) && $array['min_selections'] > 0 ) {
					if ( ( ! isset( $args[ $key ] ) ) || ( isset( $args[ $key ] ) && is_array( $args[ $key ] ) && count( $args[ $key ] ) < $array['min_selections'] ) ) {
						UM()->form()->add_error( $key, sprintf( __( 'Please select at least %s choices', 'ultimate-member' ), $array['min_selections'] ) );
					}
				}

				if ( isset( $array['max_selections'] ) && $array['max_selections'] > 0 ) {
					if ( isset( $args[ $key ] ) && is_array( $args[ $key ] ) && count( $args[ $key ] ) > $array['max_selections'] ) {
						UM()->form()->add_error( $key, sprintf( __( 'You can only select up to %s choices', 'ultimate-member' ), $array['max_selections'] ) );
					}
				}

				if ( isset( $array['min'] ) && is_numeric( $args[ $key ] ) ) {
					if ( isset( $args[ $key ] )  && $args[ $key ] < $array['min'] ) {
						UM()->form()->add_error( $key, sprintf( __( 'Minimum number limit is %s', 'ultimate-member' ), $array['min'] ) );
					}
				}

				if ( isset( $array['max'] ) && is_numeric( $args[ $key ] )  ) {
					if ( isset( $args[ $key ] ) && $args[ $key ] > $array['max'] ) {
						UM()->form()->add_error( $key, sprintf( __( 'Maximum number limit is %s', 'ultimate-member' ), $array['max'] ) );
					}
				}

				if ( ! empty( $array['validate'] ) ) {

					switch( $array['validate'] ) {

						case 'custom':
							$custom = $array['custom_validate'];
							/**
							 * UM hook
							 *
							 * @type action
							 * @title um_custom_field_validation_{$custom}
							 * @description Submit form validation for custom field
							 * @input_vars
							 * [{"var":"$key","type":"string","desc":"Field Key"},
							 * {"var":"$field","type":"array","desc":"Field Data"},
							 * {"var":"$args","type":"array","desc":"Form Arguments"}]
							 * @change_log
							 * ["Since: 2.0"]
							 * @usage add_action( 'um_custom_field_validation_{$custom}', 'function_name', 10, 3 );
							 * @example
							 * <?php
							 * add_action( 'um_custom_field_validation_{$custom}', 'my_custom_field_validation', 10, 3 );
							 * function my_custom_field_validation( $key, $field, $args ) {
							 *     // your code here
							 * }
							 * ?>
							 */
							do_action( "um_custom_field_validation_{$custom}", $key, $array, $args );
							break;

						case 'numeric':
							if ( $args[ $key ] && ! is_numeric( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Please enter numbers only in this field', 'ultimate-member' ) );
							}
							break;

						case 'phone_number':
							if ( ! UM()->validation()->is_phone_number( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Please enter a valid phone number', 'ultimate-member' ) );
							}
							break;

						case 'youtube_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 'youtube.com' ) && ! UM()->validation()->is_url( $args[ $key ], 'youtu.be' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'spotify_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 'open.spotify.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'telegram_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 't.me' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'soundcloud_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 'soundcloud.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'facebook_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 'facebook.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'twitter_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 'twitter.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'instagram_url':

							if ( ! UM()->validation()->is_url( $args[ $key ], 'instagram.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'linkedin_url':
							if ( ! UM()->validation()->is_url( $args[ $key ], 'linkedin.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'discord':
							if ( ! UM()->validation()->is_discord_id( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Please enter a valid Discord ID', 'ultimate-member' ) );
							}
							break;

						case 'tiktok_url':

							if ( ! UM()->validation()->is_url( $args[ $key ], 'tiktok.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'twitch_url':

							if ( ! UM()->validation()->is_url( $args[ $key ], 'twitch.tv' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'reddit_url':

							if ( ! UM()->validation()->is_url( $args[ $key ], 'reddit.com' ) ) {
								UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
							}
							break;

						case 'url':
							if ( ! UM()->validation()->is_url( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Please enter a valid URL', 'ultimate-member' ) );
							}
							break;

						case 'unique_username':

							if ( $args[ $key ] == '' ) {
								UM()->form()->add_error( $key, __( 'You must provide a username', 'ultimate-member' ) );
							} elseif ( $mode == 'register' && username_exists( sanitize_user( $args[ $key ] ) ) ) {
								UM()->form()->add_error( $key, __( 'The username you entered is incorrect', 'ultimate-member' ) );
							} elseif ( is_email( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Username cannot be an email', 'ultimate-member' ) );
							} elseif ( ! UM()->validation()->safe_username( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Your username contains invalid characters', 'ultimate-member' ) );
							}

							break;

						case 'unique_username_or_email':

							if ( $args[ $key ] == '' ) {
								UM()->form()->add_error( $key, __( 'You must provide a username or email', 'ultimate-member' ) );
							} elseif ( $mode == 'register' && username_exists( sanitize_user( $args[ $key ] ) ) ) {
								UM()->form()->add_error( $key, __( 'The username you entered is incorrect', 'ultimate-member' ) );
							} elseif ( $mode == 'register' && email_exists( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
							} elseif ( ! UM()->validation()->safe_username( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'Your username contains invalid characters', 'ultimate-member' ) );
							}

							break;

						case 'unique_email':

							$args[ $key ] = trim( $args[ $key ] );

							if ( in_array( $key, array( 'user_email' ) ) ) {

								if ( ! isset( $args['user_id'] ) ){
									$args['user_id'] = um_get_requested_user();
								}

								$email_exists = email_exists( $args[ $key ] );

								if ( $args[ $key ] == '' && in_array( $key, array( 'user_email' ) ) ) {
									UM()->form()->add_error( $key, __( 'You must provide your email', 'ultimate-member' ) );
								} elseif ( in_array( $mode, array( 'register' ) ) && $email_exists  ) {
									UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
								} elseif ( in_array( $mode, array( 'profile' ) ) && $email_exists && $email_exists != $args['user_id']  ) {
									UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
								} elseif ( ! is_email( $args[ $key ] ) ) {
									UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member') );
								} elseif ( ! UM()->validation()->safe_username( $args[ $key ] ) ) {
									UM()->form()->add_error( $key,  __( 'Your email contains invalid characters', 'ultimate-member' ) );
								}

							} else {

								if ( $args[ $key ] != '' && ! is_email( $args[ $key ] ) ) {
									UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
								} elseif ( $args[ $key ] != '' && email_exists( $args[ $key ] ) ) {
									UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
								} elseif ( $args[ $key ] != '' ) {

									$users = get_users( 'meta_value=' . $args[ $key ] );

									foreach ( $users as $user ) {
										if ( $user->ID != $args['user_id'] ) {
											UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
										}
									}

								}

							}

							break;

						case 'is_email':

							$args[ $key ] = trim( $args[ $key ] );

							if ( $args[ $key ] != '' && ! is_email( $args[ $key ] ) ) {
								UM()->form()->add_error( $key, __( 'This is not a valid email', 'ultimate-member' ) );
							}

							break;

						case 'unique_value':

							if ( $args[ $key ] != '' ) {

								$args_unique_meta = array(
									'meta_key'      => $key,
									'meta_value'    => $args[ $key ],
									'compare'       => '=',
									'exclude'       => array( $args['user_id'] ),
								);

								$meta_key_exists = get_users( $args_unique_meta );

								if ( $meta_key_exists ) {
									UM()->form()->add_error( $key , __( 'You must provide a unique value', 'ultimate-member' ) );
								}
							}
							break;

						case 'alphabetic':

							if ( $args[ $key ] != '' ) {

								if ( ! preg_match( '/^\p{L}+$/u', str_replace( ' ', '', $args[ $key ] ) ) ) {
									UM()->form()->add_error( $key, __( 'You must provide alphabetic letters', 'ultimate-member' ) );
								}

							}

							break;

						case 'lowercase':

							if ( $args[ $key ] != '' ) {

								if ( ! ctype_lower( str_replace(' ', '', $args[ $key ] ) ) ) {
									UM()->form()->add_error( $key , __( 'You must provide lowercase letters.', 'ultimate-member' ) );
								}
							}

							break;

					}

				}

			}

			if ( isset( $args['description'] ) ) {
				$max_chars = UM()->options()->get( 'profile_bio_maxchars' );
				$profile_show_bio = UM()->options()->get( 'profile_show_bio' );

				if ( $profile_show_bio ) {
					if ( mb_strlen( str_replace( array( "\r\n", "\n", "\r\t", "\t" ), ' ', $args['description'] ) ) > $max_chars && $max_chars ) {
						UM()->form()->add_error( 'description', sprintf( __( 'Your user description must contain less than %s characters', 'ultimate-member' ), $max_chars ) );
					}
				}

			}

		} // end if ( isset in args array )
	}
}
add_action( 'um_submit_form_errors_hook_', 'um_submit_form_errors_hook_', 10 );


/**
 * @param string $url
 *
 * @return string
 */
function um_invalid_nonce_redirect_url( $url ) {
	$url = add_query_arg( [
		'um-hash'   => substr( md5( rand() ), 0, 6 ),
	], remove_query_arg( 'um-hash', $url ) );

	return $url;
}
add_filter( 'um_login_invalid_nonce_redirect_url', 'um_invalid_nonce_redirect_url', 10, 1 );
add_filter( 'um_register_invalid_nonce_redirect_url', 'um_invalid_nonce_redirect_url', 10, 1 );
