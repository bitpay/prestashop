//load bitpay remotely
function loadRemoteBitPay(){
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = '//bitpay.com/bitpay.min.js';
    document.body.appendChild(script);
}
loadRemoteBitPay()

function bpTest(){
    bitpay.enableTestMode()
}
function hideMain(){
    console.log('asdasdasdasd')
}
