const chatMessages = document.querySelector('#chat-messages');
const chatMessage = document.querySelector('#chat-message');

if (chatMessages?.lastElementChild) {
    chatMessages.lastElementChild.scrollIntoView({ block: 'end' });
}

chatMessage?.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
        return;
    }

    event.preventDefault();
    chatMessage.form?.requestSubmit();
});
