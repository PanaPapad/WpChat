<div id="wpChatWindow" class="wpchat-window">
	<div id="wpChatMessageContainer" class="wpchat-window-msg">
		<div class="wpchat-msg-left">
			<div class="wpchat-msg-content-left">
				<p class="wpchat-msg-text">Hello, how are you?</p>
			</div>
			<div class="wpchat-msg-info">
				<p class="wpchat-msg-info-text">John Doe, 12:00</p>
			</div>
		</div>
        <div class="wpchat-msg-right">
			<div class="wpchat-msg-content-right">
				<p class="wpchat-msg-text">Hello, how are you?</p>
			</div>
			<div class="wpchat-msg-info">
				<p class="wpchat-msg-info-text">John Doe, 12:00</p>
			</div>
		</div></div>
	<div id="wpChatInput" class="wpchat-window-input-window">
		<input id="wpChatInputField" class="wpchat-input-field" type="text" placeholder="Type your message here..." />
		<button type="button" id="wpChatSendButton" class="wpchat-send-button">Send</button>
	</div>
</div>
<script>
	document.addEventListener('DOMContentLoaded', function(){
		wpChatInit();
		createChatInput();
	});
</script>