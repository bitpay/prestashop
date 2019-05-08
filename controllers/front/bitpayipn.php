<?php
if (isset($_POST)):
    $all_data = json_decode(file_get_contents("php://input"), true);
    #print_r($all_data);
    #
    $data = $all_data['data'];
    $event = $all_data['event'];
    $orderId = $data['orderId'];
    $transaction_id = $data['id'];
    $transaction_status = $event['name'];

    $table_name = '_bitpay_checkout_transactions';
    $order_table = 'ps_orders';
    $order_history_table = 'ps_order_history';

    $bp_sql = "SELECT * FROM $table_name WHERE transaction_id = '$transaction_id'";
    $results = Db::getInstance()->executes($bp_sql);
    if (count($results) == 1):
        $d = $results[0];

        switch ($transaction_status) {
            case 'invoice_confirmed': #complete
                #update the order and history
                $current_state = 2;
                $db = Db::getInstance();
                $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
                $db->Execute($bp_u);

                #update the transaction table
                $bp_t = "UPDATE $table_name SET transaction_status = '$transaction_status' WHERE transaction_id = '$transaction_id' AND order_id = $orderId";
                $db->Execute($bp_t);

                #update the history table
                $order_history_table = 'ps_order_history';
                $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                        VALUES (0,'$orderId',$current_state,NOW())";
                $db->Execute($bp_h);


                break;

            case 'invoice_paidInFull': #pending
            #update the order and history
            $current_state = 3;
            $db = Db::getInstance();
            $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
            $db->Execute($bp_u);

            #update the transaction table
            $bp_t = "UPDATE $table_name SET transaction_status = '$transaction_status' WHERE transaction_id = '$transaction_id' AND order_id = $orderId";
            $db->Execute($bp_t);

            #update the history table
            $order_history_table = 'ps_order_history';
            $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                    VALUES (0,'$orderId',$current_state,NOW())";
            $db->Execute($bp_h);

                break;

            case 'invoice_failedToConfirm':
            #update the order and history
            $current_state = 8;
            $db = Db::getInstance();
            $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
            $db->Execute($bp_u);

            #update the transaction table
            $bp_t = "UPDATE $table_name SET transaction_status = '$transaction_status' WHERE transaction_id = '$transaction_id' AND order_id = $orderId";
            $db->Execute($bp_t);

            #update the history table
            $order_history_table = 'ps_order_history';
            $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                    VALUES (0,'$orderId',$current_state,NOW())";
            $db->Execute($bp_h);

                break;
            case 'invoice_expired':
                //delete the previous order
                #update the order and history
                $current_state = 6;
                $db = Db::getInstance();
                $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
                $db->Execute($bp_u);

                #update the transaction table
                $bp_t = "UPDATE $table_name SET transaction_status = '$transaction_status' WHERE transaction_id = '$transaction_id' AND order_id = $orderId";
                $db->Execute($bp_t);

                #update the history table
                $order_history_table = 'ps_order_history';
                $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                        VALUES (0,'$orderId',$current_state,NOW())";
                $db->Execute($bp_h);

                break;

            case 'invoice_refundComplete':
            #update the order and history
            $current_state = 7;
            $db = Db::getInstance();
            $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
            $db->Execute($bp_u);

            #update the transaction table
            $bp_t = "UPDATE $table_name SET transaction_status = '$transaction_status' WHERE transaction_id = '$transaction_id' AND order_id = $orderId";
            $db->Execute($bp_t);

            #update the history table
            $order_history_table = 'ps_order_history';
            $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                    VALUES (0,'$orderId',$current_state,NOW())";
            $db->Execute($bp_h);

                break;

        }
    endif;

endif;
