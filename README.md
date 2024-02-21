# WooCommerce Custom My Account Area with Affiliate Integration

This project extends WooCommerce by creating a custom My Account area using React.js, tailored to integrate seamlessly with the Affiliate for WooCommerce plugin. It addresses the limitation of the Affiliate for WooCommerce plugin not adding its page to a custom My Account area by providing a custom endpoint for affiliate functionality within our React-based My Account area.

## Features

- **Custom My Account Area**: Reimagines the WooCommerce My Account area using React.js for a more dynamic user experience.
- **Affiliate Program Integration**: Custom endpoint and integration for the Affiliate for WooCommerce plugin within the custom My Account area.
- **Plugin Extensibility**: Designed as a plugin to easily integrate with existing WooCommerce setups.

## Prerequisites

Before you begin, ensure you have met the following requirements:
- WordPress and WooCommerce installed on your web server
- WordPress plugin development
- Flatsome or Flatsome Child Theme installed and activated on your WordPress site

## Compatibility

This project is compatible with the following plugins and extensions:
- Affiliate for WooCommerce:
- WooCommerce Subscription and WooCommerce API Manager

## Development

1. zorem_affiliate_registration_form
   -This function handles the affiliate registration form endpoint. It generates the HTML for the affiliate registration form using the [afwc_registration_form] shortcode and returns it in the response.

public function zorem_affiliate_registration_form(WP_REST_Request $request) {
     $html = do_shortcode('[afwc_registration_form]');
     return $this->return_success($html); // Assuming you want to return the post details
}

2. zorem_affiliate_dashboard
   -This function handles the affiliate dashboard endpoint. It retrieves data related to active campaigns from the database and generates HTML content for the affiliate dashboard based on the provided parameters (tabText and key).
   The generated HTML content includes dashboard content such as reports, resources, and campaigns. Additionally, it retrieves the contact admin email address and PayPal email associated with the current user.

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

## Configuration

After activating the plugin, you'll need to configure it to work with your WooCommerce and Affiliate for WooCommerce settings. This section can include specific settings that need to be adjusted, such as setting up custom endpoints or configuring affiliate settings through the WordPress admin panel.

## Usage

Explain how users can navigate the custom My Account area, access the affiliate program features, and any additional functionality provided by your plugin. Include screenshots or GIFs to guide the users visually.
