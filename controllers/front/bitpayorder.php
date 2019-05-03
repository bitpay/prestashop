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
extract($_POST);
if(isset($action) && $action == 'ipn'):
    echo 'a';
    die();
   #this will be the ipn section
elseif(isset($action) && $action == 'd'):
    

    $table_name = '_bitpay_checkout_transactions';
    $bp_sql = "SELECT * FROM $table_name WHERE order_id = $orderid AND transaction_id = '$invoiceID' AND customer_key = '$customerKey'";
    $results = Db::getInstance()->executes($bp_sql);
    if(count($results)==1):
        #delete this order
        $bp_d = "DELETE FROM $table_name WHERE transaction_id = '$invoiceID'";
        $db = Db::getInstance();
        $db->Execute($bp_d);
        #delete from the order table
        $order_table = 'ps_orders';
        $bp_d = "DELETE FROM $order_table WHERE id_order = '$orderid'";
        $db = Db::getInstance();
        $db->Execute($bp_d);    

        #delete from the history table
        $order_hisory_table = 'ps_order_history';
        $bp_d = "DELETE FROM $order_hisory_table WHERE id_order = '$orderid'";
        $db = Db::getInstance();
        $db->Execute($bp_d);

        die();
    endif;
    
   
endif;

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
        

        $use_modal =  Configuration::get('bitpay_checkout_flow');
        #modal 
        if ($use_modal == 0):
            #redirect
            #wp_redirect($invoice->BPC_getInvoiceURL());
        else:
           #modal
           # $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
            $orderId = (int)$this->module->currentOrder;

            $config = new BPC_Configuration($bitpay_token, $env);
            $params = new stdClass();
        
            $params->fullNotifications = 'true';
            $params->extension_version = 'BitPayCheckout_PrestaShop_'.$version;
            $params->price = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $params->currency = $currency->iso_code;
            $params->orderId = $orderId;

            $params->extendedNotifications = true;
            $params->transactionSpeed = 'medium';
            $params->acceptanceWindow = 1200000;

            if(Configuration::get('bitpay_checkout_capture_email') == 1)
                if ($customer->email):
                    $buyerInfo = new stdClass();
                    $buyerInfo->name = $customer->firstname.' '.$customer->lastname;
                    $buyerInfo->email = $customer->email;
                    $params->buyer = $buyerInfo;
                endif;
            endif;

            $item = new BPC_Item($config, $params);
            $invoice = new BPC_Invoice($item);
            //this creates the invoice with all of the config params from the item
            $invoice->BPC_createInvoice();
            $invoiceData = json_decode($invoice->BPC_getInvoiceData());
            $invoiceID = $invoiceData->data->id;

            setcookie('invoiceID', $invoiceID, time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('oID', $orderId, time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('env', $env, time() + (86400 * 30), "/"); // 86400 = 1 day

            $table_name = '_bitpay_checkout_transactions';
            $bp_sql = "INSERT INTO $table_name (order_id,transaction_id,customer_key) VALUES ($orderId,'$invoiceID','$customer->secure_key')";
            $db = Db::getInstance();
            $db->Execute($bp_sql);

            $order_table = 'ps_orders';
            $bp_u = "UPDATE $order_table SET current_state = 3 WHERE id_order = '$orderId' AND secure_key = '$customer->secure_key'";
            $db = Db::getInstance();
            $db->Execute($bp_u);  


            $order_hisory_table = 'ps_order_history';
            $bp_u = "INSERT INTO $order_hisory_table (id_employee,id_order,id_order_state,date_add) 
            VALUES (0,'$orderId',3,NOW())";
            $db = Db::getInstance();
            $db->Execute($bp_u);

            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
           

        endif;
    }
}
