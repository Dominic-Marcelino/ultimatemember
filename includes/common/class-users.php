<?php
namespace um\common;

use WP_Comment;
use WP_Post;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users
 *
 * @package um\common
 */
class Users {

	/**
	 * Hooks function.
	 */
	public function hooks() {
		$this->add_filters();

		add_filter( 'avatar_defaults', array( $this, 'remove_filters' ) );
		add_filter( 'default_avatar_select', array( $this, 'add_filters_cb' ) );
	}

	public function add_filters() {
		add_filter( 'pre_get_avatar_data', array( $this, 'change_avatar' ), 10, 2 );
		// add_filter( 'pre_get_avatar_data', array( $this, 'change_avatar' ), 10, 2 );
	}

	public function remove_filters( $avatar_defaults ) {
		remove_filter( 'pre_get_avatar_data', array( $this, 'change_avatar' ) );
		return $avatar_defaults;
	}

	public function add_filters_cb( $avatar_list ) {
		$this->add_filters();
		return $avatar_list;
	}

	/**
	 * Set UM default avatar data to avoid WordPress native handler and make it faster.
	 *
	 * Passing a non-null value in the 'url' member of the return array will
	 * effectively short circuit get_avatar_data(), passing the value through
	 * the {@see 'get_avatar_data'} filter and returning early.
	 *
	 * @param array $args Arguments passed to get_avatar_data(), after processing.
	 * @param mixed $id_or_email The avatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
	 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array
	 */
	public function change_avatar( $args, $id_or_email ) {
		$user = false;
		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$id_or_email = get_comment( $id_or_email );
		}

		// Process the user identifier.
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', absint( $id_or_email ) );
		} elseif ( $id_or_email instanceof WP_User ) {
			// User object.
			$user = $id_or_email;
		} elseif ( $id_or_email instanceof WP_Post ) {
			// Post object.
			$user = get_user_by( 'id', (int) $id_or_email->post_author );
		} elseif ( $id_or_email instanceof WP_Comment ) {
			// Comment object.
			if ( ! empty( $id_or_email->user_id ) && is_avatar_comment_type( get_comment_type( $id_or_email ) ) ) {
				$user = get_user_by( 'id', (int) $id_or_email->user_id );
			}
		}

		// Escape from this callback because there isn't user.
		if ( ! $user || is_wp_error( $user ) ) {
			return $args;
		}

		$url           = '';
		$profile_photo = get_user_meta( $user->ID, 'profile_photo', true );
		if ( ! empty( $profile_photo ) ) {
			$ext       = '.' . pathinfo( $profile_photo, PATHINFO_EXTENSION );
			$all_sizes = UM()->config()->get( 'avatar_thumbnail_sizes' );
			sort( $all_sizes );

//			$rraa = array();
//			foreach ( $all_sizes as $sub_size ) {
//				$rraa[ $sub_size ] = array( 'width' => $sub_size, 'height' => $sub_size, 'crop' => false );
//			}
//
//			$image_editor = wp_get_image_editor( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" );
//
//			$image_editor->multi_resize( $rraa );
//			//$image_editor->save();
//			//var_dump( $image_editor );


//			if ( file_exists( $original_path ) ) {
//				list( $original_width, $original_height ) = getimagesize( $original_path );
//				if ( ! in_array( $original_width, $all_sizes, true ) ) {
//					$all_sizes[] = $original_width;
//				}
//			}

			$size = '';
			if ( array_key_exists( 'size', $args ) ) {
				$size = UM()->get_closest_value( $all_sizes, $args['size'] );
			}

			$locate = array();
			if ( '' !== $size ) {
				foreach ( $all_sizes as $pre_size ) {
					if ( $size > $pre_size ) {
						continue;
					}

					$locate[] = "profile_photo-{$pre_size}x{$pre_size}{$ext}";
				}
			}
			$locate[] = "profile_photo{$ext}";

			if ( is_multisite() ) {
				// Multisite fix for old customers
				$multisite_fix_dir = UM()->uploader()->get_upload_base_dir();
				$multisite_fix_url = UM()->uploader()->get_upload_base_url();
				$multisite_fix_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $multisite_fix_dir );
				$multisite_fix_url = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $multisite_fix_url );

				foreach ( $locate as $avatar_basename ) {
					if ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $avatar_basename ) ) {
						$url = $multisite_fix_url . um_user( 'ID' ) . '/' . $avatar_basename;
						break;
					}
				}
			}

			if ( empty( $url ) ) {
				foreach ( $locate as $avatar_basename ) {
					if ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $avatar_basename ) ) {
						$url = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . '/' . $avatar_basename;
						break;
					}
				}
			}
		}

		if ( empty( $url ) ) {
			$replace_gravatar = UM()->options()->get( 'use_um_gravatar_default_image' );
			if ( $replace_gravatar ) {
				$default_avatar = UM()->options()->get( 'default_avatar' );
				$url            = ! empty( $default_avatar['url'] ) ? $default_avatar['url'] : '';
				if ( empty( $url ) ) {
					$url = UM_URL . 'assets/img/default_avatar.jpg';
				}

				$args['url'] = set_url_scheme( $url );
				//$args['um_default'] = true;
				if ( ! empty( $args['class'] ) ) {
					$args['class'][] = 'um-avatar-default';
				} else {
					$args['class'] = array( 'um-avatar-default' );
				}
			}
		} else {
			$args['url'] = set_url_scheme( $url );
			//$args['um_uploaded']  = true;
			$args['found_avatar'] = true;
			if ( ! empty( $args['class'] ) ) {
				$args['class'][] = 'um-avatar-uploaded';
			} else {
				$args['class'] = array( 'um-avatar-uploaded' );
			}
		}

		return $args;
	}
}
