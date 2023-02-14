<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$role = $object['data'];
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-role-register um-half-column',
			'prefix_id' => 'role',
			'fields'    => array(
				array(
					'id'          => '_um_status',
					'type'        => 'select',
					'label'       => __( 'Registration Status', 'ultimate-member' ),
					'description' => __( 'Select the status you would like this user role to have after they register on your site', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_status'] ) ? __( $role['_um_status'] , 'ultimate-member' ) : array(),
					'options'     => array(
						'approved'  => __( 'Auto Approve', 'ultimate-member' ),
						'checkmail' => __( 'Require Email Activation', 'ultimate-member' ),
						'pending'   => __( 'Require Admin Review', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_auto_approve_act',
					'type'        => 'select',
					'label'       => __( 'Action to be taken after registration', 'ultimate-member' ),
					'description' => __( 'Select what action is taken after a person registers on your site. Depending on the status you can redirect them to their profile, a custom url or show a custom message', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_auto_approve_act'] ) ? __( $role['_um_auto_approve_act'], 'ultimate-member' ) : array(),
					'options'     => array(
						'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
						'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
					),
					'conditional' => array( '_um_status', '=', 'approved' ),
				),
				array(
					'id'          => '_um_auto_approve_url',
					'type'        => 'text',
					'label'       => __( 'Set Custom Redirect URL', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_auto_approve_url'] ) ? __( $role['_um_auto_approve_url'], 'ultimate-member' ) : '',
					'conditional' => array( '_um_auto_approve_act', '=', 'redirect_url' ),
				),
				array(
					'id'          => '_um_login_email_activate',
					'type'        => 'checkbox',
					'label'       => __( 'Login user after validating the activation link?', 'ultimate-member' ),
					'description' => __( 'Login the user after validating the activation link', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_login_email_activate'] ) ? __( $role['_um_login_email_activate'], 'ultimate-member' ) : 0,
					'conditional' => array( '_um_status', '=', 'checkmail' ),
				),
				array(
					'id'          => '_um_checkmail_action',
					'type'        => 'select',
					'label'       => __( 'Action to be taken after registration', 'ultimate-member' ),
					'description' => __( 'Select what action is taken after a person registers on your site. Depending on the status you can redirect them to their profile, a custom url or show a custom message', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_checkmail_action'] ) ? __( $role['_um_checkmail_action'], 'ultimate-member' ) : array(),
					'options'     => array(
						'show_message' => __( 'Show custom message', 'ultimate-member' ),
						'redirect_url' => __( 'Redirect to URL', 'ultimate-member' ),
					),
					'conditional' => array( '_um_status', '=', 'checkmail' ),
				),
				array(
					'id'          => '_um_checkmail_message',
					'type'        => 'textarea',
					'label'       => __( 'Personalize the custom message', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_checkmail_message'] ) ? __( $role['_um_checkmail_message'], 'ultimate-member' ) : __('Thank you for registering. Before you can login we need you to activate your account by clicking the activation link in the email we just sent you.','ultimate-member'),
					'conditional' => array( '_um_checkmail_action', '=', 'show_message' ),
				),
				array(
					'id'          => '_um_checkmail_url',
					'type'        => 'text',
					'label'       => __( 'Set Custom Redirect URL', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_checkmail_url'] ) ? __( $role['_um_checkmail_url'], 'ultimate-member' ) : '',
					'conditional' => array( '_um_checkmail_action', '=', 'redirect_url' ),
				),
				array(
					'id'          => '_um_url_email_activate',
					'type'        => 'text',
					'label'       => __( 'URL redirect after e-mail activation', 'ultimate-member' ),
					'description' => __( 'If you want users to go to a specific page other than login page after e-mail activation, enter the URL here.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_url_email_activate'] ) ? __( $role['_um_url_email_activate'], 'ultimate-member' ) : '',
					'conditional' => array( '_um_status', '=', 'checkmail' ),
				),
				array(
					'id'          => '_um_pending_action',
					'type'        => 'select',
					'label'       => __( 'Action to be taken after registration', 'ultimate-member' ),
					'description' => __( 'Select what action is taken after a person registers on your site. Depending on the status you can redirect them to their profile, a custom url or show a custom message', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_pending_action'] ) ? __( $role['_um_pending_action'], 'ultimate-member' ) : array(),
					'options'     => array(
						'show_message' => __( 'Show custom message', 'ultimate-member' ),
						'redirect_url' => __( 'Redirect to URL', 'ultimate-member' ),
					),
					'conditional' => array( '_um_status', '=', 'pending' ),
				),
				array(
					'id'          => '_um_pending_message',
					'type'        => 'textarea',
					'label'       => __( 'Personalize the custom message', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_pending_message'] ) ? __( $role['_um_pending_message'], 'ultimate-member' ) : __('Thank you for applying for membership to our site. We will review your details and send you an email letting you know whether your application has been successful or not.','ultimate-member'),
					'conditional' => array( '_um_pending_action', '=', 'show_message' ),
				),
				array(
					'id'          => '_um_pending_url',
					'type'        => 'text',
					'label'       => __( 'Set Custom Redirect URL', 'ultimate-member' ),
					'conditional' => array( '_um_pending_action', '=', 'redirect_url' ),
					'value'       => ! empty( $role['_um_pending_url'] ) ? __( $role['_um_pending_url'], 'ultimate-member' ) : '',
				),
			),
		)
	)->render_form();
	?>
</div>
