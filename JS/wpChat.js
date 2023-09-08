// Version: 1.0.0
//GLOBALS
const DATETIME_FORMAT_OPTIONS = {
    weekday: 'short',
    formatMatcher: 'best fit',

}
var LAST_DATE = "";
var LAST_ID = 0;
var GROUP_ID = 500;//Default Group ID
const SAMPLE_MESSAGE = {
    "data": "This is a sample message.",
    "metadata": "Jon Doe, 10:00 AM"
};

if(!!window.EventSource) {
    createSource();
}
else {
    console.log("Your browser doesn't support SSE");
}
/**
 * Create a UUID.
 * @returns {string} UUID
 */
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
/**
 * Begin Heartbeat to keep connection alive.
 */ 
async function beginHeartbeat(uuid){
    setInterval(function(){
        const xhr = new XMLHttpRequest();
        xhr.open('GET', WPCHAT.baseUrl+'/heartbeat?uuid='+uuid, true);
        xhr.send();
    }, 20000);
}
/**
 * Create Event Source to listen to server events.
 */
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
        for(let i=0; i<data.length; i++){
            const date = new Date(data[i].date);
            const message = {
                "data": data[i].message,
                "metadata": "Jon Doe, "+date.toLocaleTimeString('en-US',DATETIME_FORMAT_OPTIONS)
            }
            presentMessage(message);
        }
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
/**
 * Send message to server.
 */
async function sendMessage(){
    const message = "Hello World! : "+generateUUID();
    const group_id = GROUP_ID;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', WPCHAT.baseUrl+'/sendMessage', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-WP-Nonce', WPCHAT.nonce);
    xhr.send(JSON.stringify({
        message: message,
        group_id: group_id
    }));
}
/**
 * Create Chat Window and attach to element with provided ID.
 * @param {string} element_id ID of the element to which chat window will be attached. If not provided, chat window will be attached to body.
 */
async function createChatWindow(element_id){
    const baseElement = document.getElementById(element_id);
    //Create Chat Window
    const chatWindow = document.createElement("div");
    chatWindow.setAttribute("id", "wpChatWindow");
    chatWindow.setAttribute("class", "wpchat-window");
    //Add Message Container
    const messageContainer = document.createElement("div");
    messageContainer.setAttribute("id", "wpChatMessageContainer");
    messageContainer.setAttribute("class", "wpchat-window-msg");
    chatWindow.appendChild(messageContainer);
    if(baseElement === null){
        document.body.appendChild(chatWindow);
    }
    else{
        baseElement.appendChild(chatWindow);
    }
}
/**
 * Create Chat Input and attach to element with provided ID.
 */
async function createChatInput(){
    const chatWindow = document.getElementById("wpChatWindow");
    if(chatWindow === null){
        console.log("Chat Window not found. Please create chat window first.");
        return;
    }
    const chatInput = document.createElement("div");
    chatInput.setAttribute("id", "wpChatInput");
    chatInput.setAttribute("class", "wpchat-window-input-window");
    //Add Input Field
    const input = document.createElement("input");
    input.setAttribute("id", "wpChatInputField");
    input.setAttribute("class", "wpchat-input-field");
    input.setAttribute("type", "text");
    input.setAttribute("placeholder", "Type your message here...");
    chatInput.appendChild(input);
    //Add Send Button
    const sendButton = document.createElement("button");
    sendButton.setAttribute("id", "wpChatSendButton");
    sendButton.setAttribute("class", "wpchat-send-button");
    sendButton.innerHTML = "Send";
    //Attach Elements to Chat Window
    chatInput.appendChild(sendButton);
    chatWindow.appendChild(chatInput);
}
/**
 * Present message in chat window.
 * @param {object} message Message object
 */
async function presentMessage(message){
    /**@type {string} */
    const data = message.data;
    /**@type {string} */
    const metadata = message.metadata;
    const messageContainer = document.getElementById("wpChatMessageContainer");
    if(messageContainer === null){
        console.log("Message Container not found. Please create chat window first.");
        return;
    }
    const messageElement = document.createElement("div");
    messageElement.setAttribute("class", "wpchat-msg");
    //Add Message Content
    const messageContent = document.createElement("div");
    messageContent.setAttribute("class", "wpchat-msg-content");
    const messageText = document.createElement("p");
    messageText.setAttribute("class", "wpchat-msg-text");
    messageText.innerHTML = data;
    messageContent.appendChild(messageText);
    //Add Metadata
    const messageMetadata = document.createElement("div");
    messageMetadata.setAttribute("class", "wpchat-msg-info");
    const messageMetadataText = document.createElement("p");
    messageMetadataText.setAttribute("class", "wpchat-msg-info-text");
    messageMetadataText.innerHTML = metadata;
    messageMetadata.appendChild(messageMetadataText);
    //Attach Elements to Message Container
    messageElement.appendChild(messageContent);    
    messageElement.appendChild(messageMetadata);
    messageContainer.appendChild(messageElement);
}