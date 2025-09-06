<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Assistant IA Médical</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .chat-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            color: #2c5aa0;
            margin-bottom: 30px;
        }
        
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #fafafa;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
        }
        
        .user-message {
            background: #2c5aa0;
            color: white;
            margin-left: 20%;
            text-align: right;
        }
        
        .assistant-message {
            background: #e3f2fd;
            color: #333;
            margin-right: 20%;
        }
        
        .input-container {
            display: flex;
            gap: 10px;
        }
        
        .message-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .send-button {
            padding: 12px 20px;
            background: #2c5aa0;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .send-button:hover {
            background: #1e3d72;
        }
        
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .suggestion {
            background: #f0f0f0;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            border: none;
        }
        
        .suggestion:hover {
            background: #e0e0e0;
        }
        
        .disclaimer {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .loading {
            text-align: center;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <h1>🤖 Assistant IA Médical</h1>
            <p>Posez vos questions de santé générale ou sur notre plateforme</p>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="message assistant-message">
                <strong>Assistant:</strong> Bonjour ! Je suis votre assistant médical virtuel. Comment puis-je vous aider aujourd'hui ?
            </div>
        </div>
        
        <div class="suggestions">
            <button class="suggestion" onclick="sendPredefinedMessage('J\'ai mal à la tête, que faire ?')">Mal de tête</button>
            <button class="suggestion" onclick="sendPredefinedMessage('Comment prendre rendez-vous ?')">Prendre RDV</button>
            <button class="suggestion" onclick="sendPredefinedMessage('Que faire en cas de fièvre ?')">Fièvre</button>
            <button class="suggestion" onclick="sendPredefinedMessage('Comment payer en ligne ?')">Paiement</button>
        </div>
        
        <div class="input-container">
            <input 
                type="text" 
                id="messageInput" 
                class="message-input" 
                placeholder="Tapez votre message..."
                onkeypress="handleKeyPress(event)"
            >
            <button class="send-button" onclick="sendMessage()">Envoyer</button>
        </div>
    </div>

    <script>
        let conversationId = 'web-test-' + Date.now();
        
        function addMessage(content, isUser = false) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + (isUser ? 'user-message' : 'assistant-message');
            messageDiv.innerHTML = '<strong>' + (isUser ? 'Vous' : 'Assistant') + ':</strong> ' + content;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function showLoading() {
            const messagesContainer = document.getElementById('chatMessages');
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message assistant-message loading';
            loadingDiv.id = 'loading';
            loadingDiv.innerHTML = '<strong>Assistant:</strong> Je réfléchis...';
            messagesContainer.appendChild(loadingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function removeLoading() {
            const loading = document.getElementById('loading');
            if (loading) {
                loading.remove();
            }
        }
        
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addMessage(message, true);
            input.value = '';
            showLoading();
            
            try {
                const response = await fetch('/api/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: conversationId
                    })
                });
                
                const data = await response.json();
                removeLoading();
                
                if (data.status === 'success') {
                    addMessage(data.response);
                    
                    if (data.disclaimer) {
                        const messagesContainer = document.getElementById('chatMessages');
                        const disclaimerDiv = document.createElement('div');
                        disclaimerDiv.className = 'disclaimer';
                        disclaimerDiv.textContent = data.disclaimer;
                        messagesContainer.appendChild(disclaimerDiv);
                    }
                } else {
                    addMessage('Désolé, une erreur est survenue: ' + data.message);
                }
                
            } catch (error) {
                removeLoading();
                addMessage('Erreur de connexion. Veuillez réessayer.');
                console.error('Erreur:', error);
            }
        }
        
        function sendPredefinedMessage(message) {
            document.getElementById('messageInput').value = message;
            sendMessage();
        }
        
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
    </script>
</body>
</html>