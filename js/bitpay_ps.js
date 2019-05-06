//load bitpay remotely
function loadRemoteBitPay(){
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = '//bitpay.com/bitpay.min.js';
    document.body.appendChild(script);
   

    if (window.location.href.indexOf("order-confirmation?id_cart=") > -1 && getCookie('invoiceID') != "" ) {
        showModal()
    }
}
loadRemoteBitPay()

function showModal(){
    var payment_status = null
    var is_paid = null
    jQuery("#main").css('opacity','0.3')
    window.addEventListener("message", function (event) {
        payment_status = event.data.status;
        if (payment_status == "paid") {
            is_paid = true
            jQuery("#main").css('opacity','1')
            deleteCookie('invoiceID')
            deleteCookie('oID')
            return;
        } 
    }, false);

    

    setTimeout(function(){ 
        if(getCookie('env')=='test'){
            bitpay.enableTestMode()
        }
        var parts = window.location.search.substr(1).split("&");
        var $_GET = {};
        for (var i = 0; i < parts.length; i++) {
            var temp = parts[i].split("=");
            $_GET[decodeURIComponent(temp[0])] = decodeURIComponent(temp[1]);
        }
        $customerKey = $_GET['key']
        $cid = $_GET['cid']
        $api = window.location.origin + '/module/bitpaycheckout/bitpayorder'
        $cart = window.location.origin + '/cart?action=show'
        bitpay.showInvoice(getCookie('invoiceID')); 

        bitpay.onModalWillLeave(function() {
            if (is_paid == true) {
                jQuery("#main").css('opacity','1')
                //delete the saved cart
                var $dataObj = {
                    action: 's',
                    cid: $cid,
                   
                }
                var saveData = jQuery.ajax({
                    type: 'POST',
                    url: $api,
                    data: $dataObj,
                    dataType: "text",
                    success: function(resultData) {
                        deleteCookie('invoiceID')
                        deleteCookie('oID')
                        location.reload()
                    }
                });


            } else {
                //we need the customer key
                
                var $dataObj = {
                    action: 'd',
                    orderid: getCookie('oID'),
                    invoiceID: getCookie('invoiceID'),
                    customerKey:$customerKey
                }
               
                var saveData = jQuery.ajax({
                    type: 'POST',
                    url: $api,
                    data: $dataObj,
                    dataType: "text",
                    success: function(resultData) {
                        console.log('aaaaa')
                        deleteCookie('invoiceID')
                        deleteCookie('oID')
                        window.location = $cart;
                    }
                });
            }
        });
    }, 500);


}

function deleteCookie(cname) {
    document.cookie = cname + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;';

}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}




function showModal2(){
    console.log('env')
    
    
    /*
    setTimeout(function(){ 
        $j(".main").css('opacity','0.3')

    jQuery.post( "/showmodal/index/modal", function(data ) {
    jQuery("#bitpaybtn").prop("disabled",true)
       var is_paid = false
       console.log('data',data)
       var response = JSON.parse(data)
        window.addEventListener("message", function (event) {
            payment_status = event.data.status;
            if (payment_status == "paid") {
                is_paid = true
                window.location.href =response.redirectURL;
                return;
            } 
        }, false);
            
            //hide the order info
            bitpay.onModalWillEnter(function () {
                $j(".main").css('opacity','0.3')
            });
            //show the order info
            bitpay.onModalWillLeave(function () {
                if (is_paid == false) {
                  window.location.href = response.cartFix;
                } //endif
            });
            //show the modal
            if(env == 'test'){
            bitpay.enableTestMode()
            }
            setTimeout(function(){ bitpay.showInvoice(response.invoiceID); }, 10);
            
      });
    }, 1000);
    */
}
