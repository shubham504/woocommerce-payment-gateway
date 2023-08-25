<?php
/*
	Plugin Name: PowerCARD-eCommerce WooCommerce Payment Gateway
	Plugin URI: https://xyz.me
	Description: PowerCARD-eCommerce WooCommerce Payment Gateway allows you to accept local and International payment via Verve Card, MasterCard & Visa Card.
	Version: 2.2.0
	Author: Shubham Sharma
	Author URI: https://xyz.me
	License:           GPL-2.0+
 	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

add_action( 'plugins_loaded', 'tbz_wc_simplepay_init', 0 );

function tbz_wc_simplepay_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
 	 * Gateway class
 	 */
	class WC_Tbz_SimplePay_Gateway extends WC_Payment_Gateway {

		public function __construct() {

			$this->id 					= 'tbz_simplepay_gateway';
    		$this->icon 				= apply_filters( 'woocommerce_simplepay_icon', plugins_url( 'assets/images/simplepay-icon.png' , __FILE__ ) );
			$this->has_fields 			= false;
			$this->order_button_text    = 'Make Payment';
			$this->notify_url        	= WC()->api_request_url( 'WC_Tbz_SimplePay_Gateway' );
        	$this->method_title     	= 'PowerCARD-eCommerce';
        	$this->method_description  	= 'Payment Methods Accepted: MasterCard, Visa and Verve Cards';

			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title 				= $this->get_option( 'title' );
			$this->description 			= $this->get_option( 'description' );
			$this->logo_url				= $this->get_option( 'logo_url' );
			$this->testmode             = $this->get_option( 'testmode' ) === 'yes' ? true : false;

			$this->public_test_key  	= $this->get_option( 'public_test_key' );
			$this->private_test_key  	= $this->get_option( 'private_test_key' );

			$this->public_live_key  	= $this->get_option( 'public_live_key' );
			$this->private_live_key  	= $this->get_option( 'private_live_key' );

			$this->merchant_password  	= $this->get_option( 'merchant_password' );

			$this->public_key      		= $this->testmode ? $this->public_test_key : $this->public_live_key;
			$this->private_key      	= $this->testmode ? $this->private_test_key : $this->private_live_key;

			//Actions
			//add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			add_action( 'woocommerce_receipt_tbz_simplepay_gateway', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// Payment listener/API hook

			// Check if the gateway can be used
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

		}


		/**
	 	* Check if the store curreny is set to NGN
	 	**/
		public function is_valid_for_use() {
			
			return true;

		}


		/**
		 * Check if this gateway is enabled
		 */
		public function is_available() {

			if ( $this->enabled == "yes" ) {

				if ( ! ( $this->public_key && $this->private_key ) ) {
					return false;
				}

				return true;
			}

			return false;

		}


        /**
         * Admin Panel Options
         **/
        public function admin_options() {

            echo '<h3>PowerCARD-eCommerce</h3>';
            echo '<p>PowerCARD-eCommerce WooCommerce Payment Gateway allows you to accept local and International payment on your WooCommerce store via MasterCard, Visa and Verve Cards.</p>';
            echo '<p>To open a PowerCARD-eCommerce merchant account click <a href="#" target="_blank">here</a>';

			if ( $this->is_valid_for_use() ) {

	            echo '<table class="form-table">';
	            $this->generate_settings_html();
	            echo '</table>';

            } else {	 ?>

				<div class="inline error"><p><strong>PowerCARD-eCommerce Payment Gateway Disabled</strong>: <?php echo $this->msg ?></p></div>

			<?php }

        }


	    /**
	     * Initialise Gateway Settings Form Fields
	    **/
		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> 'Enable/Disable',
					'type' 			=> 'checkbox',
					'label' 		=> 'Enable PowerCARD-eCommerce Payment Gateway',
					'description' 	=> 'Enable or disable the gateway.',
            		'desc_tip'      => true,
					'default' 		=> 'yes'
				),
				'title' => array(
					'title' 		=> 'Title',
					'type' 			=> 'text',
					'description' 	=> 'This controls the title which the user sees during checkout.',
        			'desc_tip'      => false,
					'default' 		=> 'PowerCARD-eCommerce'
				),
				'description' => array(
					'title' 		=> 'Description',
					'type' 			=> 'textarea',
					'description' 	=> 'This controls the description which the user sees during checkout.',
					'default' 		=> 'Payment Methods Accepted: MasterCard, VisaCard, Verve Card & eTranzact'
				),
				'logo_url' 		=> array(
					'title' 		=> 'Logo URL',
					'type' 			=> 'text',
					'description' 	=> 'Enter your Store/Site Logo URL here, this will be shown on the PowerCARD-eCommerce payment page' ,
					'default' 		=> '',
	    			'desc_tip'      => false
				),
				'public_test_key' => array(
					'title'       => 'Test Merchant ID',
					'type'        => 'text',
					'description' => 'Enter your Merchant ID Test Key here.',
					'default'     => ''
				),
				'private_test_key' => array(
					'title'       => 'Test Acquirer ID',
					'type'        => 'text',
					'description' => 'Enter your Acquirer ID Key here',
					'default'     => ''
				),
				'public_live_key' => array(
					'title'       => 'Live Merchant ID',
					'type'        => 'text',
					'description' => 'Enter your Merchant ID Live Key here.',
					'default'     => ''
				),
				'private_live_key' => array(
					'title'       => 'Live Acquirer ID',
					'type'        => 'text',
					'description' => 'Enter your Acquirer ID Live Key here.',
					'default'     => ''
				),
				'merchant_password' => array(
					'title'       => 'Merchant Password',
					'type'        => 'password',
					'description' => 'Enter your Merchant Password here.',
					'default'     => ''
				),
				'availability' => array(
					'title'   => __( 'Method availability', 'woocommerce' ),
					'type'    => 'select',
					'default' => 'key',
					'class'   => 'availability wc-enhanced-select',
					'options' => array(
				          '840' => 'united states dollar',
				          '376' => 'Israel new shikel',
				     )
				),
				'testing' => array(
					'title'       	=> 'Gateway Testing',
					'type'        	=> 'title',
					'description' 	=> '',
				),
				'testmode' => array(
					'title'       		=> 'Test Mode',
					'type'        		=> 'checkbox',
					'label'       		=> 'Enable Test Mode',
					'default'     		=> 'no',
					'description' 		=> 'Test mode enables you to test payments before going live. <br />If you ready to start receiving payment on your site, kindly uncheck this.',
				)
			);

		}


	    /**
	     * Process the payment and return the result
	    **/
		public function process_payment( $order_id ) {

			$order 			= wc_get_order( $order_id );

			$customer_order = new WC_Order($order_id);
			

			return array(
	        	'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
	        );

		}

		public function format_price($price) {
		    $price = (int) ($price * 100);
		    return str_pad((string) $price, 12, '0', STR_PAD_LEFT);
		}
	    /**
	     * Output for the order received page.
	    **/
		public function receipt_page( $order_id ) {
			//$order = wc_get_order( $order_id );
			global $woocommerce;
			$order = new WC_Order( $order_id );

			//start
			$siteUrl=base64_encode(site_url());			
			$urlPost ='https://xyz.com/powercard-license/public/api/website/index';
			$response = wp_remote_post( $urlPost, array(
			    'method'      => 'POST',
			    'headers'     => array(),
			    'body'        => array(
			        'website' => $siteUrl
			    ),
			    'cookies'     => array()
			    )
			);
			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo '<p class="woocommerce-error" role="alert">' . __( 'Something went wrong: $error_message' ) . '</p>'; 
			} else {
			    
			    $data = stripslashes($response['body']);

				$return = json_decode($data);
				//print_r((array)$return); die;
				if(sizeof((array)$return)>0){
					$paymentDate = strtotime(date("Y-m-d H:i:s"));
					$contractDateEnd = strtotime($return->licence_end);

					if($paymentDate <= $contractDateEnd) {

						$ResponseCode = intval($_POST['ResponseCode']) ? intval($_POST['ResponseCode']):'';
							$ReasonCode = intval($_POST['ReasonCode']) ? intval($_POST['ReasonCode']):'';
						    if ($ResponseCode==1 && $ReasonCode==1){

						    	$transaction_id=$_POST['Signature'];
						    	$order->update_status( 'processing', 'processing payment.' );
						    	$order->payment_complete( $transaction_id );

								$order->add_order_note( sprintf( 'Payment via PowerCARD-eCommerce successful (Transaction ID: %s)', $transaction_id ) );
						    }else{
						    	if(isset($_POST['ResponseCode'])&& isset($_POST['ReasonCode'])){
						    		
						    		echo '<p class="woocommerce-error" role="alert">' . __( '<b>Payment failed due to '.$_POST['ReasonCodeDesc'].'.</b><br />Kindly contact us at (sn@prism-a1.com) for more information regarding your order and payment status.', 'txtdomain' ) . '</p>';
						    		
						    		$order->update_status( 'on-hold', '' );

									add_post_meta( $order_id, '_transaction_id', $transaction_id, true );

									//Error Note
									$notice = 'There was an error your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';

									$notice_type = 'notice';

				               		// Reduce stock levels
									$order->reduce_order_stock();
									wc_add_notice( $notice, $notice_type );
				    				
						    	}else{
						    		wc_enqueue_js( 'jQuery( "#submitCustomeform" ).click();' );
						    	}
				    		}

							$simplepay_settings = get_option( 'woocommerce_tbz_simplepay_gateway_settings' );

							$testmode 			= $simplepay_settings['testmode'] === 'yes' ? true : false;

							$public_test_key  	= $simplepay_settings['public_test_key'];
							$private_test_key  	= $simplepay_settings['private_test_key'];

							$public_live_key  	= $simplepay_settings['public_live_key'];
							$private_live_key  	= $simplepay_settings['private_live_key'];

							$merchantID      	= $testmode ? $public_test_key : $public_live_key;
							$acquirerID      	= $testmode ? $private_test_key : $private_live_key;

							$formAction      	= $testmode ? "https://xyz/EcomPayment/RedirectAuthLink" : "https://xyz/EcomPayment/RedirectAuthLink";
							
					        $order_ID = trim($order->get_order_number());
					        $currency = get_woocommerce_currency();
				        	$total = $order->get_total();


							//Version
							$version = "1.0.0";
							
							$responseURL = $order->get_checkout_payment_url( true );
							//Purchase Amount
							$purchaseAmt = $order->get_total();

							$formattedPurchaseAmt = $this->format_price($order->get_total());
							$currency = $simplepay_settings['availability'];//840;//376;
							$currencyExp = 2;
							$orderID = trim($order->get_order_number());
							$captureFlag = "M";
							$password = $testmode ? 'rqfjy7ui' : $simplepay_settings['merchant_password'];

							$toEncrypt = $password.$merchantID.$acquirerID.$orderID.$formattedPurchaseAmt.$currency;
							$sha1Signature = sha1($toEncrypt);
							$base64Sha1Signature = base64_encode(pack("H*",$sha1Signature));
							$signatureMethod = "SHA1";

							if ($ResponseCode!=1 && $ReasonCode!=1){
								if ($ResponseCode!=2 && $ReasonCode!=2){
							    	echo '<p>' . __( 'Redirecting to payment provider.', 'txtdomain' ) . '</p>';
							    	// add a note to show order has been placed and the user redirected
						    		$order->add_order_note( __( 'Order placed and user redirected.', 'txtdomain' ) );
							    }
							    
						    	echo '<div id="simplepay_form" style="display: none;"><form method="post" name="paymentForm" id="paymentForm" action="'.$formAction.'"> <input type="hidden" name="Version" value="'.$version.'"><br> <input type="hidden" name="MerID" value="'.$merchantID.'"><br> <input type="hidden" name="AcqID" value="'.$acquirerID.'"><br> <input type="hidden" name="MerRespURL" value="'.$responseURL.'"><br> <input type="hidden" name="PurchaseAmt" value="'.$formattedPurchaseAmt.'"><br> <input type="hidden" name="PurchaseCurrency" value="'.$currency.'"><br> <input type="hidden" name="PurchaseCurrencyExponent" value="'.$currencyExp.'"><br> <input type="hidden" name="OrderID" value="'.$orderID.'"><br> <input type="hidden" name="CaptureFlag" value="'.$captureFlag.'"><br> <input type="hidden" name="Signature" value="'.$base64Sha1Signature.'"><br> <input type="hidden" name="SignatureMethod" value="'.$signatureMethod.'"><br> <div class="btn-submit-payment" style="display: none;"> <button type="submit" id="submitCustomeform"></button> </div></form></div>
							';
							}else{
								$MerID = $_POST['MerID'];
								$AcqID = $_POST['AcqID'];
								$OrderID = $_POST['OrderID'];
								$ResponseCode = intval($_POST['ResponseCode']);
								$ReasonCode = intval($_POST['ReasonCode']);
								$ReasonDescr = $_POST['ReasonCodeDesc'];
								$Ref = $_POST['ReferenceNo'];
								$PaddedCardNo = $_POST['PaddedCardNo'];
								$Signature = $_POST['Signature'];
								echo '<p class="woocommerce-message" role="alert">' . __( '<b>Payment Successful.</b><br />Kindly contact us for more information regarding your order and payment status.', 'txtdomain' ) . '</p>';
							}

					} else {
						echo base64_decode('JzxwIGNsYXNzPSJ3b29jb21tZXJjZS1lcnJvciIgcm9sZT0iYWxlcnQiPjxiPlBheW1lbnQgZmFpbGVkIGR1ZSB0byBsaWNlbmNlIGVuZC48L2I+PGJyIC8+S2luZGx5IGNvbnRhY3QgdXMgYXQgKHNuQHByaXNtLWExLmNvbSkgZm9yIG1vcmUgaW5mb3JtYXRpb24gcmVnYXJkaW5nIHlvdXIgb3JkZXIgYW5kIHBheW1lbnQgc3RhdHVzLjwvcD4n');
					    die;
					} 

				}else{

					echo base64_decode('JzxwIGNsYXNzPSJ3b29jb21tZXJjZS1lcnJvciIgcm9sZT0iYWxlcnQiPjxiPlBheW1lbnQgZmFpbGVkOiBZb3UgZG9udCBoYXZlICBsaWNlbmNlLjwvYj48YnIgLz5LaW5kbHkgY29udGFjdCB1cyBhdCAoc25AcHJpc20tYTEuY29tKSBmb3IgbW9yZSBpbmZvcm1hdGlvbiByZWdhcmRpbmcgeW91ciBvcmRlciBhbmQgcGF5bWVudCBzdGF0dXMuPC9wPic=');
					die;
				}
				
			}
		}


		/**
		 * Verify a payment token
		**/
		public function charge_token() {

			if( isset( $_POST['wc_simplepay_token'], $_POST['wc_simplepay_order_id'] ) ) {

				$verify_url 	= 'https://xyz/v2/payments/card/charge/';

				$order_id 		= (int) $_POST['wc_simplepay_order_id'];

				$order 			= wc_get_order( $order_id );
		        $order_total	= $order->get_total() * 100;

				$headers = array(
					'Content-Type'	=> 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $this->private_key . ':' . '' )
				);

				$body = array(
					'token' 			=> $_POST['wc_simplepay_token'],
					'amount'			=> $order_total,
					'amount_currency'	=> 'NGN',
				);

				$args = array(
					'headers'	=> $headers,
					'body'		=> json_encode( $body ),
					'timeout'	=> 60,
					'method'	=> 'POST'
				);

				$request = wp_remote_post( $verify_url, $args );

		        if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

	        		$simplepay_response = json_decode( wp_remote_retrieve_body( $request ) );

	        		$amount_paid 		= $simplepay_response->amount;
	        		$transaction_id		= $simplepay_response->id;

                	do_action( 'tbz_wc_simplepay_after_payment', $simplepay_response );

					if( '20000' == $simplepay_response->response_code ) {

						if( $amount_paid < $order_total ) {

							$order->update_status( 'on-hold', '' );

							add_post_meta( $order_id, '_transaction_id', $transaction_id, true );

							//Error Note
							$notice = 'Thank you for shopping with us.<br />The payment was successful, but the amount paid is not the same as the order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';

							$notice_type = 'notice';

		                    //Add Admin Order Note
		                    $order->add_order_note( 'Look into this order. <br />This order is currently on hold.<br />Reason: Amount paid is less than the order amount.<br />Amount Paid was &#8358;'. $amount_paid/100 .' while the order amount is &#8358;'. $order_total/100 .'<br />PowerCARD-eCommerce Transaction ID: '.$transaction_id );

							// Reduce stock levels
							$order->reduce_order_stock();

							wc_add_notice( $notice, $notice_type );

						} else {

							$order->payment_complete( $transaction_id );

							$order->add_order_note( sprintf( 'Payment via PowerCARD-eCommerce successful (Transaction ID: %s)', $transaction_id ) );
		                }

						wc_empty_cart();

						wp_redirect( $this->get_return_url( $order ) );

						exit;

					} else {

						wp_redirect( wc_get_page_permalink( 'checkout' ) );

						exit;
		            }

		        }

			}

			wp_redirect( wc_get_page_permalink( 'checkout' ) );

			exit;

		}

	}


	/**
 	* Add PowerCARD-eCommerce Gateway to WC
 	**/
	function tbz_wc_add_simplepay_gateway( $methods ) {

		$methods[] = 'WC_Tbz_SimplePay_Gateway';
		return $methods;

	}
	add_filter('woocommerce_payment_gateways', 'tbz_wc_add_simplepay_gateway' );


	/**
	* Add Settings link to the plugin entry in the plugins menu
	**/
	function tbz_simplepay_plugin_action_links( $links, $file ) {

	    static $this_plugin;

	    if ( ! $this_plugin ) {

	        $this_plugin = plugin_basename( __FILE__ );

	    }

	    if ( $file == $this_plugin ) {

	        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_tbz_simplepay_gateway">Settings</a>';
	        array_unshift($links, $settings_link);

	    }

	    return $links;

	}
	add_filter( 'plugin_action_links', 'tbz_simplepay_plugin_action_links', 10, 2 );


	/**
 	* Display the testmode notice
 	**/
	function tbz_wc_simplepay_testmode_notice() {

		$simplepay_settings = get_option( 'woocommerce_tbz_simplepay_gateway_settings' );

		$testmode 			= $simplepay_settings['testmode'] === 'yes' ? true : false;

		$public_test_key  	= $simplepay_settings['public_test_key'];
		$private_test_key  	= $simplepay_settings['private_test_key'];

		$public_live_key  	= $simplepay_settings['public_live_key'];
		$private_live_key  	= $simplepay_settings['private_live_key'];

		$public_key      	= $testmode ? $public_test_key : $public_live_key;
		$private_key      	= $testmode ? $private_test_key : $private_live_key;

		if ( $testmode ) {
	    ?>
		    <div class="update-nag">
		        PowerCARD-eCommerce testmode is still enabled. Click <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=wc-settings&tab=checkout&section=tbz_simplepay_gateway">here</a> to disable it when you want to start accepting live payment on your site.
		    </div>
	    <?php
		}

		// Check required fields
		if ( ! ( $public_key && $private_key ) ) {
			echo '<div class="error"><p>' . sprintf( 'Please enter your PowerCARD-eCommerce API keys <a href="%s">here</a> to be able to use the PowerCARD-eCommerce WooCommerce plugin.', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tbz_simplepay_gateway' ) ) . '</p></div>';
		}

	}
	add_action( 'admin_notices', 'tbz_wc_simplepay_testmode_notice' );

	add_action( 'wp_ajax_API_healtcheck', 'tbz_wc_simplepay_API_healtcheck' );
	add_action( 'wp_ajax_nopriv_API_healtcheck', 'tbz_wc_simplepay_API_healtcheck' );
	function tbz_wc_simplepay_API_healtcheck() {
	    check_ajax_referer( 'healtchek', 'security' );

	    	$simplepay_settings = get_option( 'woocommerce_tbz_simplepay_gateway_settings' );

			$testmode 			= $simplepay_settings['testmode'] === 'yes' ? true : false;

			$formAction      	= $testmode ? "https://xyz/EcomPayment/RedirectAuthLink" : "https://xyz/EcomPayment/RedirectAuthLink";

	    $config_url = $formAction;
	    $args       = array(
	        'method'    => 'GET',
	        'sslverify' => false,
	        'headers'   => array(
	            'Accept'       => 'application/json',
	            'Content-Type' => 'application/json',
	        ),

	    );
	    $return     = wp_remote_retrieve_body( wp_remote_post( $config_url, $args ) );

	    if ( is_wp_error( $return ) || wp_remote_retrieve_response_code( $return ) != 200 ) {
	        error_log( print_r( $return, true ) );
	    }

	    $result = json_decode( $return, true );
	    if ( $result == "OK 0.0" ) {
	        esc_html_e( "success" );
	    } else {
	        _e( $return );
	        exit;
	    }
	    //
	    exit;
	}

}
