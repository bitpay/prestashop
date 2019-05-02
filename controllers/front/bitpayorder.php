<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class BitpayCheckoutBitpayorderModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'bitpaycheckout') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'bitpayorder'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);
     

       
       
        $this->setTemplate('module:bitpaycheckout/views/templates/front/payment_return.tpl');


         $customer = new Customer($cart->id_customer);
         if (!Validate::isLoadedObject($customer))
             Tools::redirect('index.php?controller=order&step=1');

         $currency = $this->context->currency;
         $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
         $mailVars = array(
            
         );
          #load BP classess
         $level = 2;
         include(dirname(__DIR__, $level)."/BitPayLib/BPC_Client.php");
         include(dirname(__DIR__, $level)."/BitPayLib/BPC_Configuration.php");
         include(dirname(__DIR__, $level)."/BitPayLib/BPC_Invoice.php");
         include(dirname(__DIR__, $level)."/BitPayLib/BPC_Item.php");     

         #BITPAY SPECIFIC INFO
        
      
         $env = 'test';
         $bitpay_token = Configuration::get('bitpay_checkout_token_dev');
         
        if (Configuration::get('bitpay_checkout_endpoint') == 1):
            $env = 'production';
            $bitpay_token = get_option('bitpay_checkout_token_prod');
        endif;
        global $cookie;
        $module = Module::getInstanceByName('bitpaycheckout');
        $version = $module->version;
       
        $currency = new CurrencyCore($cookie->id_currency);
        $config = new BPC_Configuration($bitpay_token, $env);
        $params = new stdClass();
       
        $params->fullNotifications = 'true';
        $params->extension_version = 'BitPayCheckout_PrestaShop_'.$version;
        $params->price = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $params->currency = $currency->iso_code;
        $params->orderId = trim($cart->id);

        $params->extendedNotifications = true;
        $params->transactionSpeed = 'medium';
        $params->acceptanceWindow = 1200000;

        $item = new BPC_Item($config, $params);
        $invoice = new BPC_Invoice($item);
        //this creates the invoice with all of the config params from the item
        $invoice->BPC_createInvoice();
        $invoiceData = json_decode($invoice->BPC_getInvoiceData());
        die();

         #$current_user = wp_get_current_user();
        /*
        if ($bitpay_checkout_options['bitpay_checkout_capture_email'] == 1):
            $current_user = wp_get_current_user();

            if ($current_user->user_email):
                $buyerInfo = new stdClass();
                $buyerInfo->name = $current_user->display_name;
                $buyerInfo->email = $current_user->user_email;
                $params->buyer = $buyerInfo;
            endif;
        endif;
        */

            //orderid
            


        die();
       # $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
         #Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }
}
