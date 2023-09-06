// Version: 1.0.0
//GLOBALS
var LAST_DATE = "";
var LAST_ID = 0;

if(!!window.EventSource) {
    createSource();
}
else {
    console.log("Your browser doesn't support SSE");
}
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
async function beginHeartbeat(uuid){
    setInterval(function(){
        const xhr = new XMLHttpRequest();
        xhr.open('GET', WPCHAT.baseUrl+'/heartbeat?uuid='+uuid, true);
        xhr.send();
    }, 20000);
}
async function createSource(){
    const uuid = generateUUID();
    let date_param = "";
    if(LAST_DATE !== ""){
        date_param = "&last_date="+LAST_DATE;
    }
    if(LAST_ID !== 0){
        date_param = date_param+"&last_id="+LAST_ID;
    }
    const source = new EventSource(WPCHAT.baseUrl+'/sse?uuid='+uuid+date_param);
    source.addEventListener('newMessage', function(e) {
        console.log(e.data);
        const data = JSON.parse(e.data);
        LAST_DATE = data[0].date;
        LAST_ID = data[0].id;
    }, false);
    source.addEventListener('open', function(e) {
        // Connection was opened.
        console.log('Connection was opened.');
        //beginHeartbeat(uuid);
    }
    , false);
    source.addEventListener('error', function(e) {
        console.log('Error occurred. and connection was closed.');
        source.close();
    }
    , false);
    source.addEventListener('closeConnection', function(e) {
        // Connection was closed.
        console.log('Connection was closed from the server.');
        source.close();
    }
    , false);
    source.addEventListener('expiredConnection', function(e) {
        // Connection was closed.
        console.log('Connection has expired. Opening new connection.');
        source.close();
        //Open new connection
        createSource();
    }
    , false);
}
async function sendMessage(){
    const message = "Hello World! : "+generateUUID();
    const group_id = 500;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', WPCHAT.baseUrl+'/sendMessage', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-WP-Nonce', WPCHAT.nonce);
    xhr.send(JSON.stringify({
        message: message,
        group_id: group_id
    }));
}