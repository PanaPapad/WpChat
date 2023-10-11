// Version: 1.0.0
//GLOBALS
const DATE_FORMATTER = {
    LONG_DATE :{
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        formatMatcher: 'best fit',
        hour: 'numeric',
        minute: 'numeric'
    },
    SHORT_DATE :{
        weekday: 'short',
        hour: 'numeric',
        minute: 'numeric'
    },
    SHORT_TIME :{
        hour: 'numeric',
        minute: 'numeric'
    },
    /**
     * Format date.
     * @param {Date} date Date to be formatted.
     * @param {string} format Format of the date. Default is LONG_DATE.
     * @returns {string} Formatted date.
     **/
    format: function(date){
        //Date < 24 hours ago
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        if(diff < 86400000){
            const formatter = new Intl.DateTimeFormat('en-US', this.SHORT_TIME);
            return formatter.format(date);
        }
        //Date < 7 days ago
        if(diff < 604800000){
            const formatter = new Intl.DateTimeFormat('en-US', this.SHORT_DATE);
            return formatter.format(date);
        }
        //Date > 7 days ago
        const formatter = new Intl.DateTimeFormat('en-US', this.LONG_DATE);
        return formatter.format(date);
    }
}
//GLOBALS
var LAST_DATE = "";
var LAST_ID = 0;
var GROUP_ID = 500;//Default Group ID


/**
 * Initialize WPChat.
 */
async function wpChatInit(){
    //Check if EventSource is supported
    if(!!window.EventSource) {
        createSource();
    }
    else {
        console.error("Your browser doesn't support SSE");
    }
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
 * @param {string} uuid UUID of the connection.
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
        const data = JSON.parse(e.data);
        //Get latest message date and id to be used in next request
        LAST_DATE = data[0].date;
        LAST_ID = data[0].message_id;
        //Most recent message is at the start of the array
        for(let i=data.length-1; i>=0; i--){
            const date = new Date(data[i].date);
            const message = {
                "data": data[i].message,
                "metadata": `${data[i].username}, `+DATE_FORMATTER.format(date),
                "isMine": data[i].sender === WPCHAT.current_user_id
            }
            presentMessage(message);
        }
    }, false);
    source.addEventListener('open', function(e) {
        // Connection was opened.
        console.info('Connection was opened.');
        //beginHeartbeat(uuid);
    }
    , false);
    source.addEventListener('error', function(e) {
        console.warn('Error occurred. and connection was closed.');
        source.close();
    }
    , false);
    source.addEventListener('closeConnection', function(e) {
        // Connection was closed.
        console.info('Connection was closed from the server.');
        source.close();
    }
    , false);
    source.addEventListener('expiredConnection', function(e) {
        // Connection was closed.
        console.info('Connection has expired. Opening new connection.');
        source.close();
        //Open new connection
        createSource();
    }
    , false);
}
/**
 * Send message to server.
 * @param {string} message Message to be sent.
 */
async function sendMessage(message){
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
 * Create Chat Input and attach it to the Chat Window.
 */
async function createChatInput(){
    const chatWindow = document.getElementById("wpChatWindow");
    if(chatWindow === null){
        console.error("Chat Window not found. Please create chat window first.");
        return;
    }
    if(document.getElementById("wpChatInput") !== null){
        document.getElementById("wpChatInput").remove();
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

    //Add Event Listeners to button and input
    sendButton.addEventListener("click", sendTextInput);
    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendTextInput();
        }
    });
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
        console.error("Message Container not found. Please create chat window first.");
        return;
    }
    const messageElement = document.createElement("div");
    if(message.isMine){
        messageElement.setAttribute("class", "wpchat-msg-left");
    }
    else{
        messageElement.setAttribute("class", "wpchat-msg-right");
    }
    //Add Message Content
    const messageContent = document.createElement("div");
    if(message.isMine){
        messageContent.setAttribute("class", "wpchat-msg-content-left");
    }
    else{
        messageContent.setAttribute("class", "wpchat-msg-content-right");
    }
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
    messageContainer.scrollTop = messageContainer.scrollHeight;
}
/**
 * Send message from input field.
 */
async function sendTextInput(){
    const input = document.getElementById("wpChatInputField");
    if(input === null){
        console.error("Input field not found. Please create chat input first.");
        return;
    }
    const message = input.value;
    input.value = "";
    sendMessage(message);
}
