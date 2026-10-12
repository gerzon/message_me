const chatMessages = document.querySelector('#chat-messages');

if (chatMessages?.lastElementChild) {
    chatMessages.lastElementChild.scrollIntoView({ block: 'end' });
}
