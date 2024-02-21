
<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * REST API Zorem Account Area controller.
 *
 * Handles requests to /account area endpoint.
 *
 * @since 1.1
 */

class Zorem_Account_REST_API_Controller
{

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;

	/**
	 * Get the class instance
	 */
	public static function get_instance()
	{

		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct()
	{
		// echo 'fhfgg'; exit;
		add_action('rest_api_init', array($this, 'rest_api_register_routes'));

	}

	/**
	 * Register the routes for trackings.
	 */
	public function rest_api_register_routes()
	{

		//woocommerce order lists
		register_rest_route('account', 'account-orders', array(
			'methods' => 'POST',
			'callback' => array($this, 'account_orders'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		));

		//CONNECTED STORES
		register_rest_route('account', 'connected-stores', array(
			'methods' => 'POST',
			'callback' => array($this, 'connected_stores_list'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		));

		//woocommerce subscription order lists
		register_rest_route('account', 'account-subscription', array(
			'methods' => 'POST',
			'callback' => array($this, 'account_subscription_order_list'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		));

		//woocommerce account setting
		register_rest_route('account', 'setting-account-info', array(
			'methods' => 'POST',
			'callback' => array($this, 'get_account_settings'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//user update-password
		register_rest_route('account', 'settings/update-password', array(
			'methods' => 'POST',
			'callback' => array($this, 'update_account_password'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//update user info 
		register_rest_route('account', 'settings/update-user', array(
			'methods' => 'POST',
			'callback' => array($this, 'update_account_info'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//Deactivate CONNECTED STORES
		register_rest_route('account', 'settings/connnected-store-deactivate', array(
			'methods' => 'POST',
			'callback' => array($this, 'connnected_store_deactivate'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//get Auto Renew value
		register_rest_route('account', 'settings/subscription/auto-renew', array(
			'methods' => 'POST',
			'callback' => array($this, 'get_auto_renew'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//get view-orders
		register_rest_route('account', 'view-orders', array(
			'methods' => 'POST',
			'callback' => array($this, 'single_view_orders'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//get user information
		register_rest_route('account', 'user-info', array(
			'methods' => 'POST',
			'callback' => array($this, 'get_user_account_information'),
			'permission_callback' => array($this, 'account_endpoint_validate'),
		)
		);

		//change user subscription
		register_rest_route( 'account', 'my-account/subscriptions',array(
			'methods'               => 'POST',
			'callback'              => array( $this, 'change_user_subscription' ),
			'permission_callback'   => array( $this, 'account_endpoint_validate' ),
		));

		//my-account/affiliate-registration-form/
		register_rest_route( 'account', 'my-account/affiliate-registration-form',array(
			'methods'               => 'POST',
			'callback'              => array( $this, 'zorem_affiliate_registration_form' ),
			'permission_callback'   => array( $this, 'account_endpoint_validate' ),
		));

		//my-account/affiliate-dashboard/
		register_rest_route( 'account', 'my-account/affiliate-dashboard',array(
			'methods'               => 'POST',
			'callback'              => array( $this, 'zorem_affiliate_dashboard' ),
			'permission_callback'   => array( $this, 'account_endpoint_validate' ),
		));

	}

	public function zorem_affiliate_dashboard(WP_REST_Request $request) {
		global $wpdb;
		// $html = do_shortcode('[afwc_dashboard]');
		$user_id = get_current_user_id();

		if ($user_id !== 0) {
			$user = get_userdata($user_id);
		} else {
			// User is not logged in
			echo "User is not logged in.";
			return;
		}

		// echo '<pre>';print_r($_REQUEST);echo '</pre>';
		$afwc_my_account_1 = AFWC_My_Account::get_instance();

		$tabText = $request['tabText'];
		$key = $request['key'];

		$sql_query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM wvnxl_afwc_campaigns WHERE status = 'Active'"
			)
		);

		if ($sql_query) {
			foreach ($sql_query as $row) {
				$data_array[] = array(
					'id' => $row->id,
					'title' => $row->title,
					'slug' => $row->slug,
					'target_link' => $row->target_link,
					'short_description' => $row->short_description,
					'body' => $row->body,
					'status' => $row->status,
					'meta_data' => $row->meta_data,
					'rules' => $row->rules,
					'user_id' => get_current_user_id(),
				);
			}
		} else {
			// Handle query error
			echo "Error: " . $wpdb->last_error;
		}

		ob_start();	
		if ( $tabText == 'reports' && $key == '1' ) {
			$afwc_my_account_1->dashboard_content($user);
		} elseif ( $tabText == 'resources' && $key == '2' ) {
			$afwc_my_account_1->profile_resources_content($user);
		} elseif ( $tabText == 'campaigns' && $key == '3' ) {
			$afwc_my_account_1->campaigns_content();
		}

		$api_response = ob_get_clean();

		$response_data = array(
			'html' => $api_response,
			'afwc_contact_admin_email_address' 	=> get_option( 'afwc_contact_admin_email_address', '' ),
			'afwc_paypal_email'      			=> get_user_meta( $user_id, 'afwc_paypal_email', true ),
			'campaigns_data' 					=> $data_array, // Include the database query result in the response
			'campaigns_id'						=> $user_id,
		);

		return $this->return_success($response_data);
	}

	public function zorem_affiliate_registration_form(WP_REST_Request $request) {

		$html = do_shortcode('[afwc_registration_form]');

		return $this->return_success($html); // Assuming you want to return the post details
	}

	/**
	 * Account Settings - billing
	 * Change subscription status
	 */
	public function change_user_subscription($request) {
		
		$subscription_id = isset( $request['subscription_id'] ) ? sanitize_text_field( $request['subscription_id'] ) : false;
		$change_status   = isset( $request['change_subscription_to'] ) ? sanitize_text_field( $request['change_subscription_to'] ) : false;
		$wpnonce         = isset( $request['change_wpnonce'] ) ? sanitize_text_field( $request['change_wpnonce'] ) : false;

		$response = $this->maybe_change_users_subscription( $subscription_id, 'cancelled', $wpnonce );

		return $response;
	}

	/**
	 * Checks if the current request is by a user to change the status of their subscription, and if it is,
	 * validate the request and proceed to change to the subscription.
	 *
	 * @since 2.0
	 */
	public static function maybe_change_users_subscription( $subscription_id, $change_subscription_to, $wpnonce ) {
		if ( isset( $change_subscription_to, $subscription_id, $wpnonce ) && ! empty( $wpnonce ) ) {
		   	$user_id      = get_current_user_id();
			$subscription = wcs_get_subscription( absint( $subscription_id ) );
			$new_status   = wc_clean( $change_subscription_to );

			$validate = self::validate_subscription_request( $user_id, $subscription, $new_status, $wpnonce );
			if ( $validate['success'] == true ) {
				return self::change_users_subscription( $subscription, $new_status );
			} else {
				return $validate;
			}
		} else {
			$r = array(
				'success' => false,
				'message' => 'Invalid Parameters'
			);
			return $r;
		}
	}

	/**
	 * Change the status of a subscription and show a notice to the user if there was an issue.
	 *
	 * @since 2.0
	 */
	public static function change_users_subscription( $subscription, $new_status ) {
		$subscription = ( ! is_object( $subscription ) ) ? wcs_get_subscription( $subscription ) : $subscription;
		
		do_action( 'woocommerce_before_customer_changed_subscription_to_' . $new_status, $subscription );
		
		$success = false;

		switch ( $new_status ) {
			case 'active':
				if ( ! $subscription->needs_payment() ) {
					$subscription->update_status( $new_status );
					$subscription->add_order_note( _x( 'Subscription reactivated by the subscriber from their account page.', 'order note left on subscription after user action', 'woocommerce-subscriptions' ) );
					
					$success = true;
					$message = 'Your subscription has been reactivated.';
				} else {
					$message = 'You can not reactivate that subscription until paying to renew it. Please contact us if you need assistance.';
				}
				break;
			case 'on-hold':
				if ( wcs_can_user_put_subscription_on_hold( $subscription ) ) {
					$subscription->update_status( $new_status );
					$subscription->add_order_note( _x( 'Subscription put on hold by the subscriber from their account page.', 'order note left on subscription after user action', 'woocommerce-subscriptions' ) );

					$success = true;
					$message = 'Your subscription has been put on hold.';
				} else {
					$message = 'You can not suspend that subscription - the suspension limit has been reached. Please contact us if you need assistance.';
				}
				break;
			case 'cancelled':
				$subscription->cancel_order();
				$subscription->add_order_note( _x( 'Subscription cancelled by the subscriber from their account page.', 'order note left on subscription after user action', 'woocommerce-subscriptions' ) );
				
				$success = true;
				$message = 'Your subscription has been cancelled.';
				break;
		}

		if ( $success ) {
			do_action( 'woocommerce_customer_changed_subscription_to_' . $new_status, $subscription );
		}

		$r['success'] = $success;
		$r['message'] = $message ? $message : '';

		return $r;
	}

	/**
	 * Validates a user change status change request.
	 *
	 * @since 2.0.0
	 *
	 * @param int             $user_id The ID of the user performing the request.
	 * @param WC_Subscription $subscription The Subscription to update.
	 * @param string          $new_status The new subscription status to validate.
	 * @param string|null     $wpnonce Optional. The nonce to validate the request or null if there's no nonce to validate.
	 *
	 * @return bool Whether the status change request is valid.
	 */
	public static function validate_subscription_request( $user_id, $subscription, $new_status, $wpnonce = null ) {
		$subscription = ( ! is_object( $subscription ) ) ? wcs_get_subscription( $subscription ) : $subscription;

		if ( ! wcs_is_subscription( $subscription ) ) {
			$success = false;
			$message = 'That subscription does not exist. Please contact us if you need assistance.';
		} elseif ( isset( $wpnonce ) && wp_verify_nonce( $wpnonce, $subscription->get_id() . $subscription->get_status() ) === false ) {
			$success = false;
			$message = 'Security error. Please contact us if you need assistance.';
		} elseif ( ! user_can( $user_id, 'edit_shop_subscription_status', $subscription->get_id() ) ) {
			$success = false;
			$message = 'That doesn\'t appear to be one of your subscriptions.';
		} elseif ( ! $subscription->can_be_updated_to( $new_status ) ) {
			// translators: placeholder is subscription's new status, translated
			$success = false;
			$message = sprintf( 'That subscription can not be changed to %s. Please contact us if you need assistance.', wcs_get_subscription_status_name( $new_status ) );
		} else {
			$success = true;
		}

		$r['success'] = $success;
		$r['message'] = isset($message) ? $message : '';

		return $r;
	}

	function account_endpoint_validate($request) {
		$user_id = get_current_user_id();
		if ( $user_id !== 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get user information
	 */
	public function get_user_account_information($request)
	{
		$user_id = get_current_user_id();
		// $user_id = $request['user_id'];
		// print_r($user);

		if ($user_id !== 0) {
			$user = get_userdata($user_id);
		} else {
			// User is not logged in
			echo "User is not logged in.";
			return;
		}

		$data = [
			'email_verified' => get_user_meta($user_id, 'customer_email_verified', true),
			'avatar' => get_avatar_url($user_id),
			'name' => $user->display_name,
			'email' => $user->user_email,
			'user_id' => $user_id,
		];

		return $this->return_success($data);
	}

	public function single_view_orders($request)
	{

		$order = wc_get_order($request['order_id']);
		$user_id = get_current_user_id();
		// if ( $user_id != $request['user_id'] ) {
		// 	$return = array(
		// 		'message'   => 'order is not found.',
		// 	);
		// 	return $this->return_error( $return );
		// }

		if ( !get_current_user_id() ) {
			$return = array(
				'message'   => 'user id is not found.',
			);
			return $this->return_error( $return );
		}

		if ( empty($order) ) {
			$return = array(
				'message'   => 'order not found.',
			);
			return $this->return_error( $return );
		}
		$order_items = $order->get_items('coupon');
		$coupon_item = reset($order_items);

		if ($coupon_item instanceof WC_Order_Item_Coupon) {
			$discount = $coupon_item->get_discount();
		} else {
			$discount = '';
		}

		$coupons = $order->get_items('coupon');

		if (!empty($coupons)) {
			// Assuming there is only one coupon used in the order.
			// If there can be multiple coupons, you may need to loop through them accordingly.
			$coupon = reset($coupons);

			// Get the coupon code from the coupon item data.
			$coupon_code = $coupon->get_code();
		} else {
			$coupon_code = '';
		}
		$invoice_url = '';
		$green_invoice = get_post_meta($order->get_id(), '_green_invoice', true);
		if ($green_invoice) {
			foreach ($green_invoice as $invoice) {
				$invoice_url = $invoice['url'][$invoice['lang']];
			}
		}

		// Assuming you have the $order object.
		if ($order instanceof WC_Order) {

			// Get the VAT number meta data
			$vat_number = $order->get_meta('_vat_number');

			// Get the billing details from the order.
			$billing_details = array(
				'postcode' => $order->get_billing_postcode(),
				'country' => $order->get_billing_country(),
				'first_name' => $order->get_billing_first_name(),
				'last_name' => $order->get_billing_last_name(),
				'email' => $order->get_billing_email(),
				'company' => $order->get_billing_company(),
				'vat_number' => $vat_number
			);
		} else {
			$billing_details = '';
		}

		$order_date = $order->get_date_created()->format('Y-m-d H:i:s');
		$order_status = $order->get_status();
		$order_total = $order->get_total();

		foreach ($order->get_items() as $item_id => $item) {
			$product = $item->get_product();
			$product_name = $product ? $product->get_name() : $item->get_name();
			$quantity = $item->get_quantity();
			$item_total = $item->get_total();
			// $item_sub_total = $item->get_subtotal();
		}
		$get_order_currency = get_woocommerce_currency_symbol($order->get_currency());
		$decoded_currency_symbol = html_entity_decode($get_order_currency);

		$order_subtotal = $order->get_subtotal();

		if ( is_plugin_active( 'refund-for-woocommerce/refund-for-woocommerce.php' ) ) {
			$is_order_valid_for_refund = Refund_For_WooCommerce()->front->is_order_valid_for_refund($order);
		}

		$order_entry = array(
			'id' => $request['order_id'],
			'date' => $order_date,
			'status' => $order_status,
			'total' => $order_total,
			'name' => $product_name,
			'quantity' => $quantity,
			'sub_total' => $order_subtotal,
			'discount' => $discount,
			'coupon_code' => $coupon_code,
			'billing_details' => $billing_details,
			'invoice_url' => $invoice_url,
			'get_order_currency' => $decoded_currency_symbol,
			'is_order_valid_for_refund' => isset( $is_order_valid_for_refund ) ? $is_order_valid_for_refund : '',
		);

		return $this->return_success($order_entry);
	}

	public function get_auto_renew($request)
	{
		// $user_id = $request['user_id'];
		// $user_id = 195;
		$user_id = get_current_user_id();
		$subscription = wcs_get_subscription(absint($request['sid']));
	
		if (!$subscription || is_wp_error($subscription)) {
			// Handle the case where the subscription is not found or an error occurred.
			// You might want to log the error or provide an appropriate response.
			$json['success'] = false;
			$json['error'] = 'Subscription not found or an error occurred.';
			return $this->return_success($json);
		}

		$subscription_id = isset( $request['sid'] ) ? $request['sid'] : '';
		$sub_item_id = isset( $request['sub_item_id'] ) ? $request['sub_item_id'] : '';

		$items = $subscription->get_items();

		$new_status = wc_clean($request['action']);
		$checked = wc_clean($request['checked']);
		if ($checked == '' && $new_status == 'active') {
			$new_status = 'active';
		} else if ($checked == 'checked' && $new_status == 'active') {
			$new_status = 'cancelled';
		} else if ($checked == '' && $new_status == 'cancelled') {
			$new_status = 'active';
		}

		$wpnonce = $request['wpnonce'];
		$sid = sanitize_text_field($request['sid']);

		check_ajax_referer('wc_subscription_auto_renew_update_' . $sid, 'wpnonce');

		$json['success'] = false;

		if (WCS_User_Change_Status_Handler::validate_request($user_id, $subscription, $new_status)) {

			if ('active' == $subscription->get_status() && 'cancelled' == $new_status) {
				$this->change_users_subscription($subscription, $new_status);
				$json['success'] = true;
			}

			if ('pending-cancel' == $subscription->get_status() && 'active' == $new_status) {
				$this->change_users_subscription($subscription, $new_status);
				$json['success'] = true;
			}
		}

		$upgrade_downgrade_link = ''; // Initialize the variable outside the loop

		foreach ($items as $item) {
			$resource_subscription = wcs_get_subscription($subscription_id);
			if ($resource_subscription) {
				$upgrade_downgrade_link = print_subscription_switch_link($sub_item_id, $item, $subscription);
			}
		}
		
		$actions = wcs_get_all_user_actions_for_subscription($subscription, $user_id);

		if ( $subscription ) {
			// change payment method
			if ( $subscription->can_be_updated_to( 'new-payment-method' ) ) {

				if ( $subscription->has_payment_gateway() && wc_get_payment_gateway_by_order( $subscription )->supports( 'subscriptions' ) ) {
					$action_name = _x( 'Change payment', 'label on button, imperative', 'woocommerce-subscriptions' );
				} else {
					$action_name = _x( 'Add payment', 'label on button, imperative', 'woocommerce-subscriptions' );
				}
				
				$change_payment_url = wp_nonce_url( add_query_arg( array( 'change_payment_method' => $subscription_id ), $subscription->get_checkout_payment_url() ) );
				$change_payment_url = str_replace( 'amp;', '', $change_payment_url );
			}
		}

		if (isset($actions['cancel']) || isset($actions['reactivate'])) {
			if (isset($actions['cancel'])) {
				$checked = 'checked';
				$action = 'cancelled';
			}
			if (isset($actions['reactivate'])) {
				$checked = '';
				$action = 'active';
			}
		}
	
		$change_subscription_to = $subscription->get_status();
		$change_subscription_nonce = wp_create_nonce($subscription_id . $change_subscription_to);

		if ($subscription)
			$json['status'] = $subscription->get_status();
			$json['upgrade_downgrade_link'] = $upgrade_downgrade_link;
			$json['change_payment_url'] = isset( $change_payment_url ) ? $change_payment_url : '';
			$json['action_name'] = isset( $action_name ) ? $action_name : '';
			$json['checked'] = $checked;
			$json['action'] = $action;
			$json['change_subscription_to'] = $change_subscription_to;
			$json['change_subscription_nonce'] = $change_subscription_nonce;


		return $this->return_success($json);
	}
	
	public function account_orders( $request ) {

		$user_id = get_current_user_id();
		$order_total_count = wc_get_customer_order_count($user_id);
		$paged = $request['paged'] ? $request['paged'] : 1;
		$length = $request['length'] ? $request['length'] : 10;

		$order_sorting = $request['order_sorting'] ? $request['order_sorting'] : 'DESC';
		$sorting_col = $request['sorting_col'] ? $request['sorting_col'] : 'order';

		$args = array(
			'type' => 'shop_order',
			'paged' => $paged,
			'posts_per_page' => $length,
			'paginate' => true,
			'customer_id' => $user_id,
			'order' => $order_sorting,
			'orderby' => $sorting_col,
		);
		
		$results = wc_get_orders($args);
		$order_details = array();
		$order_entry = array();
		foreach ($results->orders as $order) {
			$invoice_url = '';
			$green_invoice = get_post_meta($order->get_id(), '_green_invoice', true);
			if ($green_invoice) {
				foreach ($green_invoice as $invoice) {
					$invoice_url = $invoice['url'][$invoice['lang']];
				}
			}

			$order_id = $order->get_id();
			// Get order details
			$order_date = $order->get_date_created()->format('Y-m-d H:i:s');
			$order_status_for_status = wc_get_order_status_name( $order->get_status() );
			$order_status = $order->get_status();
			
			$order_total = $order->get_total();
			$total_refunded = $order->get_total_refunded();
			$get_order_currency = get_woocommerce_currency_symbol($order->get_currency());
			$decoded_currency_symbol = html_entity_decode($get_order_currency);
			$item_count = $order->get_item_count() - $order->get_item_count_refunded();
			// Get order items
			$order_items = array();
			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();
				$product_name = $product ? $product->get_name() : $item->get_name();
				$quantity = $item->get_quantity();

				$item_total = $item->get_total();

				$order_items[] = array(
					'Name' => $product_name,
					// 'Quantity' => $quantity,
					'Total' => $item_total,
				);
			}

			if ( is_plugin_active( 'refund-for-woocommerce/refund-for-woocommerce.php' ) ) {
				$eligible_status = Refund_For_WooCommerce()->admin->refund_settings('refund_order_statuses', array('wc-processing', 'wc-completed '));
				$is_order_valid_for_refund = Refund_For_WooCommerce()->front->is_order_valid_for_refund($order);
				$check_status = in_array('wc-' . $order_status, $eligible_status);
				// echo '<pre>';print_r($eligible_status);echo '</pre>';
				if ($check_status) {
					$eligible_status_data = 'yes';
				} else {
					$eligible_status_data = 'no';
				}
			}


			$order_entry[] = array(
				'ID' => $order_id,
				'Date' => $order_date,
				'Status' => $order_status_for_status,
				'Total' => $order_total,
				'total_refunded' => $total_refunded,
				'get_order_currency' => $decoded_currency_symbol,
				'Quantity' => $item_count,
				'invoice_url' => $invoice_url,
				'Items' => $order_items,
				'eligible_status' => isset( $eligible_status_data ) ? $eligible_status_data : '',
				'is_order_valid_for_refund' => isset( $is_order_valid_for_refund ) ? $is_order_valid_for_refund : '',
				'user_id' => $user_id
			);
			// Add order entry to the order details array
		}
		$order_details['order_data'] = $order_entry;
		$order_details['total_order'] = $order_total_count;
		return $this->return_success($order_details);
	}

	public function connected_stores_list($request)
	{
		$user_id = get_current_user_id();
		// $user_id = $request['user_id'];
		$product_id = $request['product_id'];		
		$master_api_key = WC_AM_USER()->get_master_api_key($user_id);

		$activation_data = WC_AM_API_ACTIVATION_DATA_STORE()->get_total_activations_resources_for_api_key_by_product_id($master_api_key, $product_id);
		//echo '<pre>';print_r($activation_data);echo '</pre>';exit;

		$result = array();
		$item = array();
		if ($activation_data) {
			foreach ($activation_data as $data) {
				$nonce = wp_create_nonce('wc_delete_activation_' . $data->instance);
				$item = array(
					'store_url' => $data->object,
					'instance' => $data->instance,
					'order_id' => $data->order_id,
					'api_key' => $data->api_key,
					'product_id' => $data->product_id,
					'nonce' => $nonce
				);
				$result[] = $item;
			}
		} else {
			$item = array(
				'msg' => "This subscription isn't being used on any WooCommerce site yet.",
			);
			$result = $item;
		}

		return $this->return_success($result);
	}

	public function connnected_store_deactivate($request)
	{
		$api = WC_AM_API_Activation_Data_Store::instance();
		$result = $api->delete_api_key_activation_by_instance_id(wc_clean($_POST['instance']));

		/**
		 * Refresh cache.
		 *
		 * @since 2.2.0
		 */
		WC_AM_SMART_CACHE()->delete_cache(wc_clean(
			array(
				'admin_resources' => array(
					'instance' => $_POST['instance'],
					'order_id' => $_POST['order_id'],
					'sub_parent_id' => $_POST['sub_parent_id'],
					'api_key' => $_POST['api_key'],
					'product_id' => $_POST['product_id'],
					'user_id' => get_current_user_id()
				)
			)), true);

		$json['message'] = 'Success';

		return $this->return_success($error_msg);
	}

	public function account_subscription_order_list($request)
	{
		global $wpdb; // Add this line to access the global $wpdb object
		$user_id = get_current_user_id();	
		$table_name = $wpdb->prefix . 'wc_am_api_resource';
		
		// $last = $wpdb->last_query;
		$subscription_order_data = array();
		// $status = array();
		
		$subscriptions = wcs_get_users_subscriptions( $user_id );
		// print_r($subscriptions);exit;
		
		$data = array();
		$product_data = array();
		$sum = 0; 
		foreach ($subscriptions as $subscription) {
			
			$subscription_id = $subscription->get_id();
			$order_id = $subscription->get_parent_id();
			$items = $subscription->get_items();
			
			$product_names = [];
			foreach ($items as $item) {

				$product = $item->get_product(); // Get the product object				
				$product_id = $item->get_product_id();
				$image_size = array( 64, 64, 'single-post-thumbnail' );
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), $image_size );
				$item_id = $item->get_id();
				$actions = wcs_get_all_user_actions_for_subscription($subscription, $user_id);
				

				if ( $subscription ) {
					// change payment method
					if ( $subscription->can_be_updated_to( 'new-payment-method' ) ) {
	
						if ( $subscription->has_payment_gateway() && wc_get_payment_gateway_by_order( $subscription )->supports( 'subscriptions' ) ) {
							$action_name = _x( 'Change payment', 'label on button, imperative', 'woocommerce-subscriptions' );
						} else {
							$action_name = _x( 'Add payment', 'label on button, imperative', 'woocommerce-subscriptions' );
						}
						
						$change_payment_url = wp_nonce_url( add_query_arg( array( 'change_payment_method' => $subscription_id ), $subscription->get_checkout_payment_url() ) );
						$change_payment_url = str_replace( 'amp;', '', $change_payment_url );
					}
				}

				if (isset($actions['cancel']) || isset($actions['reactivate'])) {
					if (isset($actions['cancel'])) {
						$checked = 'checked';
						$action = 'cancelled';
					}
					if (isset($actions['reactivate'])) {
						$checked = '';
						$action = 'active';
					}
				}

				if ($subscription->get_date('next_payment')) {
					$next_renewal = date("M j, Y", strtotime($subscription->get_date('next_payment')));
				} else {
					$next_renewal = date("M j, Y", strtotime($subscription->get_date('end')));
				}
				
				
				$currency_symbol = get_woocommerce_currency_symbol($subscription->get_currency());
				$decoded_currency_symbol = html_entity_decode($currency_symbol);
				
				$remote_url = WC_AM_URL()->is_download_external_url( $item->get_product_id() );
				$secure_order_download_url_final = $remote_url ? WC_AM_ORDER_DATA_STORE()->get_secure_order_download_url($user_id, $subscription_id, $item->get_variation_id() , $remote_url) : WC_AM_ORDER_DATA_STORE()->get_secure_order_download_url($user_id, $subscription_id, $item->get_variation_id());
				
				$resources = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM $table_name WHERE user_id = %d AND sub_id = %d AND variation_id = %d",
						$user_id,
						$subscription_id,
						$item->get_variation_id(),						
						)
					);

				if ($resources !== null) {
					$sub_item_id = $resources->sub_item_id;
				} else {
					$sub_item_id = null;
				}
					
				$resource_subscription = wcs_get_subscription($subscription_id);
				if ($resource_subscription) {
					$upgrade_downgrade_link = print_subscription_switch_link($sub_item_id, $item, $subscription);
				} else {
					$upgrade_downgrade_link = '';
				}

				$subscription_status = esc_html(wcs_get_subscription_status_name($subscription->get_status()));


				$change_subscription_to = $subscription->get_status();
				
				$change_subscription_nonce = wp_create_nonce($subscription_id . $change_subscription_to);
				$product_name = explode(" - ", $item->get_name());
				$product_data[] = array(
					'order_id' => $order_id,
					'subscription_id' => $subscription_id,
					'product_name' => $product_name[0],
					'sid' => $subscription_id,
					'product_id' => $item->get_product_id(),
					'variation_id' => $item->get_variation_id(),
					'quantity' => $item->get_quantity(),
					'tax_class' => $item->get_tax_class(),
					'subtotal' => $item->get_subtotal(),
					'subtotal_tax' => $item->get_subtotal_tax(),
					'total' => $item->get_total(),
					'total_tax' => $item->get_total_tax(),
					'status' => $subscription->get_status(),
					'subscription_status' => $subscription_status,
					'action_name' => $action_name,
					'change_payment_url' => $change_payment_url,
					'checked' => $checked,
					'action' => $action,
					'next_renewal' => $next_renewal,
					'secure_order_download_url_final' => $secure_order_download_url_final,
					'product_url' => get_permalink( $item->get_product_id() ),
					'license' => isset( $resources->activations_purchased_total ) ? $resources->activations_purchased_total : '',
					'active' => isset( $resources->activations_total ) ? $resources->activations_total : '',
					'user_id' => $user_id,
					'get_order_currency' => $decoded_currency_symbol,
					'wpnonce' => wp_create_nonce('wc_subscription_auto_renew_update_' . $subscription_id),
					'change_subscription_to' => $change_subscription_to,
					'change_subscription_nonce' => $change_subscription_nonce,
					'upgrade_downgrade_link' => $upgrade_downgrade_link,
					'sub_item_id' => $sub_item_id ? $sub_item_id : '',
					'product_image_url' => $image[0]
				);
				$sum++;		
			}			
		}
		
		$subscription_order_data['subscription_data'] = $product_data;
		$subscription_order_data['subscription_order_count'] = (int)$sum;

		return $this->return_success($subscription_order_data);

	}

	// account details
	/**
	 * Account Settings
	 * Get all account settings data
	 */
	public function get_account_settings()
	{

		// $user_id = get_current_user_id(); // Get the current user's ID
		// $user_id = 2476;
		$user_id = get_current_user_id();
		
		if ($user_id !== 0) {
			$user = get_userdata($user_id);
		} else {
			// User is not logged in
			echo "User is not logged in.";
			return;
		}
		
		$user = wp_get_current_user();
		$mailchimp_user_subscription_status = ($user && $user->ID) ? get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true) : false;

		if ($mailchimp_user_subscription_status !== false && $mailchimp_user_subscription_status !== 'archived') {
			if ( '1' === $mailchimp_user_subscription_status ) {
				$mailchimp_data = '1';
			} else {
				$mailchimp_data = '';
			}
		}

		$meta = (object) get_user_meta($user_id);

		$user_info = [
			'account_email' => isset($user->user_email) ? $user->user_email : '',
			'account_first_name' => isset($meta->first_name[0]) ? $meta->first_name[0] : '',
			'account_last_name' => isset($meta->last_name[0]) ? $meta->last_name[0] : '',
			'account_billing_company' => isset($meta->billing_company[0]) ? $meta->billing_company[0] : '',
			'account_vat_number' => isset($meta->vat_number[0]) ? $meta->vat_number[0] : '',
			'account_display_name' => isset($user->display_name) ? $user->display_name : '',
			'account_billing_country' => isset($meta->billing_country[0]) ? $meta->billing_country[0] : '',
			'user_api_key' => isset($meta->user_api_key[0]) ? $meta->user_api_key[0] : '',
			'mailchimp_data' => $mailchimp_data,
			'save_account_details_nonce' => wp_create_nonce('save_account_details'),
		];

		return $this->return_success($user_info);

	}

	// password change

	/**
	 * Account Settings
	 * Update user account password
	 */
	public function update_account_password()
	{

		if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
			return;
		}

		if (empty($_POST['action']) || 'save_account_password' !== $_POST['action']) {
			$error_msg[] = 'Invalid Action';
		}

		wc_nocache_headers();

		$nonce_value = wc_get_var($_REQUEST['save_account_details_nonce'], wc_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

		if (!wp_verify_nonce($nonce_value, 'save_account_details')) {
			$error_msg[] = 'Could not verify form token. please refresh and try again.';
		}

		$user_id = get_current_user_id();
		// $user_id = 2476;

		if ($user_id <= 0) {
			return;
		}

		$pass_cur = !empty($_POST['password_current']) ? $_POST['password_current'] : '';
		$pass1 = !empty($_POST['password_1']) ? $_POST['password_1'] : '';
		$pass2 = !empty($_POST['password_2']) ? $_POST['password_2'] : '';
		$save_pass = true;

		// Current user data.
		$current_user = get_user_by('id', $user_id);
		$current_first_name = $current_user->first_name;
		$current_last_name = $current_user->last_name;
		$current_email = $current_user->user_email;

		// New user data.
		$user = new stdClass();
		$user->ID = $user_id;
		$user->user_email = $current_email;

		$error_msg = array();

		if (!empty($pass_cur) && empty($pass1) && empty($pass2)) {
			$error_msg[] = __('Please fill out all password fields.', 'woocommerce');
		} elseif (!empty($pass1) && empty($pass_cur)) {
			$error_msg[] = __('Please enter your current password.', 'woocommerce');
		} elseif (!empty($pass1) && empty($pass2)) {
			$error_msg[] = __('Please re-enter your password.', 'woocommerce');
		} elseif ((!empty($pass1) || !empty($pass2)) && $pass1 !== $pass2) {
			$error_msg[] = __('New passwords do not match.', 'woocommerce');
		} elseif (!empty($pass1) && !wp_check_password($pass_cur, $current_user->user_pass, $current_user->ID)) {
			$error_msg[] = __('Your current password is incorrect.', 'woocommerce');
		}

		if (empty($pass_cur)) {
			$error_msg[] = __('Please fill out all password fields.', 'woocommerce');
		}

		if ($pass1 && count($error_msg) === 0) {
			$user->user_pass = $pass1;
		}

		// Allow plugins to return their own errors.
		$errors = new WP_Error();
		do_action_ref_array('woocommerce_save_account_details_errors', array(&$errors, &$user));

		if ($errors->get_error_messages()) {
			foreach ($errors->get_error_messages() as $error) {
				$error_msg[] = $error;
			}
		}

		if (count($error_msg) === 0) {

			wp_update_user($user);

			$success_msg[] = __('Account password changed successfully.', 'woocommerce');

			do_action('woocommerce_save_account_details', $user->ID);

			return $this->return_success($success_msg);
		}

		wp_send_json_error($error_msg);
	}

	/**
	 * Account Settings
	 * Update user account data
	 */
	public function update_account_info($request)
	{

		if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'])) {
			return;
		}

		if (empty($_POST['action']) || 'save_account_info' !== $_POST['action']) {
			$error_msg[] = 'Invalid Action';
		}

		wc_nocache_headers();

		$nonce_value = wc_get_var($_REQUEST['save_account_details_nonce'], wc_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

		if (!wp_verify_nonce($nonce_value, 'save_account_details')) {
			$error_msg[] = 'Could not verify form token. please refresh and try again.';
		}

		$user_id = get_current_user_id();
		// $user_id = 2476;

		if ($user_id <= 0) {
			return;
		}

		$account_first_name = !empty($_POST['account_first_name']) ? wc_clean($_POST['account_first_name']) : '';
		$account_last_name = !empty($_POST['account_last_name']) ? wc_clean($_POST['account_last_name']) : '';
		$account_billing_company = !empty($_POST['account_billing_company']) ? wc_clean($_POST['account_billing_company']) : '';
		$vat_number = !empty($_POST['vat_number']) ? wc_clean($_POST['vat_number']) : '';
		$vat_number = !empty($_POST['account_vat_number']) ? wc_clean($_POST['account_vat_number']) : $vat_number; // temporary
		$account_email = !empty($_POST['account_email']) ? wc_clean($_POST['account_email']) : '';
		$account_display_name = !empty($_POST['account_display_name']) ? wc_clean($_POST['account_display_name']) : '';
		$mailchimp_data = !empty($request['mailchimp_data']) ? wc_clean($request['mailchimp_data']) : false;
	
		if( 'true' == $mailchimp_data ) {
			$mailchimp_data = '1';
		} else {
			$mailchimp_data = '';
		}

		// Current user data.
		$current_user = get_user_by('id', $user_id);
		$current_first_name = $current_user->first_name;
		$current_last_name = $current_user->last_name;
		$current_email = $current_user->user_email;

		// New user data.
		$user = new stdClass();
		$user->ID = $user_id;
		$user->first_name = $account_first_name;
		$user->last_name = $account_last_name;
		$user->billing_company = $account_billing_company;
		$user->display_name = $account_display_name;

		$error_msg = array();
		// Prevent display name to be changed to email.
		if (is_email($account_billing_company)) {
			$error_msg[] = __('Company name cannot be changed to email address due to privacy concern.', 'woocommerce');
		}

		// Handle required fields.
		$required_fields = apply_filters('woocommerce_save_account_details_required_fields', array(
			'account_first_name' => __('First name', 'woocommerce'),
			'account_last_name' => __('Last name', 'woocommerce'),
			'account_billing_company' => __('Company name', 'woocommerce'),
			'account_email' => __('Email address', 'woocommerce'),
			'account_display_name' => __('Display Name', 'woocommerce'),
		));

		foreach ($required_fields as $field_key => $field_name) {
			if (empty($_POST[$field_key])) {
				$error_msg[] = sprintf(__('%s is a required field.', 'woocommerce'), esc_html($field_name));
			}
		}

		if ($account_email) {
			$account_email = sanitize_email($account_email);
			if (!is_email($account_email)) {
				$error_msg[] = __('Please provide a valid email address.', 'woocommerce');
			} elseif (email_exists($account_email) && $account_email !== $current_user->user_email) {
				$error_msg[] = __('This email address is already registered.', 'woocommerce');
			}
			$user->user_email = $account_email;
		}

		// Allow plugins to return their own errors.
		$errors = new WP_Error();
		do_action_ref_array('woocommerce_save_account_details_errors', array(&$errors, &$user));

		if ($errors->get_error_messages()) {
			foreach ($errors->get_error_messages() as $error) {
				$error_msg[] = $error;
			}
		}

		if (count($error_msg) === 0) {
			wp_update_user($user);

			// Update customer object to keep data in sync.
			$customer = new WC_Customer($user->ID);

			if ($customer) {
				// Keep billing data in sync if data changed.
				if (is_email($user->user_email) && $current_email !== $user->user_email) {
					$customer->set_billing_email($user->user_email);
				}

				if ($current_first_name !== $user->first_name) {
					$customer->set_billing_first_name($user->first_name);
				}

				if ($current_last_name !== $user->last_name) {
					$customer->set_billing_last_name($user->last_name);
				}

				$customer->set_billing_company($user->billing_company);
				$customer->set_display_name( $user->display_name );
				update_user_meta($user->ID, 'vat_number', $vat_number);

				$customer->save();
			}

			$success_msg[] = __('Account details changed successfully.', 'woocommerce');

			// do_action('woocommerce_save_account_details', $user->ID);

			update_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', $mailchimp_data);

			return $this->return_success($success_msg);

		}
		wp_send_json_error($error_msg);
	}

	/**
	 * Success response
	 */
	public function return_success($data = null)
	{
		$response = array('success' => true);

		if (isset($data)) {
			$response['data'] = $data;
		}
		return new WP_REST_Response($response, 200);
		// return $response;
	}

	/**
	 * Error response
	 */
	public function return_error($data = null)
	{
		$response = array('success' => false);

		if (isset($data)) {
			$response['data'] = $data;
		}
		return new WP_REST_Response($response, 200);
		// return $response;
	}

}
