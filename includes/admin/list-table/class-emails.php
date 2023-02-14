<?php
namespace um\admin\list_table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Emails
 */
class Emails extends \WP_List_Table {


	/**
	 * @var string
	 */
	var $no_items_message = '';


	/**
	 * @var array
	 */
	var $sortable_columns = array();


	/**
	 * @var string
	 */
	var $default_sorting_field = '';


	/**
	 * @var array
	 */
	var $actions = array();


	/**
	 * @var array
	 */
	var $bulk_actions = array();


	/**
	 * @var array
	 */
	var $columns = array();


	/**
	 * UM_Emails_List_Table constructor.
	 *
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => __( 'item', 'ultimate-member' ),
			'plural'   => __( 'items', 'ultimate-member' ),
			'ajax'     => false,
		) );

		$this->no_items_message = $args['plural'] . ' ' . __( 'not found.', 'ultimate-member' );

		parent::__construct( $args );
	}


	/**
	 * @param callable $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	function __call( $name, $arguments ) {
		return call_user_func_array( array( $this, $name ), $arguments );
	}


	/**
	 *
	 */
	function prepare_items() {
		$screen = $this->screen;

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, array(), $sortable );

		$emails = UM()->config()->get( 'email_notifications' );

		@uasort($emails, function ( $a, $b ) {
			if ( strtolower( $a['title'] ) == strtolower( $b['title'] ) ) {
				return 0;
			}
			return ( strtolower( $a['title'] ) < strtolower( $b['title'] ) ) ? -1 : 1;
		});

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 999 );
		$paged = $this->get_pagenum();

		$this->items = array_slice( $emails, ( $paged - 1 ) * $per_page, $per_page );

		$this->set_pagination_args( array(
			'total_items' => count( $emails ),
			'per_page'    => $per_page,
		) );
	}


	/**
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		} else {
			return apply_filters( 'um_emails_list_table_custom_column_content', '', $item, $column_name );
		}
	}


	/**
	 *
	 */
	function no_items() {
		echo $this->no_items_message;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_sortable_columns( $args = array() ) {
		$return_args = array();
		foreach ( $args as $k => $val ) {
			if ( is_numeric( $k ) ) {
				$return_args[ $val ] = array( $val, $val == $this->default_sorting_field );
			} else if( is_string( $k ) ) {
				$return_args[ $k ] = array( $val, $k == $this->default_sorting_field );
			} else {
				continue;
			}
		}
		$this->sortable_columns = $return_args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_sortable_columns() {
		return $this->sortable_columns;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_columns( $args = array() ) {
		if ( count( $this->bulk_actions ) ) {
			$args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
		}
		$this->columns = $args;

		return $this;
	}


	/**
	 * @return array
	 */
	function get_columns() {
		return $this->columns;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_actions( $args = array() ) {
		$this->actions = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_actions() {
		return $this->actions;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_bulk_actions( $args = array() ) {
		$this->bulk_actions = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_bulk_actions() {
		return $this->bulk_actions;
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_email( $item ) {
		$active = UM()->options()->get( $item['key'] . '_on' );

		$icon = ! empty( $active ) ? ' um-notification-is-active dashicons-yes' : ' dashicons-no-alt';
		$link = add_query_arg( array( 'email' => $item['key'] ) );
		$text = '<span class="dashicons um-notification-status' . esc_attr( $icon ) . '"></span><a href="' . esc_url( $link ) . '"><strong>' . $item['title'] . '</strong></a>';

		if ( ! empty( $item['description'] ) ) {
			$text .= ' <span class="um-helptip dashicons dashicons-editor-help" title="' . esc_attr( $item['description'] ) . '"></span>';
		}

		return $text;
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_recipients( $item ) {
		if ( 'admin' === $item['recipient'] ) {
			return UM()->options()->get( 'admin_email' );
		} else {
			return __( 'Member', 'ultimate-member' );
		}
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_configure( $item ) {
		return '<a class="button um-email-configure" href="' . add_query_arg( array( 'email' => $item['key'] ) ) . '" title="' . esc_attr__( 'Edit template', 'ultimate-member' ) . '"><span class="dashicons dashicons-admin-generic"></span></a>';
	}
}
