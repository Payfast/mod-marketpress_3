<?php

class MP_Gateway_PayFast extends MP_Gateway_API
{
    //build
    var $build = 2;

    //private gateway slug. Lowercase alpha (a-z) and underscores (_) only please!
    var $plugin_name = 'payfast';

    //name of your gateway, for the admin side.
    var $admin_name = '';

    //public name of your gateway, for lists and such.
    var $public_name = '';

    //url for an image for your checkout method. Displayed on checkout form if set
    var $method_img_url = '';

    //url for an submit button image for your checkout method. Displayed on checkout form if set
    var $method_button_img_url = '';

    //whether or not ssl is needed for checkout page
    var $force_ssl = false;

    //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
    var $ipn_url;

    //only required for global capable gateways. The maximum stores that can checkout at once
    var $max_stores = 1;

    // Payment action
    var $payment_action = 'Sale';

    //payfast vars
    var $pfMerchantId, $pfMerchantKey, $SandboxFlag, $returnURL, $cancelURL, $payfastURL, $version, $currencyCode, $passphrase, $test_mode;

    //use confirmation step
    var $use_confirmation_step = true;

    //whether if this is the only enabled gateway it can skip the payment_form step
    var $skip_form = true;

    var $currencies = array( 'ZAR' => 'ZAR - South African Rand');


    function on_creation()
    {
        //set names here to be able to translate
        $this->admin_name  = __( 'PayFast', 'mp' );
        $this->public_name = __( 'PayFast', 'mp' );


        $this->method_img_url = 'https://www.payfast.co.za/images/logo/PayFast_Logo_75.png';
        $this->method_button_img_url = 'https://www.payfast.co.za/images/logo/PayFast_Logo_75.png';

        $this->currencyCode = "ZAR";

        //determine mode
        $this->SandboxFlag = $this->get_setting('mode');

        $this->cancelURL = add_query_arg( 'cancel', '1', mp_store_page_url( 'checkout', false ) );
        $this->version   = '2.0'; //api version

        // Set api urls
        if ( $mode == 'sandbox' )
        {
            $this->payfastURL = 'https://sandbox.payfast.co.za/eng/process';
        }
        else
        {
            $this->payfastURL = 'https://www.payfast.co.za/eng/process';
        }
        // Set passphrase //
        $this->passphrase = $this->get_setting('payfast_credentials->passphrase');
    }

    /**
     * Init network settings metaboxes
     *
     * @since 3.0
     * @access public
     */
    function init_network_settings_metabox()
    {
        $metabox = new WPMUDEV_Metabox( array(
            'id' => $this->generate_metabox_id(),
            'page_slugs' => array( 'network-store-settings' ),
            'title' => __( 'PayFast Network Settings', 'mp' ),
            'site_option_name' => 'mp_network_settings',
            'desc' => __( 'You will need to setup an account on the <a href="https://www.payfast.co.za">PayFast website</a> in order to process transactions using PayFast', 'mp' ),
            'order' => 16,
            'conditional' => array(
                'operator' => 'AND',
                'action' => 'show',
                array(
                    'name' => 'global_cart',
                    'value' => 1,
                ),
                array(
                    'name' => 'global_gateway',
                    'value' => $this->plugin_name,
                ),
            ),
        ) );
        $this->common_metabox_fields( $metabox );
    }

    /**
     * Init settings metaboxes
     *
     * @since 3.0
     * @access public
     */
    function init_settings_metabox()
    {
        $metabox = new WPMUDEV_Metabox( array(
            'id' => $this->generate_metabox_id(),
            'page_slugs' => array( 'store-settings-payments' ),
            'title' => __( 'PayFast Settings', 'mp' ),
            'option_name' => 'mp_settings',
            'desc' => __( 'You will need to setup an account on the <a href="https://www.payfast.co.za">PayFast website</a> in order to process transactions using PayFast', 'mp' ),
            'conditional' => array(
                'name' => 'gateways[allowed][' . $this->plugin_name . ']',
                'value' => 1,
                'action' => 'show',
            ),
        ) );

        if ( mp_get_network_setting( 'global_cart' ) )
        {
            $metabox->add_field( 'text', array(
                'name' => $this->get_field_name( 'merchant_email' ),
                'label' => array( 'text' => __( 'Merchant Email', 'mp' ) ),
                'validation' => array(
                    'required' => true,
                    'email' => true,
                ),
            ) );
        } 
        else 
        {
            $this->common_metabox_fields( $metabox );
        }
    }

    /**
     * Both network settings and blog setting use these same fields
     *
     * @since 3.0
     * @access public
     *
     * @param WPMUDEV_Metabox $metabox
     */
    function common_metabox_fields( $metabox )
    {
        $metabox->add_field( 'advanced_select', array(
            'name' => $this->get_field_name( 'currency' ),
            'label' => array( 'text' => __( 'Currency', 'mp' ) ),
            'multiple' => false,
            'options' => $this->currencies,
            'width' => 'element',
        ) );
        $metabox->add_field( 'radio_group', array(
            'name' => $this->get_field_name( 'mode' ),
            'label' => array( 'text' => __( 'Mode', 'mp' ) ),
            'default_value' => 'sandbox',
            'options' => array(
                'sandbox' => __( 'Sandbox', 'mp' ),
                'live' => __( 'Live', 'mp' ),
            ),
        ) );
        $creds = $metabox->add_field( 'complex', array(
            'name' => $this->get_field_name( 'payfast_credentials' ),
            'label' => array( 'text' => __( 'PayFast Credentials', 'mp' ) ),
           ) );

        if ( $creds instanceof WPMUDEV_Field )
        {
            $creds->add_field( 'text', array(
                'name' => 'merchant_id',
                'label' => array( 'text' => __( 'Merchant ID', 'mp' ) ),
                'validation' => array(
                    'required' => true,
                ),
            ) );
            $creds->add_field( 'text', array(
                'name' => 'merchant_key',
                'label' => array( 'text' => __( 'Merchant Key', 'mp' ) ),
                'validation' => array(
                    'required' => true,
                ),
            ) );
            $creds->add_field( 'text', array(
                'name' => 'passphrase',
                'label' => array( 'text' => __( 'Passphrase', 'mp' ) ),
            ) );
        }
    }



    /**
     * Return fields you need to add to the payment screen, like your credit card info fields.
     *    If you don't need to add form fields set $skip_form to true so this page can be skipped
     *    at checkout.
     *
     * @param array $cart . Contains the cart contents for the current blog, global cart if mp()->global_cart is true
     * @param array $shipping_info . Contains shipping info and email in case you need it
     */
    function payment_form( $cart, $shipping_info )
    {
        return __( 'You will be redirected to PayFast to finalize your payment.', 'mp' );
    }



    function process_payment( $cart, $billing_info, $shipping_info )
    {
        global $mp;
        require_once ( 'payfast_common.inc' );
        $order_id = $_SESSION['mp_order'];
        $shipping_info = $_SESSION['mp_shipping_info'];
        $pfAmount = 0;
        $pfDescription = '';
        $pfOutput = '';
        $timestamp = time();
        $orderId = mp()->generate_order_id();

        $returnURL = $this->returnURL = mp_checkout_step_url( 'checkout' ) . "?success=1";
        $cancelURL = $this->cancelURL = mp_checkout_step_url( 'checkout' ) . "?cancel=1";

        $selected_cart = $global_cart;
        $settings = get_site_option( 'mp_network_settings' );

        if ( $this->SandboxFlag == 'sandbox' )
        {
            $payfastUrl = "https://sandbox.payfast.co.za/eng/process";
        }
        else
        {
            $payfastUrl = "https://www.payfast.co.za/eng/process";
        }

        $total = 0;
        $counter = 1;
        $items = $cart->get_items_as_objects();

        foreach ( $items as $item )
        {
            $price = $item->get_price( 'lowest' );
            $total += ($price * $item->qty);

            $counter ++;
        }

        $shipping_tax = 0;
        if ( ($shipping_price = $cart->shipping_total( false )) !== false )
        {
            $counter += 1;
            $total += $shipping_price;
        }

        //tax line
        if ( !mp_get_setting( 'tax->tax_inclusive' ) )
        {
            $counter += 1;
            $total += $tax_price;
        }

        $pfAmount = $total;

        // Construct variables for post
        $data = array(
            'merchant_id' => ( $this->SandboxFlag == 'sandbox' ? '10000100' : $this->get_setting( 'payfast_credentials->merchant_id' ) ),
            'merchant_key' => ( $this->SandboxFlag == 'sandbox' ? '46f0cd694581a' : $this->get_setting( 'payfast_credentials->merchant_key' ) ),
            'return_url' => $returnURL,
            'cancel_url' => $cancelURL,
            'notify_url' => $this->ipn_url,
            'm_payment_id' => $orderId,
            'amount' => number_format(sprintf("%01.2f", $pfAmount), 2, '.', ''),
            'item_name' => 'Order #' . $orderId,
        );
        foreach( $data as $key => $val )
        {
            if( !empty( $val ) )
            {
                $pfOutputSig .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
        }

        if ( !empty( $this->passphrase ) && $this->SandboxFlag == 'live' )
        {
            $getString .= $pfOutputSig.'passphrase='.urlencode( $this->get_setting('payfast_credentials->passphrase') );
        }
        else
        {
            // Remove last ampersand
            $getString .= substr( $pfOutputSig, 0, -1 );
        }

        $pfOutput = $pfOutputSig .'signature='.md5($getString) .'&user_agent=MarketPress 3.x';

        $order = new MP_Order( $orderId );
        $order->save( array(
            'payment_info' => array(
                'gateway_public_name' => $this->public_name,
                'gateway_private_name' => $this->admin_name,
                'status' => array(
                    $timestamp => __( 'Received', 'mp' ),
                ),
                'total' => $cart->total(),
                'currency' => $this->currencyCode,
                'transaction_id' => $orderId,
                'method' => __( 'PayFast', 'mp' ),
            ),
            'cart' => mp_cart(),
            'paid' => false,
        ) );

        header("Location: " . $payfastUrl . "?" . $pfOutput);
        exit(0);
    }

    /**
     * Use to handle any payment returns from your gateway to the ipn_url. Do not echo anything here. If you encounter errors
     *    return the proper headers to your ipn sender. Exits after.
     */
    function process_ipn_return()
    {
        global $mp;
        require_once( 'payfast_common.inc' );
        $timestamp = time();

        if ($mp->get_setting('gateways->payfast->debug') == 'no')
        {
            define('PF_DEBUG', false);
        }
        else
        {
            define('PF_DEBUG', true);
        }
        $invoice = $pfData['m_payment_id'];

        $pfError = false;
        $pfErrMsg = '';
        $pfDone = false;
        $pfData = array();
        $pfHost = ( ( $this->SandboxFlag == 'sandbox' ) ? 'sandbox' : 'www') . '.payfast.co.za';
        $pfOrderId = '';
        $pfParamString = '';


        pflog('PayFast ITN call received');

        //// Notify PayFast that information has been received
        if ( !$pfError && !$pfDone )
        {
            header( 'HTTP/1.0 200 OK' );
            flush();
        }

        //// Get data sent by PayFast
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Get posted data' );

            // Posted variables from ITN
            $pfData = pfGetData();

            pflog( 'PayFast Data: ' . print_r( $pfData, true ) );

            if ( $pfData === false )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Verify security signature' );

            $pfPassPhrase = !empty( $this->passphrase ) && $this->SandboxFlag == 'live' ? $this->passphrase : null;

            // If signature different, log for debugging
            if ( !pfValidSignature( $pfData, $pfParamString, $pfPassPhrase ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }

        //// Verify source IP (If not in debug mode)
        if ( !$pfError && !$pfDone && !PF_DEBUG )
        {
            pflog( 'Verify source IP' );

            if ( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }

        //// Get internal order and verify it hasn't already been processed
        if( !$pfError && !$pfDone )
        {
            // Get order data
            $pfOrderId = $pfData['m_payment_id'];
            $order = $mp->get_order( $pfOrderId );

            pflog( "Purchase:\n". print_r( $order, true )  );

            // Check if order has already been processed
            // It has been "processed" if it has a status above "Order Received"
            if( $purchase['processed'] > get_option( 'payfast_pending_status' ) )
            {
                pflog( "Order has already been processed" );
                $pfDone = true;
            }
        }

        //// Verify data received
        if ( !$pfError )
        {
            pflog( 'Verify data received' );

            $pfValid = pfValidData( $pfHost, $pfParamString );

            if ( !$pfValid )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Check status and update order
        if ( !$pfError && !$pfDone )
        {
            pflog( 'Check status and update order ');

                switch ( $pfData['payment_status' ])
                {
                    case 'COMPLETE':
                        pflog('- Complete');
                        $order = new MP_Order( $pfData['m_payment_id'] );

                        $payment_info = $order->get_meta( 'mp_payment_info' );
                        $payment_info['transaction_id'] = $pfData['m_payment_id'];
                        $payment_info['method'] = 'PayFast';

                        $order->update_meta( 'mp_payment_info', $payment_info );
                        $order->change_status( 'paid', true );

                        break;

                    case 'FAILED':
                        pflog('- Failed');
                        $status = __("The payment has failed. This happens only if the payment was made from your customer's bank account.", 'mp');
                        $paid = false;

                        // Need to wait for "Completed" before processing
                        break;

                    case 'PENDING':
                        pflog('- Pending');
                        $status = __('The payment is pending.', 'mp');
                        $paid = false;

                        // Need to wait for "Completed" before processing
                        break;

                    default:
                        // If unknown status, do nothing (safest course of action)
                        break;
                }

            $order->log_ipn_status( $payment_status . ': ' . $status );
        }


        // If an error occurred
        if ( $pfError )
        {
            pflog('Error occurred: ' . $pfErrMsg);
        }

        // Close log
        pflog( '', true );
        exit();

        }

        function trim_name($name, $length = 127)
        {
            while ( strlen( urlencode( $name ) ) > $length )
            {
                $name = substr( $name, 0, -1 );
            }

            return urldecode( $name );
        }
}
//register gateway plugin
        mp_register_gateway_plugin( 'MP_Gateway_PayFast', 'payfast', __( 'PayFast', 'mp' ), true );