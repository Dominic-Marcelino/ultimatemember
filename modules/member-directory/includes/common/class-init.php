<?php
namespace umm\member_directory\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\member_directory\includes\common
 */
class Init {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}


	public function includes() {
		$this->cpt()->hooks();
		$this->fields();
		$this->forms();
		$this->pages();
		$this->shortcode();
		$this->user();
	}

	/**
	 * @return CPT()
	 */
	public function cpt() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\cpt'] ) ) {
			UM()->classes['umm\member_directory\includes\common\cpt'] = new CPT();
		}
		return UM()->classes['umm\member_directory\includes\common\cpt'];
	}

	/**
	 * @return Fields()
	 */
	public function fields() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\fields'] ) ) {
			UM()->classes['umm\member_directory\includes\common\fields'] = new Fields();
		}
		return UM()->classes['umm\member_directory\includes\common\fields'];
	}

	/**
	 * @return Forms()
	 */
	public function forms() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\forms'] ) ) {
			UM()->classes['umm\member_directory\includes\common\forms'] = new Forms();
		}
		return UM()->classes['umm\member_directory\includes\common\forms'];
	}

	/**
	 * @return Pages()
	 */
	public function pages() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\pages'] ) ) {
			UM()->classes['umm\member_directory\includes\common\pages'] = new Pages();
		}
		return UM()->classes['umm\member_directory\includes\common\pages'];
	}

	/**
	 * @return Shortcode()
	 */
	public function shortcode() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\shortcode'] ) ) {
			UM()->classes['umm\member_directory\includes\common\shortcode'] = new Shortcode();
		}
		return UM()->classes['umm\member_directory\includes\common\shortcode'];
	}

	/**
	 * @return User()
	 */
	public function user() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\user'] ) ) {
			UM()->classes['umm\member_directory\includes\common\user'] = new User();
		}
		return UM()->classes['umm\member_directory\includes\common\user'];
	}
}
