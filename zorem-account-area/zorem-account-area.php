<?php
/**
 * Plugin Name: Zorem Account Area
 * Plugin URI: https://www.zorem.com/shop/
 * Description: Zorem Account Area endpoints.
 * Version: 1.0
 * Author: zorem
 * Author URI:  http://www.zorem.com/
 * License:     GPL-2.0+
 * License URI: http://www.zorem.com/
 * Text Domain: zorem-account-area
 * Domain Path: /lang/
 * WC requires at least: 5.0
 * WC tested up to: 6.3.1
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Zorem_Account_Area {
	
	/**
	 * Zorem_Account_Area
	 *
	 * @var string
	 */
	public $version = '1.0';
	public $zae_api;
	public $plugin_path;

	/**
	* Constructor.
	*/
	public function __construct() {
		// Check if Wocoomerce is activated
		if ( $this->is_wc_active() ) {
			$this->includes();
		}
	}
	
	/**
	 * Check if WooCommerce is active
	 *	 
	 * @since  1.0.0
	 * @return bool
	*/
	private function is_wc_active() {
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}		

		// Do the WC active check
		if ( false === $is_active ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
		}		
		return $is_active;
	}
	
	/**
	 * Display WC active notice
	 *
	 * @since  1.0.0
	*/
	public function notice_activate_wc() {
		?>
		<div class="error">
			<p>
				<?php 
				/* translators: %s: search WooCommerce plugin link */
				printf( esc_html__( 'Please install and activate %1$sWooCommerce%2$s for Zorem Account Area!', 'zorem-account-area' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) ) . '">', '</a>' ); 
				?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Include plugin file.
	 *
	 * @since 1.0.0
	 *
	 */	
	public function includes() {

		
		//load css/javascript in frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css_js' ) );

		require_once $this->get_plugin_path() . '/includes/api/zorem-account-rest-api-controller.php';
		$this->zae_api = Zorem_Account_REST_API_Controller::get_instance();

		// Load plugin textdomain
		add_action( 'plugins_loaded', array($this, 'load_textdomain') );
		add_action( 'afwc_registration_additional_field_updates', array($this, 'afwc_save_additional_fields'),10,3 );

		add_filter( 'afwc_registration_form_fields', array($this, 'afwc_registration_form_fun') );

		add_filter( 'afwc_registration_submitted_data', array($this, 'afwc_registration_submitted_data'), 10, 2 );
		
		
		// add_action( 'init', array($this, 'custom_data') );
	}
	
	// public function custom_data() {
	// 	$affiliate_registration = AFWC_Registration_Submissions::get_instance();
	// 	$affiliate_registration->hide_fields[] = 'afwc_regform_password';
	// 	$affiliate_registration->hide_fields[] = 'afwc_reg_full_name';
	// }

	public function afwc_registration_submitted_data( $user_fields, $array ) {
		// echo '<pre>';print_r($_POST); echo '</pre>';exit;
		$user_fields['afwc_billing_address'] = sanitize_text_field($_POST['afwc_billing_address']);
		$user_fields['afwc_social_media_profile'] = sanitize_text_field($_POST['afwc_social_media_profile']);
		$user_fields['afwc_promote_our_products'] = sanitize_textarea_field($_POST['afwc_promote_our_products']);
		return $user_fields;
	}

	public function afwc_save_additional_fields($user_id, $user_data, $array) {
		$fields_to_update = array(
			'afwc_billing_address' => 'afwc_billing_address',
			'afwc_social_media_profile' => 'afwc_social_media_profile',
			'afwc_promote_our_products' => 'afwc_promote_our_products',
		);
	
		foreach ($fields_to_update as $source_field => $meta_key) {
			if (isset($user_data[$source_field])) {
				update_user_meta($user_id, $meta_key, $user_data[$source_field]);
			}
		}
	}
	
	public function afwc_registration_form_fun($form_fields) {
		$afwc_reg_terms = $form_fields['afwc_reg_terms'];
		$afwc_reg_contact = $form_fields['afwc_reg_contact'];
		$afwc_reg_website = $form_fields['afwc_reg_website'];
		$afwc_reg_desc = $form_fields['afwc_reg_desc'];
		unset($form_fields['afwc_reg_terms']);
		unset($form_fields['afwc_reg_contact']);
		unset($form_fields['afwc_reg_website']);
		unset($form_fields['afwc_reg_desc']);

		$form_fields['afwc_reg_contact'] = $afwc_reg_contact;
		$form_fields['afwc_billing_address'] = array(
			'type'		=> 'textarea',
			'required'	=> 'required',
			'show'		=> true,
			'label'		=> __( 'Billing Address', 'affiliate-for-woocommerce' ),
		);
		$form_fields['afwc_reg_website'] = $afwc_reg_website;
		$form_fields['afwc_social_media_profile'] = array(
			'type'		=> 'text',
			'required'	=> 'required',
			'show'		=> true,
			'label'		=> __( 'Social Media Profile', 'affiliate-for-woocommerce' ),
		);
		$form_fields['afwc_reg_desc'] = $afwc_reg_desc;
		$form_fields['afwc_promote_our_products'] = array(
			'type'		=> 'textarea',
			'required'	=> 'required',
			'show'		=> true,
			'label'		=> __( 'How do you plan to promote our products?', 'affiliate-for-woocommerce' ),
		);
		$form_fields['afwc_reg_terms'] = $afwc_reg_terms;

		return $form_fields;
	}



	public function enqueue_css_js() {
		global $wp;
		wp_enqueue_script( 'zorem-account-react', get_bloginfo( 'stylesheet_directory' ) . '/assets/js/custom-script.js', array(), 1.0 );
		if ( is_account_page() ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_script( 'Zorem_Account_Area', Zorem_Account_Area()->plugin_dir_url() . '/assets/js/main.js', array(), $this->version, true );
			if ( is_plugin_active( 'affiliate-for-woocommerce/affiliate-for-woocommerce.php' ) ) {
				$user = wp_get_current_user();
				if ( is_object( $user ) && $user instanceof WP_User && ! empty( $user->ID ) ) {
					$is_affiliate = afwc_is_user_affiliate( $user );
					$afwc_is_registration_open = apply_filters( 'afwc_is_registration_open', get_option( 'afwc_show_registration_form_in_account', 'yes' ), array( 'source' => $this ) );
	
					if ( is_admin() ) {
						$afwc_dashboard_name = 'Affiliate';
					} else {
						if ( 'yes' === $is_affiliate ) {
							$afwc_dashboard_name = 'Affiliate';
						}
						if ( 'no' === $is_affiliate ) {
							$afwc_dashboard_name = 'Register as an affiliate';
						}
						if ( 'not_registered' === $is_affiliate && 'yes' === $afwc_is_registration_open ) {
							$afwc_dashboard_name = 'Register as an affiliate';
						}
						if ( 'pending' === $is_affiliate ) {
							$afwc_dashboard_name = 'Register as an affiliate';
						}
					}
				}

				// $account_menu_items = apply_filters('woocommerce_account_menu_items', array());
				// $afwc_dashboard_name = isset( $account_menu_items['afwc-dashboard'] ) ? $account_menu_items['afwc-dashboard'] : '';
				$plugin_data = Affiliate_For_WooCommerce::get_plugin_data();
				wp_enqueue_style( 'afwc-reg-form-style', AFWC_PLUGIN_URL . '/assets/css/afwc-reg-form.css', array(), $plugin_data['Version'] );
				wp_enqueue_script( 'afwc-reg-form-js', AFWC_PLUGIN_URL . '/assets/js/afwc-reg-form.js', array( 'jquery', 'wp-i18n' ), $plugin_data['Version'], true );
				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'afwc-reg-form-js', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
				}

				wp_localize_script( 'afwc-reg-form-js', 'afwcRegistrationFormParams', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

				if ( ! wp_script_is( 'afwc-profile-js' ) ) {
					wp_register_script( 'afwc-profile-js', AFWC_PLUGIN_URL . '/assets/js/my-account/affiliate-profile.js', array( 'jquery', 'wp-i18n', 'afwc-affiliate-link' ), $plugin_data['Version'], true );
					if ( function_exists( 'wp_set_script_translations' ) ) {
						wp_set_script_translations( 'afwc-profile-js', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
					}
				}
				wp_enqueue_script( 'afwc-profile-js' );
				$pname = get_option( 'afwc_pname', 'ref' );
				$pname = ( ! empty( $pname ) ) ? $pname : 'ref';
				$user_id                                = intval( get_current_user_id() );
				$affiliate                              = new AFWC_Affiliate( $user_id );
				
				$affiliate_identifier                   = is_callable( array( $affiliate, 'get_identifier' ) ) ? $affiliate->get_identifier() : '';
				$afwc_allow_custom_affiliate_identifier = get_option( 'afwc_allow_custom_affiliate_identifier', 'yes' );
				$afwc_use_pretty_referral_links         = get_option( 'afwc_use_pretty_referral_links', 'no' );

				$localize_params = array(
					'pName'                    => $pname,
					'homeURL'                  => esc_url( trailingslashit( home_url() ) ),
					'saveAccountDetailsURL'    => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_save_account_details' ) ),
					'saveAccountSecurity'      => wp_create_nonce( 'afwc-save-account-details' ),
					'isPrettyReferralEnabled'  => $afwc_use_pretty_referral_links,
					'savedAffiliateIdentifier' => $affiliate_identifier,
				);

				if ( 'yes' === $afwc_allow_custom_affiliate_identifier ) {
					$localize_params['identifierRegexPattern']                  = afwc_affiliate_identifier_regex_pattern();
					$localize_params['identifierPatternValidationErrorMessage'] = apply_filters( 'afwc_affiliate_identifier_regex_pattern_error_message', _x( 'Invalid identifier. It should be a combination of alphabets and numbers, but the number should not be in the first position.', 'referral identifier pattern validation error message', 'affiliate-for-woocommerce' ) );
					$localize_params['saveReferralURLIdentifier']               = esc_url_raw( WC_AJAX::get_endpoint( 'afwc_save_ref_url_identifier' ) );
					$localize_params['saveIdentifierSecurity']                  = wp_create_nonce( 'afwc-save-ref-url-identifier' );
				}

				wp_localize_script( 'afwc-profile-js', 'afwcProfileParams', $localize_params );

				wp_register_style( 'afwc-profile-css', AFWC_PLUGIN_URL . '/assets/css/my-account/affiliate-profile.css', array(), $plugin_data['Version'], 'all' );
				if ( ! wp_style_is( 'afwc-profile-css', 'enqueued' ) ) {
					wp_enqueue_style( 'afwc-profile-css' );
				}

				if ( ! wp_script_is( 'afwc-reports' ) ) {
					wp_register_script( 'afwc-reports', AFWC_PLUGIN_URL . '/assets/js/my-account/affiliate-reports.js', array( 'jquery', 'wp-i18n' ), $plugin_data['Version'], true );
					wp_enqueue_script('afwc-reports', AFWC_PLUGIN_URL . '/assets/js/my-account/affiliate-reports.js', array('jquery', 'wp-i18n'), $plugin_data['Version'], true);

					if ( function_exists( 'wp_set_script_translations' ) ) {
						wp_set_script_translations( 'afwc-reports', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
					}
				}

				$affiliate_id = afwc_get_affiliate_id_based_on_user_id( get_current_user_id() );

				wp_localize_script(
					'afwc-reports',
					'afwcDashboardParams',
					array(
						'products'    => array(
							'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_products' ) ),
							'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-products' ) ),
						),
						'referrals'   => array(
							'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_referrals' ) ),
							'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-referrals' ) ),
						),
						'payouts'     => array(
							'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_payouts' ) ),
							'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-payouts' ) ),
						),
						'loadAllData' => array(
							'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_reload_dashboard' ) ),
							'nonce'   => esc_js( wp_create_nonce( 'afwc-reload-dashboard' ) ),
						),
						'affiliateId' => $affiliate_id,
					)
				);

				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

				wp_enqueue_script( 'afwc-reports' );

				wp_register_style( 'afwc-admin-dashboard-font', AFWC_PLUGIN_URL . '/assets/fontawesome/css/all' . $suffix . '.css', array(), $plugin_data['Version'] );

				wp_enqueue_style( 'afwc-admin-dashboard-font' );
				wp_enqueue_style( 'afwc-my-account', AFWC_PLUGIN_URL . '/assets/css/afwc-my-account.css', array(), $plugin_data['Version'] );

				if ( ! wp_style_is( 'jquery-ui-style', 'registered' ) ) {
					wp_register_style( 'jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui' . $suffix . '.css', array(), WC()->version );
				}

				wp_enqueue_style( 'jquery-ui-style' );

				$affiliate_id = afwc_get_affiliate_id_based_on_user_id( get_current_user_id() );
				$afwc_tab_endpoint = apply_filters( 'afwc_dashboard_tab_endpoint', get_option( 'afwc_dashboard_tab_endpoint', 'afwc-tab' ) );
				if ( ( ! empty( $wp->query_vars ) && ! empty( $wp->query_vars[ $afwc_tab_endpoint ] ) ) && ( 'campaigns' === $wp->query_vars[ $afwc_tab_endpoint ] || 'multi-tier' === $wp->query_vars[ $afwc_tab_endpoint ] ) ) {
					$plugin_data = Affiliate_For_WooCommerce::get_plugin_data();
					// Dashboard scripts.
					wp_register_script( 'mithril', AFWC_PLUGIN_URL . '/assets/js/mithril/mithril.min.js', array(), $plugin_data['Version'], true );
					wp_register_script( 'afwc-frontend-styles', AFWC_PLUGIN_URL . '/assets/js/styles.js', array( 'mithril' ), $plugin_data['Version'], true );
					wp_register_script( 'afwc-frontend-dashboard', AFWC_PLUGIN_URL . '/assets/js/frontend.js', array( 'afwc-frontend-styles', 'wp-i18n' ), $plugin_data['Version'], true );
					if ( function_exists( 'wp_set_script_translations' ) ) {
						wp_set_script_translations( 'afwc-frontend-dashboard', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
					}
					if ( ! wp_script_is( 'afwc-frontend-dashboard' ) ) {
						wp_enqueue_script( 'afwc-frontend-dashboard' );
					}

					$affiliate_id = afwc_get_affiliate_id_based_on_user_id( get_current_user_id() );
					
					wp_localize_script(
						'afwc-frontend-dashboard',
						'afwcDashboardParams',
						array(
							'security'                => array(
								'campaign'  => array(
									'fetchData' => wp_create_nonce( 'afwc-fetch-campaign' ),
								),
								'dashboard' => array(
									'multiTierData' => wp_create_nonce( 'afwc-multi-tier-data' ),
								),
							),
							'currencySymbol'          => AFWC_CURRENCY,
							'pname'                   => $pname,
							'affiliate_id'            => $affiliate_id,
							'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
							'campaign_status'         => 'Active',
							'no_campaign_string'      => __( 'No Campaign yet', 'affiliate-for-woocommerce' ),
							'isPrettyReferralEnabled' => get_option( 'afwc_use_pretty_referral_links', 'no' ),
						)
					);

					wp_register_style( 'afwc_frontend', AFWC_PLUGIN_URL . '/assets/css/frontend.css', array(), $plugin_data['Version'] );
					if ( ! wp_style_is( 'afwc_frontend' ) ) {
						wp_enqueue_style( 'afwc_frontend' );
					}

					wp_register_style( 'afwc-common-tailwind', AFWC_PLUGIN_URL . '/assets/css/common.css', array(), $plugin_data['Version'] );

					if ( ! wp_style_is( 'afwc-common-tailwind' ) ) {
						wp_enqueue_style( 'afwc-common-tailwind' );
					}
				}
			}

			$user_logout = wc_logout_url();
			// wp_enqueue_script( 'Zorem_Account_Area', Zorem_Account_Area()->plugin_dir_url() . '/assets/js/main.js', array(), $this->version, true );
			$cev_action = add_query_arg( array(
				'action'    => 'checkout_page_send_verification_code',
				'nonce'     => wp_create_nonce()
			), admin_url( 'admin-ajax.php' ) );

			if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
				$woocommerce_subscriptions_active = 'active_WCsubscription';
			} else {
				$woocommerce_subscriptions_active = 'inactive_WCsubscription';
			}
	
			// Check if the "Affiliate for WooCommerce" plugin is active
			if ( is_plugin_active( 'affiliate-for-woocommerce/affiliate-for-woocommerce.php' ) ) {
				$affiliate_active = 'active_affiliate';
			} else {
				$affiliate_active = 'inactive_affiliate';
			}

			if ( is_plugin_active( 'refund-for-woocommerce/refund-for-woocommerce.php' ) ) {
				$refund_active = 'active_refund';
			} else {
				$refund_active = 'inactive_refund';	
			}

			$origin = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
			$origin .= $_SERVER['HTTP_HOST'];
			if ( $origin == 'https://staging.zorem.com' || $origin == 'https://www.zorem.com' ) {
				$origin = 'active_origin';
			} else {
				$origin = 'inactive_origin';
			}

			wp_localize_script('zorem-account-react', 'zoremAccountConfig', [
				'user_logout'			=> $user_logout,
				'nonce'					=> wp_create_nonce('wp_rest'),
				'rest_base_url'			=> esc_url_raw( rest_url() ),
				'user_name'				=> wp_get_current_user()->first_name,
				'afwc_dashboard_name'	=> isset( $afwc_dashboard_name ) ? $afwc_dashboard_name : '',
				'security'				=> wp_create_nonce( 'afwc-reload-dashboard' ),
				'saveAccountSecurity'	=> wp_create_nonce( 'afwc-save-account-details' ),
				'cev_action'			=> $cev_action,
				'affiliate_active'		=> $affiliate_active,
				'woocommerce_subscriptions_active' => $woocommerce_subscriptions_active,
				'refund_active' 		=> $refund_active,
				'origin'				=> $origin
			]);
		}

		// if ( is_page( 20982 ) ) {
		// 	// 97413, 97407
		// 	wp_enqueue_style( 'afwc-my-account');
		// } else {
		// 	wp_dequeue_style( 'afwc-my-account');
		// }

	}
	
	/**
	 * Gets the absolute plugin path without a trailing slash, e.g.
	 * /path/to/wp-content/plugins/plugin-directory.
	 *
	 * @return string plugin path
	 */
	public function get_plugin_path() {
		if ( isset ( $this->plugin_path ) ) {
			return $this->plugin_path;
		}
		
		$this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
		return $this->plugin_path;
	}
	
	public static function get_plugin_domain() {
		return __FILE__;
	}
	
	/*
	* plugin file directory function
	*/	
	public function plugin_dir_url() {
		return plugin_dir_url( __FILE__ );
	}
	
	/*
	* load text domain
	*/
	public function load_textdomain() {
		load_plugin_textdomain( 'zorem-account-area', false, plugin_dir_path( plugin_basename(__FILE__) ) . 'lang/' );
	}

}	
	
/**
 * Returns an instance of Zorem_Account_Area.
 *
 * @since 1.0
 * @version 1.0
 *
 * @return Zorem_Account_Area
*/
function Zorem_Account_Area() {
	static $instance;

	if ( ! isset ( $instance ) ) {		
		$instance = new Zorem_Account_Area();
	}

	return $instance;
}

/**
 * Register this class globally.
 *
 * Backward compatibility.
*/
Zorem_Account_Area();
