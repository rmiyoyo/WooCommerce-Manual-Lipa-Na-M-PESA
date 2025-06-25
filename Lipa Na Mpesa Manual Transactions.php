<?php
/**
 * Plugin Name: WooCommerce Manual M-PESA
 * Plugin URI: https://github.com/rmiyoyo/woocommerce-manual-mpesa
 * Description: Allow customers to pay manually using M-PESA by entering a Transaction ID and First Name.
 * Version: 1.2.0
 * Author: Raphael Miyoyo
 * Author URI: https://github.com/rmiyoyo
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woocommerce-manual-mpesa
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * Network: false
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WC_MANUAL_MPESA_VERSION' ) ) {
    define( 'WC_MANUAL_MPESA_VERSION', '1.2.0' );
}

if ( ! defined( 'WC_MANUAL_MPESA_PLUGIN_FILE' ) ) {
    define( 'WC_MANUAL_MPESA_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WC_MANUAL_MPESA_PLUGIN_URL' ) ) {
    define( 'WC_MANUAL_MPESA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WC_MANUAL_MPESA_PLUGIN_PATH' ) ) {
    define( 'WC_MANUAL_MPESA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

add_action( 'init', 'wc_manual_mpesa_load_textdomain' );
function wc_manual_mpesa_load_textdomain() {
    load_plugin_textdomain( 'woocommerce-manual-mpesa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'admin_notices', 'wc_manual_mpesa_admin_notices' );
function wc_manual_mpesa_admin_notices() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'WooCommerce Manual M-PESA requires WooCommerce to be installed and active.', 'woocommerce-manual-mpesa' ); ?></p>
        </div>
        <?php
        return;
    }
    
    if ( version_compare( WC_VERSION, '4.0', '<' ) ) {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'WooCommerce Manual M-PESA requires WooCommerce version 4.0 or higher.', 'woocommerce-manual-mpesa' ); ?></p>
        </div>
        <?php
    }
}

add_action( 'plugins_loaded', 'wc_manual_mpesa_init', 11 );
function wc_manual_mpesa_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }
    
    class WC_Gateway_Manual_Mpesa extends WC_Payment_Gateway {
        
        public function __construct() {
            $this->id                 = 'manual_mpesa';
            $this->icon               = '';
            $this->has_fields         = true;
            $this->method_title       = __( 'Manual M-PESA', 'woocommerce-manual-mpesa' );
            $this->method_description = __( 'Allow customers to pay manually using M-PESA by entering Transaction ID and First Name.', 'woocommerce-manual-mpesa' );
            $this->supports           = array( 'products' );
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions' );
            $this->till_number  = $this->get_option( 'till_number' );
            
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }
        
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce-manual-mpesa' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Manual M-PESA Payment', 'woocommerce-manual-mpesa' ),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => __( 'Title', 'woocommerce-manual-mpesa' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-manual-mpesa' ),
                    'default'     => __( 'M-PESA Payment', 'woocommerce-manual-mpesa' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woocommerce-manual-mpesa' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-manual-mpesa' ),
                    'default'     => __( 'Pay using M-PESA mobile money service.', 'woocommerce-manual-mpesa' ),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', 'woocommerce-manual-mpesa' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-manual-mpesa' ),
                    'default'     => __( 'Thank you for your M-PESA payment. Your order will be processed once payment is verified.', 'woocommerce-manual-mpesa' ),
                    'desc_tip'    => true,
                ),
                'till_number' => array(
                    'title'       => __( 'Till Number', 'woocommerce-manual-mpesa' ),
                    'type'        => 'text',
                    'description' => __( 'Your M-PESA Till Number where customers will send payments.', 'woocommerce-manual-mpesa' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }
        
        public function payment_fields() {
            if ( $this->description ) {
                echo '<p>' . wp_kses_post( $this->description ) . '</p>';
            }
            
            if ( empty( $this->till_number ) ) {
                echo '<p style="color: red;">' . esc_html__( 'Till Number not configured. Please contact the store administrator.', 'woocommerce-manual-mpesa' ) . '</p>';
                return;
            }
            
            $instructions = $this->get_payment_instructions();
            echo wp_kses_post( $instructions );
            
            echo '<div class="mpesa-payment-fields">';
            woocommerce_form_field( 'mpesa_first_name', array(
                'type'        => 'text',
                'class'       => array( 'form-row-wide' ),
                'label'       => __( 'First Name (as on M-PESA)', 'woocommerce-manual-mpesa' ),
                'placeholder' => __( 'Enter your first name', 'woocommerce-manual-mpesa' ),
                'required'    => true,
            ), '' );
            
            woocommerce_form_field( 'mpesa_transaction_id', array(
                'type'        => 'text',
                'class'       => array( 'form-row-wide' ),
                'label'       => __( 'M-PESA Transaction ID', 'woocommerce-manual-mpesa' ),
                'placeholder' => __( 'e.g., TC67DC0DX4', 'woocommerce-manual-mpesa' ),
                'required'    => true,
            ), '' );
            echo '</div>';
        }
        
        private function get_payment_instructions() {
            $order_total = WC()->cart->get_total( 'edit' );
            
            return sprintf(
                '<div class="mpesa-instructions" style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #00a651;">
                    <h4>%s</h4>
                    <ol>
                        <li>%s</li>
                        <li>%s</li>
                        <li>%s <strong>%s</strong></li>
                        <li>%s <strong>%s</strong></li>
                        <li>%s</li>
                        <li>%s</li>
                    </ol>
                    <p><strong>%s</strong></p>
                </div>',
                esc_html__( 'How to Pay via M-PESA:', 'woocommerce-manual-mpesa' ),
                esc_html__( 'Go to M-PESA on your phone', 'woocommerce-manual-mpesa' ),
                esc_html__( 'Select "Lipa Na M-PESA" then "Buy Goods and Services"', 'woocommerce-manual-mpesa' ),
                esc_html__( 'Enter Till Number:', 'woocommerce-manual-mpesa' ),
                esc_html( $this->till_number ),
                esc_html__( 'Enter Amount:', 'woocommerce-manual-mpesa' ),
                esc_html( $order_total ),
                esc_html__( 'Complete the transaction', 'woocommerce-manual-mpesa' ),
                esc_html__( 'You will receive an SMS with Transaction ID', 'woocommerce-manual-mpesa' ),
                esc_html__( 'Enter your First Name and Transaction ID below to complete your order.', 'woocommerce-manual-mpesa' )
            );
        }
        
        public function validate_fields() {
            $first_name = sanitize_text_field( $_POST['mpesa_first_name'] ?? '' );
            $transaction_id = sanitize_text_field( $_POST['mpesa_transaction_id'] ?? '' );
            
            if ( empty( $first_name ) ) {
                wc_add_notice( __( 'First Name is required.', 'woocommerce-manual-mpesa' ), 'error' );
                return false;
            }
            
            if ( empty( $transaction_id ) ) {
                wc_add_notice( __( 'M-PESA Transaction ID is required.', 'woocommerce-manual-mpesa' ), 'error' );
                return false;
            }
            
            if ( strlen( $transaction_id ) < 8 || strlen( $transaction_id ) > 15 ) {
                wc_add_notice( __( 'Invalid Transaction ID format.', 'woocommerce-manual-mpesa' ), 'error' );
                return false;
            }
            
            if ( ! preg_match( '/^[A-Z0-9]+$/', $transaction_id ) ) {
                wc_add_notice( __( 'Transaction ID should contain only letters and numbers.', 'woocommerce-manual-mpesa' ), 'error' );
                return false;
            }
            
            return true;
        }
        
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            
            if ( ! $order ) {
                wc_add_notice( __( 'Invalid order.', 'woocommerce-manual-mpesa' ), 'error' );
                return array( 'result' => 'fail' );
            }
            
            $first_name = sanitize_text_field( $_POST['mpesa_first_name'] ?? '' );
            $transaction_id = strtoupper( sanitize_text_field( $_POST['mpesa_transaction_id'] ?? '' ) );
            
            if ( $this->is_transaction_used( $transaction_id ) ) {
                wc_add_notice( __( 'This Transaction ID has already been used.', 'woocommerce-manual-mpesa' ), 'error' );
                return array( 'result' => 'fail' );
            }
            
            $order->update_meta_data( '_mpesa_first_name', $first_name );
            $order->update_meta_data( '_mpesa_transaction_id', $transaction_id );
            $order->update_meta_data( '_mpesa_till_number', $this->till_number );
            $order->save();
            
            $order->update_status( 'on-hold', __( 'Awaiting M-PESA payment verification.', 'woocommerce-manual-mpesa' ) );
            
            $order->add_order_note( sprintf(
                __( 'M-PESA payment details submitted. First Name: %1$s, Transaction ID: %2$s', 'woocommerce-manual-mpesa' ),
                $first_name,
                $transaction_id
            ) );
            
            wc_reduce_stock_levels( $order_id );
            WC()->cart->empty_cart();
            
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }
        
        private function is_transaction_used( $transaction_id ) {
            global $wpdb;
            
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_mpesa_transaction_id' 
                AND meta_value = %s",
                $transaction_id
            ) );
            
            return $count > 0;
        }
        
        public function thankyou_page( $order_id ) {
            if ( $this->instructions ) {
                echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
            }
            
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $transaction_id = $order->get_meta( '_mpesa_transaction_id' );
                if ( $transaction_id ) {
                    echo '<p><strong>' . esc_html__( 'Transaction ID:', 'woocommerce-manual-mpesa' ) . '</strong> ' . esc_html( $transaction_id ) . '</p>';
                }
            }
        }
        
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
                echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
            }
        }
        
        public function admin_options() {
            ?>
            <h2><?php esc_html_e( 'Manual M-PESA Settings', 'woocommerce-manual-mpesa' ); ?></h2>
            <p><?php esc_html_e( 'Configure your M-PESA payment settings below.', 'woocommerce-manual-mpesa' ); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <?php
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'wc_add_manual_mpesa_gateway' );
function wc_add_manual_mpesa_gateway( $methods ) {
    $methods[] = 'WC_Gateway_Manual_Mpesa';
    return $methods;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_manual_mpesa_action_links' );
function wc_manual_mpesa_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=manual_mpesa' ) . '">' . __( 'Settings', 'woocommerce-manual-mpesa' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

register_activation_hook( __FILE__, 'wc_manual_mpesa_activation_check' );
function wc_manual_mpesa_activation_check() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 
            esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'woocommerce-manual-mpesa' ),
            esc_html__( 'Plugin Activation Error', 'woocommerce-manual-mpesa' ),
            array( 'back_link' => true )
        );
    }
}

add_action( 'before_woocommerce_init', 'wc_manual_mpesa_declare_compatibility' );
function wc_manual_mpesa_declare_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}