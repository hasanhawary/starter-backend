(function () {
    'use strict';

    var VERSION = '1.0.0';

    var STORAGE_KEY = 'ai_chat_session_id';
    var STORAGE_CONVERSATIONS = 'ai_chat_conversations';

    var DEFAULTS = {
        apiBaseUrl: '',
        position: 'bottom-right',
        theme: 'light',
        title: 'AI Assistant',
        subtitle: 'Ask me anything',
        welcomeMessage: '',
        systemPrompt: '',
        avatar: '',
        bubbleIcon: '',
        primaryColor: '#4f46e5',
        backgroundColor: '#ffffff',
        textColor: '#111827',
        maxFileSize: 10485760,
        autoOpen: false,
        openDelay: 3000,
        height: 500,
        width: 380,
        onOpen: null,
        onClose: null,
        onMessage: null,
        onError: null,
    };

    var STATE = {
        isOpen: false,
        isLoading: false,
        isThinking: false,
        conversations: [],
        currentConversationId: null,
        messages: [],
    };

    var config = {};
    var shadowRoot = null;
    var container = null;
    var bubble = null;
    var window_ = null;

    function merge() {
        var target = arguments[0] || {};
        for (var i = 1; i < arguments.length; i++) {
            var source = arguments[i];
            if (source) {
                for (var key in source) {
                    if (source.hasOwnProperty(key)) {
                        target[key] = source[key];
                    }
                }
            }
        }
        return target;
    }

    function getSessionId() {
        var id = localStorage.getItem(STORAGE_KEY);
        if (!id) {
            id = 'sess_' + generateUUID();
            localStorage.setItem(STORAGE_KEY, id);
        }
        return id;
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(date) {
        var d = new Date(date);
        var h = d.getHours().toString().padStart(2, '0');
        var m = d.getMinutes().toString().padStart(2, '0');
        return h + ':' + m;
    }

    function renderMarkdown(text) {
        if (!text) return '';
        var escaped = escapeHtml(text);
        escaped = escaped.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code class="lang-$1">$2</code></pre>');
        escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');
        escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        escaped = escaped.replace(/\*(.+?)\*/g, '<em>$1</em>');
        escaped = escaped.replace(/^### (.+)$/gm, '<h4>$1</h4>');
        escaped = escaped.replace(/^## (.+)$/gm, '<h3>$1</h3>');
        escaped = escaped.replace(/^# (.+)$/gm, '<h2>$1</h2>');
        escaped = escaped.replace(/\n/g, '<br>');
        return escaped;
    }

    function createStyles() {
        var isDark = config.theme === 'dark';
        var primary = config.primaryColor;
        var bg = config.backgroundColor;
        var text = config.textColor;

        if (isDark) {
            bg = bg === '#ffffff' ? '#1f2937' : bg;
            text = text === '#111827' ? '#f3f4f6' : text;
        }

        return '\
* { box-sizing: border-box; margin: 0; padding: 0; }\
\
:host { all: initial; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }\
\
.ai-chat-bubble {\
    position: fixed;\
    z-index: 999999;\
    width: 60px;\
    height: 60px;\
    border-radius: 50%;\
    background: ' + primary + ';\
    color: #fff;\
    cursor: pointer;\
    display: flex;\
    align-items: center;\
    justify-content: center;\
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\
    transition: transform 0.2s, box-shadow 0.2s;\
    user-select: none;\
    border: none;\
    font-size: 28px;\
    line-height: 1;\
}\
\
.ai-chat-bubble:hover {\
    transform: scale(1.1);\
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);\
}\
\
.ai-chat-bubble.pos-bottom-right { bottom: 24px; right: 24px; }\
.ai-chat-bubble.pos-bottom-left { bottom: 24px; left: 24px; }\
.ai-chat-bubble.pos-top-right { top: 24px; right: 24px; }\
.ai-chat-bubble.pos-top-left { top: 24px; left: 24px; }\
\
.ai-chat-window {\
    position: fixed;\
    z-index: 999998;\
    width: ' + config.width + 'px;\
    height: ' + config.height + 'px;\
    max-height: 80vh;\
    max-width: 95vw;\
    background: ' + bg + ';\
    border-radius: 16px;\
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);\
    display: flex;\
    flex-direction: column;\
    overflow: hidden;\
    font-size: 14px;\
    color: ' + text + ';\
    transition: opacity 0.2s, transform 0.2s;\
    opacity: 0;\
    transform: scale(0.95) translateY(10px);\
    pointer-events: none;\
}\
\
.ai-chat-window.open {\
    opacity: 1;\
    transform: scale(1) translateY(0);\
    pointer-events: all;\
}\
\
.ai-chat-window.pos-bottom-right { bottom: 96px; right: 24px; }\
.ai-chat-window.pos-bottom-left { bottom: 96px; left: 24px; }\
.ai-chat-window.pos-top-right { top: 24px; right: 24px; }\
.ai-chat-window.pos-top-left { top: 24px; left: 24px; }\
\
.ai-chat-header {\
    padding: 16px 20px;\
    background: ' + primary + ';\
    color: #fff;\
    display: flex;\
    align-items: center;\
    justify-content: space-between;\
    flex-shrink: 0;\
}\
\
.ai-chat-header-left { display: flex; align-items: center; gap: 10px; }\
\
.ai-chat-avatar {\
    width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.2);\
    display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;\
}\
\
.ai-chat-header-info h3 { font-size: 15px; font-weight: 600; }\
.ai-chat-header-info p { font-size: 12px; opacity: 0.85; margin-top: 1px; }\
\
.ai-chat-close {\
    background: rgba(255,255,255,0.15); border: none; color: #fff; width: 32px; height: 32px;\
    border-radius: 50%; cursor: pointer; font-size: 18px; display: flex;\
    align-items: center; justify-content: center; transition: background 0.15s;\
}\
.ai-chat-close:hover { background: rgba(255,255,255,0.3); }\
\
.ai-chat-messages {\
    flex: 1; overflow-y: auto; padding: 16px; display: flex;\
    flex-direction: column; gap: 12px;\
}\
\
.ai-chat-messages::-webkit-scrollbar { width: 4px; }\
.ai-chat-messages::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 4px; }\
\
.ai-msg { max-width: 85%; display: flex; flex-direction: column; }\
.ai-msg-user { align-self: flex-end; }\
.ai-msg-assistant { align-self: flex-start; }\
\
.ai-msg-bubble {\
    padding: 10px 14px; border-radius: 16px; line-height: 1.5; word-wrap: break-word;\
}\
.ai-msg-user .ai-msg-bubble {\
    background: ' + primary + '; color: #fff; border-bottom-right-radius: 4px;\
}\
.ai-msg-assistant .ai-msg-bubble {\
    background: ' + (isDark ? '#374151' : '#f3f4f6') + '; color: ' + text + '; border-bottom-left-radius: 4px;\
}\
\
.ai-msg-bubble pre {\
    background: ' + (isDark ? '#1f2937' : '#e5e7eb') + ';\
    padding: 10px; border-radius: 8px; overflow-x: auto; margin: 6px 0; font-size: 13px;\
}\
.ai-msg-bubble code {\
    font-family: "SF Mono", Monaco, Consolas, monospace; font-size: 13px;\
}\
.ai-msg-bubble code:not(pre code) {\
    background: ' + (isDark ? '#1f2937' : '#e5e7eb') + '; padding: 2px 6px; border-radius: 4px;\
}\
\
.ai-msg-time { font-size: 11px; opacity: 0.5; margin-top: 4px; }\
.ai-msg-user .ai-msg-time { text-align: right; }\
\
.ai-thinking-indicator {\
    display: flex; align-items: center; gap: 8px; padding: 10px 14px;\
    color: ' + (isDark ? '#9ca3af' : '#6b7280') + '; font-size: 13px;\
}\
\
.ai-thinking-dots { display: flex; gap: 4px; }\
.ai-thinking-dots span {\
    width: 6px; height: 6px; background: ' + (isDark ? '#9ca3af' : '#6b7280') + '; border-radius: 50%;\
    animation: ai-think-bounce 1.4s infinite ease-in-out both;\
}\
.ai-thinking-dots span:nth-child(1) { animation-delay: 0s; }\
.ai-thinking-dots span:nth-child(2) { animation-delay: 0.2s; }\
.ai-thinking-dots span:nth-child(3) { animation-delay: 0.4s; }\
\
@keyframes ai-think-bounce {\
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }\
    40% { transform: scale(1); opacity: 1; }\
}\
\
.ai-chat-input {\
    padding: 12px 16px; border-top: 1px solid ' + (isDark ? '#374151' : '#e5e7eb') + ';\
    display: flex; align-items: flex-end; gap: 8px; flex-shrink: 0;\
}\
\
.ai-chat-textarea {\
    flex: 1; resize: none; border: 1px solid ' + (isDark ? '#4b5563' : '#d1d5db') + ';\
    border-radius: 12px; padding: 10px 14px; font-size: 14px; font-family: inherit;\
    color: ' + text + '; background: ' + bg + '; outline: none;\
    max-height: 120px; min-height: 40px; line-height: 1.5;\
}\
\
.ai-chat-textarea:focus { border-color: ' + primary + '; }\
\
.ai-chat-send {\
    width: 40px; height: 40px; border-radius: 50%; background: ' + primary + ';\
    color: #fff; border: none; cursor: pointer; display: flex; align-items: center;\
    justify-content: center; font-size: 18px; flex-shrink: 0; transition: opacity 0.15s;\
}\
\
.ai-chat-send:hover { opacity: 0.85; }\
.ai-chat-send:disabled { opacity: 0.4; cursor: not-allowed; }\
\
.ai-chat-footer {\
    padding: 6px 16px 10px; text-align: center; font-size: 11px;\
    opacity: 0.4; flex-shrink: 0;\
}\
\
.ai-chat-error {\
    background: #fef2f2; color: #dc2626; padding: 8px 12px; border-radius: 8px;\
    font-size: 13px; margin: 4px 0;\
}\
';
    }

    function buildBubble() {
        bubble = document.createElement('button');
        bubble.className = 'ai-chat-bubble pos-' + config.position;
        bubble.innerHTML = config.bubbleIcon || '&#x1F4AC;';
        bubble.title = config.title;
        bubble.addEventListener('click', toggle);
        return bubble;
    }

    function buildWindow() {
        window_ = document.createElement('div');
        window_.className = 'ai-chat-window pos-' + config.position;
        window_.innerHTML = '\
<div class="ai-chat-header">\
    <div class="ai-chat-header-left">\
        <div class="ai-chat-avatar">' + (config.avatar || '&#x1F916;') + '</div>\
        <div class="ai-chat-header-info">\
            <h3>' + escapeHtml(config.title) + '</h3>\
            <p>' + escapeHtml(config.subtitle) + '</p>\
        </div>\
    </div>\
    <button class="ai-chat-close">&times;</button>\
</div>\
<div class="ai-chat-messages"></div>\
<div class="ai-chat-input">\
    <textarea class="ai-chat-textarea" rows="1" placeholder="Type a message..."></textarea>\
    <button class="ai-chat-send">&#x27A4;</button>\
</div>\
<div class="ai-chat-footer">Powered by GLM-5.1</div>';

        var messagesEl = window_.querySelector('.ai-chat-messages');
        var textarea = window_.querySelector('.ai-chat-textarea');
        var sendBtn = window_.querySelector('.ai-chat-send');
        var closeBtn = window_.querySelector('.ai-chat-close');

        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        textarea.addEventListener('input', function () {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        });

        sendBtn.addEventListener('click', sendMessage);
        closeBtn.addEventListener('click', function () { toggle(); });

        return window_;
    }

    function toggle() {
        STATE.isOpen = !STATE.isOpen;
        if (STATE.isOpen) {
            window_.classList.add('open');
            bubble.style.display = 'none';
            if (typeof config.onOpen === 'function') config.onOpen();

            if (STATE.messages.length === 0 && config.welcomeMessage) {
                appendMessage('assistant', config.welcomeMessage);
            }

            var textarea = window_.querySelector('.ai-chat-textarea');
            setTimeout(function () { textarea.focus(); }, 100);
        } else {
            window_.classList.remove('open');
            bubble.style.display = 'flex';
            if (typeof config.onClose === 'function') config.onClose();
        }
    }

    function appendMessage(role, content, thinking) {
        var messagesEl = window_.querySelector('.ai-chat-messages');
        var msg = document.createElement('div');
        msg.className = 'ai-msg ai-msg-' + role;

        var bubbleHtml = '';

        if (role === 'assistant' && thinking) {
            bubbleHtml = '<div class="ai-thinking-indicator"><div class="ai-thinking-dots"><span></span><span></span><span></span></div><span>Thinking...</span></div>';
        }

        if (content) {
            bubbleHtml = '<div class="ai-msg-bubble">' + renderMarkdown(content) + '</div>';
        }

        var time = formatTime(new Date());
        bubbleHtml += '<div class="ai-msg-time">' + time + '</div>';

        msg.innerHTML = bubbleHtml;
        messagesEl.appendChild(msg);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        return msg;
    }

    function appendError(message) {
        var messagesEl = window_.querySelector('.ai-chat-messages');
        var err = document.createElement('div');
        err.className = 'ai-chat-error';
        err.textContent = message;
        messagesEl.appendChild(err);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        setTimeout(function () { err.remove(); }, 8000);
    }

    function updateLastAssistantMessage(content) {
        var messagesEl = window_.querySelector('.ai-chat-messages');
        var lastMsg = messagesEl.querySelector('.ai-msg-assistant:last-child');
        if (!lastMsg) {
            lastMsg = appendMessage('assistant', '');
        }
        var bubble = lastMsg.querySelector('.ai-msg-bubble');
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.className = 'ai-msg-bubble';
            var timeEl = lastMsg.querySelector('.ai-msg-time');
            if (timeEl) {
                lastMsg.insertBefore(bubble, timeEl);
            } else {
                lastMsg.appendChild(bubble);
            }
        }
        bubble.innerHTML = renderMarkdown(content);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setLoading(loading) {
        STATE.isLoading = loading;
        var sendBtn = window_.querySelector('.ai-chat-send');
        sendBtn.disabled = loading;
    }

    function setThinking(thinking) {
        STATE.isThinking = thinking;
        if (thinking) {
            appendMessage('assistant', '', true);
        } else {
            var messagesEl = window_.querySelector('.ai-chat-messages');
            var indicator = messagesEl.querySelector('.ai-thinking-indicator');
            if (indicator) {
                indicator.parentElement.remove();
            }
        }
    }

    function sendMessage() {
        var textarea = window_.querySelector('.ai-chat-textarea');
        var message = textarea.value.trim();

        if (!message || STATE.isLoading) return;

        appendMessage('user', message);
        textarea.value = '';
        textarea.style.height = 'auto';

        STATE.messages.push({ role: 'user', content: message });
        setLoading(true);

        var body = {
            message: message,
            session_id: getSessionId(),
            stream: true,
        };

        if (STATE.currentConversationId) {
            body.conversation_id = STATE.currentConversationId;
        }

        if (config.systemPrompt) {
            body.system_prompt = config.systemPrompt;
        }

        fetch(config.apiBaseUrl + '/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        }).then(function (response) {
            if (!response.ok) {
                return response.json().then(function (data) {
                    throw new Error(data.message || 'Request failed: ' + response.status);
                });
            }

            var contentType = response.headers.get('content-type') || '';

            if (contentType.indexOf('text/event-stream') !== -1) {
                return streamResponse(response);
            }

            return response.json().then(function (data) {
                var reply = data.message || data.text || '';
                appendMessage('assistant', reply);
                STATE.messages.push({ role: 'assistant', content: reply });
                STATE.currentConversationId = data.conversation_id;
                if (typeof config.onMessage === 'function') config.onMessage({ role: 'assistant', content: reply });
            });
        }).catch(function (error) {
            appendError(error.message);
            if (typeof config.onError === 'function') config.onError(error);
        }).finally(function () {
            setLoading(false);
            setThinking(false);
        });
    }

    function streamResponse(response) {
        var fullText = '';
        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';
        var currentEvent = '';

        setThinking(true);

        function processChunk(result) {
            if (result.done) {
                if (buffer.trim()) parseSSELines(buffer.split('\n'));
                finalizeStream(fullText);
                return;
            }

            buffer += decoder.decode(result.value, { stream: true });
            var lines = buffer.split('\n');
            buffer = lines.pop() || '';

            parseSSELines(lines);

            return reader.read().then(processChunk);
        }

        function parseSSELines(lines) {
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim();

                if (line.startsWith('event:')) {
                    currentEvent = line.substring(6).trim();
                    continue;
                }

                if (!line || !line.startsWith('data:')) continue;
                var data = line.substring(5).trim();

                if (currentEvent === 'conversation_id') {
                    try {
                        var parsed = JSON.parse(data);
                        if (parsed.conversation_id) {
                            STATE.currentConversationId = parsed.conversation_id;
                        }
                    } catch (e) {}
                    currentEvent = '';
                    continue;
                }

                currentEvent = '';

                if (data === '[DONE]') continue;

                try {
                    var parsed = JSON.parse(data);

                    if (parsed.type === 'thinking_start' || parsed.type === 'reasoning_start') {
                        setThinking(true);
                        continue;
                    }

                    if (parsed.type === 'thinking_end' || parsed.type === 'reasoning_end') {
                        setThinking(false);
                        continue;
                    }

                    if (parsed.type === 'thinking_delta' || parsed.type === 'reasoning_delta') {
                        continue;
                    }

                    if (parsed.type === 'text_delta' || parsed.type === 'text_start') {
                        var delta = parsed.delta || parsed.content || '';
                        fullText += delta;
                        setThinking(false);
                        updateLastAssistantMessage(fullText);
                        continue;
                    }

                    if (parsed.type === 'text_end' || parsed.type === 'stream_end') {
                        continue;
                    }
                } catch (e) {
                    // skip unparseable lines
                }
            }
        }

        function finalizeStream(text) {
            if (text) {
                STATE.messages.push({ role: 'assistant', content: text });
                if (typeof config.onMessage === 'function') config.onMessage({ role: 'assistant', content: text });
            }
        }

        return reader.read().then(processChunk);
    }

    function init(options) {
        config = merge({}, DEFAULTS, options || {});

        if (!config.apiBaseUrl) {
            console.error('[AIChatWidget] apiBaseUrl is required');
            return;
        }

        if (config.apiBaseUrl.endsWith('/')) {
            config.apiBaseUrl = config.apiBaseUrl.slice(0, -1);
        }

        if (typeof ShadowRoot !== 'undefined') {
            container = document.createElement('div');
            shadowRoot = container.attachShadow({ mode: 'open' });

            var style = document.createElement('style');
            style.textContent = createStyles();
            shadowRoot.appendChild(style);

            shadowRoot.appendChild(buildBubble());
            shadowRoot.appendChild(buildWindow());

            document.body.appendChild(container);
        } else {
            console.error('[AIChatWidget] Shadow DOM is not supported in this browser');
            return;
        }

        if (config.autoOpen) {
            setTimeout(toggle, config.openDelay || 3000);
        }
    }

    window.AIChatWidget = {
        init: init,
        open: function () { if (!STATE.isOpen) toggle(); },
        close: function () { if (STATE.isOpen) toggle(); },
        destroy: function () { if (container) container.remove(); },
        sendMessage: function (msg) {
            var textarea = window_.querySelector('.ai-chat-textarea');
            textarea.value = msg;
            sendMessage();
        },
        VERSION: VERSION,
    };
})();
