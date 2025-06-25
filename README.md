# WooCommerce Manual M-PESA Payment Gateway

A WooCommerce payment gateway that allows customers to pay manually using M-PESA by entering their transaction details (Transaction ID and First Name) after making the payment. This solution is perfect for merchants who prefer a manual approach compared to STK Push implementations.

## Description

The WooCommerce Manual M-PESA plugin adds a straightforward payment method to your WooCommerce store that enables customers to pay via M-PESA. Unlike STK Push solutions, customers manually enter their payment details after completing the transaction on their phone.

## Features

- Simple setup and configuration
- Collects M-PESA transaction ID and payer's first name for verification
- Displays clear payment instructions to customers during checkout
- Validates transaction ID format (length and character requirements)
- Prevents duplicate transaction IDs from being used
- Stores payment details with each order for reference
- Mobile-friendly interface
- Supports WooCommerce HPOS (High-Performance Order Storage)
- Compatible with latest WordPress and WooCommerce versions

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

1. Download the plugin ZIP file from GitHub
2. Go to **Plugins > Add New > Upload Plugin** in your WordPress admin
3. Upload the ZIP file
4. Click "Install Now"
5. Activate the plugin

## Configuration

After activating the plugin:

1. Go to **WooCommerce > Settings > Payments**
2. Find "M-PESA Payment (Manual)" in the payment methods list and click "Set up"
3. Configure the following settings:

   - **Enable/Disable**: Check to enable the payment method
   - **Title**: The payment method title shown during checkout
   - **Description**: Description shown to customers during checkout
   - **Instructions**: Payment instructions shown on thank you pages and emails
   - **Till Number**: Your M-PESA Till Number where customers should send payments

4. Click "Save changes"

## Usage

1. After configuration, customers will see "M-PESA Payment (Manual)" during checkout
2. When selected, customers receive clear instructions for making their M-PESA payment
3. After payment, customers enter their:
   - First Name (as it appears on M-PESA)
   - Transaction ID from their M-PESA confirmation SMS
4. The system automatically updates order status upon verification

## Support

For support or feature requests:
- [Open an issue on GitHub](https://github.com/rmiyoyo/woocommerce-manual-mpesa/issues)

## License

This plugin is licensed under the MIT license. See the [LICENSE](LICENSE) file for details.