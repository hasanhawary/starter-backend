(function () {
    'use strict';

    var VERSION = '3.1.0';

    var STORAGE_KEY = 'ai_chat_session_id';
    var STORAGE_CONVERSATION_KEY = 'ai_chat_conversation_id';
    var STORAGE_MESSAGES_KEY = 'ai_chat_messages';

    var DEFAULTS = {
        apiBaseUrl: '',
        position: 'bottom-right',
        theme: 'light',
        title: 'WaKeb AI',
        subtitle: 'How can we help you?',
        welcomeMessage: 'Welcome to WaKeb AI! How can we assist you today?',
        avatar: '',
        primaryColor: '#5196F3',
        backgroundColor: '#ffffff',
        textColor: '#111827',
        autoOpen: false,
        openDelay: 3000,
        height: 600,
        width: 380,
        allowFullscreen: true,
        showBranding: true,
        brandingText: 'Powered by WaKeb AI',
        maxFileSize: 10485760,
        onOpen: null,
        onClose: null,
        onMessage: null,
        onError: null,
        showFeedback: true,
        showSuggestions: true,
        suggestedPrompts: [
            'What AI solutions does WaKeb offer?',
            'Tell me about WaKeb products',
            'How can WaKeb help my business?',
            'Schedule a consultation',
        ],
        agentAvatar: '',
        onlineStatus: 'online',
    };

    var STATE = {
        isOpen: false,
        isMinimized: false,
        isFullscreen: false,
        isLoading: false,
        isThinking: false,
        isStreaming: false,
        currentConversationId: null,
        messages: [],
        error: null,
    };

    var config = {};
    var shadowRoot = null;
    var container = null;
    var bubble = null;
    var windowEl = null;
    var abortController = null;
    var confirmOverlay = null;
    var unreadCount = 0;
    var isRtl = false;
    var currentLang = 'ar';
    var LANG_KEY = 'ai_chat_lang';

    var TRANSLATIONS = {
        ar: {
            subtitle: 'كيف يمكننا مساعدتك؟',
            welcomeMessage: 'مرحباً! كيف يمكنني مساعدتك اليوم؟',
            inputPlaceholder: 'اكتب رسالتك...',
            brandingText: 'مدعوم من WaKeb AI',
            thinking: 'يفكر...',
            stopGenerating: 'إيقاف التوليد',
            retry: 'إعادة المحاولة',
            copyReply: 'نسخ الرد',
            helpful: 'مفيد',
            notHelpful: 'غير مفيد',
            openChat: 'فتح المحادثة',
            closeChat: 'إغلاق المحادثة',
            close: 'إغلاق',
            minimize: 'تصغير',
            newConversation: 'محادثة جديدة',
            fullscreen: 'شاشة كاملة',
            exitFullscreen: 'خروج من الشاشة الكاملة',
            sendMessage: 'إرسال',
            chatMessages: 'رسائل المحادثة',
            toggleLanguage: 'English',
            aiThinking: 'الذكاء الاصطناعي يفكر',
            dismissError: 'تجاهل الخطأ',
            minimizeToggle: 'تبديل التصغير',
            suggestedPrompts: 'أسئلة مقترحة',
            suggestedPromptsList: [
                'ما هي حلول الذكاء الاصطناعي التي تقدمها WaKeb؟',
                'حدثني عن منتجات WaKeb',
                'كيف يمكن WaKeb مساعدة أعمالي؟',
                'احجز استشارة',
            ],
            online: 'متصل الآن',
            aiTitle: 'المساعد الذكي',
            moreOptions: 'خيارات إضافية',
            clearConversation: 'مسح المحادثة',
            clearConfirm: 'سيتم مسح كل الرسائل. هل أنت متأكد؟',
            cancel: 'إلغاء',
            confirm: 'تأكيد',
        },
    };

    function __(key, fallback) {
        if (currentLang === 'ar' && TRANSLATIONS.ar[key]) {
            return TRANSLATIONS.ar[key];
        }
        return fallback || key;
    }

    function getStoredLanguage() {
        return localStorage.getItem(LANG_KEY) || '';
    }

    function setStoredLanguage(lang) {
        localStorage.setItem(LANG_KEY, lang);
    }

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

    function getStoredConversationId() {
        return localStorage.getItem(STORAGE_CONVERSATION_KEY);
    }

    function setStoredConversationId(id) {
        if (id) {
            localStorage.setItem(STORAGE_CONVERSATION_KEY, id);
        } else {
            localStorage.removeItem(STORAGE_CONVERSATION_KEY);
        }
    }

    function saveMessages() {
        try {
            localStorage.setItem(STORAGE_MESSAGES_KEY, JSON.stringify(STATE.messages));
        } catch (e) {}
    }

    function loadMessagesFromStorage() {
        try {
            var stored = localStorage.getItem(STORAGE_MESSAGES_KEY);
            if (stored) {
                var parsed = JSON.parse(stored);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed;
                }
            }
        } catch (e) {}
        return null;
    }

    function clearStoredMessages() {
        localStorage.removeItem(STORAGE_MESSAGES_KEY);
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
        var now = new Date();
        var isToday = d.getDate() === now.getDate() &&
            d.getMonth() === now.getMonth() &&
            d.getFullYear() === now.getFullYear();
        var h = d.getHours().toString().padStart(2, '0');
        var m = d.getMinutes().toString().padStart(2, '0');
        if (isToday) {
            return h + ':' + m;
        }
        var month = (d.getMonth() + 1).toString().padStart(2, '0');
        var day = d.getDate().toString().padStart(2, '0');
        return month + '/' + day + ' ' + h + ':' + m;
    }

    function renderMarkdown(text) {
        if (!text) return '';
        var escaped = escapeHtml(text);

        escaped = escaped.replace(/```(\w*)\n?([\s\S]*?)```/g, function (match, lang, code) {
            var langClass = lang ? ' class="lang-' + escapeHtml(lang) + '"' : '';
            var langLabel = lang ? '<span class="ai-code-lang">' + escapeHtml(lang) + '</span>' : '';
            return '<div class="ai-code-block">' +
                langLabel +
                '<button class="ai-code-copy" data-code="' + escapeHtml(code.trim()) + '" title="Copy code">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>' +
                '</button>' +
                '<pre><code' + langClass + '>' + code.trim() + '</code></pre>' +
                '</div>';
        });

        escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');

        escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        escaped = escaped.replace(/\*(.+?)\*/g, '<em>$1</em>');

        escaped = escaped.replace(/^### (.+)$/gm, '<h4>$1</h4>');
        escaped = escaped.replace(/^## (.+)$/gm, '<h3>$1</h3>');
        escaped = escaped.replace(/^# (.+)$/gm, '<h2>$1</h2>');

        escaped = escaped.replace(/^- (.+)/gm, '<li>$1</li>');

        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    function svgIcon(name) {
        var icons = {
            sparkles: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 6 6 0 0 0-9 9 6 6 0 0 0-9-9 6 6 0 0 0 9-9Z"/><path d="M8 14.5 10.5 17l-2.5 1"/><path d="M14 6.5 16.5 9 14 10"/></svg>',
            close: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
            minimize: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>',
            maximize: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/></svg>',
            minimize2: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>',
            send: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12 12 5l7 7"/></svg>',
            stop: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>',
            copy: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
            check: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
            refresh: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
            trash: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',
            chat: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9 10h.01"/><path d="M15 10h.01"/></svg>',
            thumbsUp: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H11z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>',
            thumbsDown: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3H10z"/><path d="M17 2h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"/></svg>',
            wakeb: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18 6 6l3 7 3-7 3 7 3-7 3 12"/></svg>',
            newChat: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>',
            robot: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="6" width="16" height="12" rx="3"/><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M12 4v2"/><path d="M12 18v2"/><path d="M8 21h8"/><path d="M12 14v.01"/></svg>',
            launcher: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><circle cx="9" cy="10" r="1.5"/><circle cx="12" cy="10" r="1.5"/><circle cx="15" cy="10" r="1.5"/><path d="M7 14h10"/></svg>',
            launcherClose: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
            more: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>',
            globe: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            globeAr: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><line x1="12" y1="2" x2="12" y2="22"/><path d="M4.93 4.93A10 10 0 0 1 12 2a10 10 0 0 1 7.07 2.93"/><path d="M4.93 19.07A10 10 0 0 0 12 22a10 10 0 0 0 7.07-2.93"/></svg>',
        };
        return icons[name] || '';
    }

    function createStyles() {
        var isDark = config.theme === 'dark';
        var primary = config.primaryColor;
        var bg = config.backgroundColor;
        var text = config.textColor;

        if (isDark) {
            bg = bg === '#ffffff' ? '#0f172a' : bg;
            text = text === '#111827' ? '#f1f5f9' : text;
        }

        var headerBg = primary;
        var inputBorder = isDark ? 'rgba(255,255,255,0.08)' : '#e5e7eb';
        var msgBgUser = primary;
        var msgBgAssistant = isDark ? '#1e293b' : '#f3f4f6';
        var bubbleBg = isDark ? '#1e293b' : '#ffffff';
        var shadowColor = isDark ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.08)';
        var inputBg = isDark ? '#1e293b' : '#f9fafb';
        var surfaceColor = isDark ? '#1e293b' : '#ffffff';
        var mutedColor = isDark ? '#64748b' : '#9ca3af';
        var textSecondary = isDark ? '#94a3b8' : '#6b7280';
        var borderLight = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
        var skeletonBg = isDark ? '#1e293b' : '#e5e7eb';
        var skeletonShine = isDark ? '#334155' : '#f3f4f6';
        var hoverBg = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';

        return [
            '@import url("https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Noto+Kufi+Arabic:wght@400;500;600;700&display=swap");',
            '* { box-sizing: border-box; margin: 0; padding: 0; }',
            ':host {',
            '  all: initial;',
            '  --ai-primary: ' + primary + ';',
            '  --ai-primary-rgb: ' + hexToRgb(primary) + ';',
            '  --ai-bg: ' + bg + ';',
            '  --ai-bg-rgb: ' + hexToRgb(bg) + ';',
            '  --ai-text: ' + text + ';',
            '  --ai-text-secondary: ' + textSecondary + ';',
            '  --ai-text-muted: ' + mutedColor + ';',
            '  --ai-border: ' + inputBorder + ';',
            '  --ai-shadow: ' + shadowColor + ';',
            '  --ai-radius: 16px;',
            '  --ai-radius-sm: 10px;',
            '  --ai-radius-xs: 8px;',
            '  --ai-launcher-size: 64px;',
            '  --ai-widget-width: ' + config.width + 'px;',
            '  --ai-widget-height: ' + config.height + 'px;',
            '  --ai-z-index: 2147483000;',
            '  --ai-font: "IBM Plex Sans Arabic", "Tajawal", "Cairo", "Noto Kufi Arabic", -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;',
            '  --ai-font-mono: "SF Mono", "Fira Code", "Fira Mono", Menlo, Consolas, "IBM Plex Mono Arabic", monospace;',
            '  --ai-transition: 0.3s cubic-bezier(0.22, 1, 0.36, 1);',
            '  --ai-transition-spring: 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);',
            '  font-family: var(--ai-font);',
            '}',
            '',
            '/* ===== LAUNCHER ===== */',
            '.ai-chat-bubble {',
            '  position: fixed; z-index: var(--ai-z-index) !important;',
            '  width: var(--ai-launcher-size); height: var(--ai-launcher-size); border-radius: 50%;',
            '  background: ' + headerBg + ';',
            '  color: #fff; cursor: pointer;',
            '  display: flex; align-items: center; justify-content: center;',
            '  box-shadow: 0 2px 12px rgba(0,0,0,0.08), 0 8px 32px ' + primary + '33;',
            '  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;',
            '  user-select: none; border: none;',
            '  overflow: hidden;',
            '}',
            '',
            '.ai-chat-bubble::before {',
            '  content: "";',
            '  position: absolute; inset: 0;',
            '  border-radius: 50%;',
            '  border: 2px solid rgba(255,255,255,0.15);',
            '  pointer-events: none;',
            '}',
            '',
            '.ai-chat-bubble::after {',
            '  content: "";',
            '  position: absolute; inset: 2px;',
            '  border-radius: 50%;',
            '  background: radial-gradient(circle at 30% 25%, rgba(255,255,255,0.2) 0%, transparent 60%);',
            '  pointer-events: none;',
            '}',
            '',
            '.ai-chat-bubble .ai-bubble-icon {',
            '  position: relative; z-index: 1;',
            '  display: flex; align-items: center; justify-content: center;',
            '  transition: transform 0.2s ease;',
            '}',
            '',
            '.ai-chat-bubble:hover {',
            '  transform: scale(1.05);',
            '  box-shadow: 0 4px 20px rgba(0,0,0,0.1), 0 12px 40px ' + primary + '44;',
            '}',
            '',
            '.ai-chat-bubble:active { transform: scale(0.92); }',
            '',
            '.ai-chat-bubble.open {',
            '  animation: none;',
            '  opacity: 0;',
            '  transform: scale(0.8);',
            '  pointer-events: none;',
            '}',
            '',
            '.ai-chat-bubble .ai-badge {',
            '  position: absolute; top: -3px; right: -3px;',
            '  min-width: 20px; height: 20px; border-radius: 10px;',
            '  background: #ef4444; color: #fff;',
            '  font-size: 10px; font-weight: 700; letter-spacing: 0.02em;',
            '  display: flex; align-items: center; justify-content: center;',
            '  padding: 0 5px;',
            '  box-shadow: 0 2px 8px rgba(239,68,68,0.4);',
            '  border: 2px solid ' + (isDark ? '#0f172a' : '#fff') + ';',
            '  animation: ai-badge-in 0.25s ease-out;',
            '  pointer-events: none;',
            '  z-index: 2;',
            '}',
            '',
            '',
            '',
            '',
            '',
            '@keyframes ai-badge-in {',
            '  0% { transform: scale(0); }',
            '  100% { transform: scale(1); }',
            '}',
            '',
            '@keyframes ai-pulse {',
            '  0% { box-shadow: 0 0 0 0 ' + primary + '44; }',
            '  70% { box-shadow: 0 0 0 14px rgba(var(--ai-primary-rgb), 0); }',
            '  100% { box-shadow: 0 0 0 0 rgba(var(--ai-primary-rgb), 0); }',
            '}',
            '',
            '@keyframes ai-glow {',
            '  0%, 100% { filter: brightness(1); }',
            '  50% { filter: brightness(1.15); }',
            '}',
            '',
            '.ai-chat-bubble.pos-bottom-right { bottom: 24px; right: 24px; }',
            '.ai-chat-bubble.pos-bottom-left { bottom: 24px; left: 24px; }',
            '.ai-chat-bubble.pos-top-right { top: 24px; right: 24px; }',
            '.ai-chat-bubble.pos-top-left { top: 24px; left: 24px; }',
            '',
            '/* ===== WINDOW CONTAINER ===== */',
            '.ai-chat-window {',
            '  position: fixed; z-index: calc(var(--ai-z-index) + 1) !important;',
            '  width: var(--ai-widget-width); height: var(--ai-widget-height);',
            '  max-height: 85vh; max-width: calc(100vw - 32px);',
            '  background: ' + bg + ';',
            '  border-radius: var(--ai-radius);',
            '  box-shadow: 0 0 0 1px ' + borderLight + ', 0 1px 4px ' + shadowColor + ', 0 16px 48px ' + shadowColor + ';',
            '  display: flex; flex-direction: column; overflow: hidden;',
            '  font-size: 14px; color: var(--ai-text);',
            '  transition: opacity 0.2s ease, transform 0.25s ease;',
            '  opacity: 0; transform: scale(0.95) translateY(12px);',
            '  pointer-events: none;',
            '  transform-origin: bottom right;',
            '  backdrop-filter: blur(24px);',
            '  -webkit-backdrop-filter: blur(24px);',
            '}',
            '',
            '.ai-chat-window.pos-bottom-right { bottom: 96px; right: 24px; transform-origin: bottom right; }',
            '.ai-chat-window.pos-bottom-left { bottom: 96px; left: 24px; transform-origin: bottom left; }',
            '.ai-chat-window.pos-top-right { top: 24px; right: 24px; transform-origin: top right; }',
            '.ai-chat-window.pos-top-left { top: 24px; left: 24px; transform-origin: top left; }',
            '',
            '.ai-chat-window.open {',
            '  opacity: 1; transform: scale(1) translateY(0);',
            '  pointer-events: all;',
            '}',
            '',
            '.ai-chat-window.fullscreen {',
            '  width: 100vw !important; height: 100vh !important;',
            '  max-width: 100vw !important; max-height: 100vh !important;',
            '  border-radius: 0 !important; bottom: 0 !important; right: 0 !important;',
            '  top: 0 !important; left: 0 !important;',
            '  backdrop-filter: blur(32px);',
            '  -webkit-backdrop-filter: blur(32px);',
            '}',
            '',
            '.ai-chat-window.minimized {',
            '  height: auto !important; max-height: 60px !important; overflow: hidden !important;',
            '}',
            '',
            '.ai-chat-window.minimized .ai-chat-messages,',
            '.ai-chat-window.minimized .ai-chat-input-container,',
            '.ai-chat-window.minimized .ai-chat-footer { display: none !important; }',
            '',
            '/* ===== HEADER ===== */',
            '.ai-chat-header {',
            '  padding: 14px 16px;',
            '  background: ' + headerBg + ';',
            '  color: #fff; display: flex; align-items: center;',
            '  justify-content: space-between; flex-shrink: 0;',
            '  position: relative; overflow: hidden;',
            '  cursor: pointer;',
            '  border-radius: 16px 16px 0 0;',
            '  min-height: 60px;',
            '  box-shadow: 0 1px 0 rgba(0,0,0,0.06);',
            '}',
            '',
            '.ai-chat-window.fullscreen .ai-chat-header { border-radius: 0; }',
            '',
            '.ai-chat-header::before {',
            '  content: ""; position: absolute; top: -60%; right: -15%;',
            '  width: 160px; height: 160px; border-radius: 50%;',
            '  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 65%);',
            '  pointer-events: none;',
            '}',
            '',
            '.ai-chat-header::after {',
            '  content: ""; position: absolute; bottom: -50%; left: -10%;',
            '  width: 100px; height: 100px; border-radius: 50%;',
            '  background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 65%);',
            '  pointer-events: none;',
            '}',
            '',
            '.ai-chat-header-left {',
            '  display: flex; align-items: center; gap: 12px; z-index: 1;',
            '  min-width: 0; flex: 1;',
            '}',
            '',
'.ai-chat-avatar {',
'  width: 36px; height: 36px; border-radius: 50%;',
'  background: linear-gradient(135deg, rgba(0,0,0,0.15), rgba(0,0,0,0.08));',
'  display: flex; align-items: center; justify-content: center;',
'  font-size: 18px; flex-shrink: 0;',
'  position: relative;',
'  border: 2px solid rgba(255,255,255,0.2);',
'}',
            '',
            '.ai-chat-avatar img {',
            '  width: 100%; height: 100%; object-fit: cover; border-radius: 50%;',
            '}',
            '',
            '.ai-chat-avatar svg {',
            '  width: 20px; height: 20px;',
            '}',
            '',
'.ai-chat-status {',
'  position: absolute; bottom: -3px; right: -3px;',
'  width: 14px; height: 14px; border-radius: 50%;',
'  border: 2.5px solid ' + (isDark ? '#0f172a' : '#fff') + ';',
'  z-index: 3;',
'}',
            '',
            '.ai-chat-status.online { background: #22c55e; }',
            '.ai-chat-status.online::after {',
            '  content: ""; position: absolute; inset: -1px; border-radius: 50%;',
            '  background: rgba(34,197,94,0.2);',
            '  animation: ai-status-pulse 2.5s ease-out infinite;',
            '}',
            '',
            '.ai-chat-status.away { background: #f59e0b; }',
            '.ai-chat-status.busy { background: #ef4444; }',
            '.ai-chat-status.offline { background: #94a3b8; }',
            '',
            '@keyframes ai-status-pulse {',
            '  0% { transform: scale(1); opacity: 0.5; }',
            '  100% { transform: scale(1.5); opacity: 0; }',
            '}',
            '',
            '.ai-chat-header-info { min-width: 0; flex: 1; }',
            '.ai-chat-header-info h3 {',
            '  font-size: 15px; font-weight: 600; letter-spacing: -0.01em;',
            '  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
            '  line-height: 1.35;',
            '}',
            '.ai-chat-header-info p {',
            '  font-size: 11px; opacity: 0.8; margin-top: 1px;',
            '  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
            '}',
            '',
            '.ai-chat-header-actions {',
            '  display: flex; align-items: center; gap: 2px; z-index: 1;',
            '  flex-shrink: 0; margin-left: 8px;',
            '}',
            '',
            '.ai-chat-header-btn {',
            '  background: rgba(255,255,255,0.08); border: none; color: #fff;',
            '  width: 32px; height: 32px; border-radius: 8px; cursor: pointer;',
            '  font-size: 16px; display: flex; align-items: center; justify-content: center;',
            '  transition: background 0.15s ease, transform 0.15s ease;',
            '  backdrop-filter: blur(4px);',
            '  -webkit-backdrop-filter: blur(4px);',
            '}',
            '',
            '.ai-chat-header-btn:last-child { margin-right: 0; }',
            '.ai-chat-header-lang { font-size: 11px; font-weight: 700; letter-spacing: 0.02em; }',
            '.ai-chat-header-btn:hover { background: rgba(255,255,255,0.15); }',
            '.ai-chat-header-btn:active { background: rgba(255,255,255,0.05); }',
            '.ai-chat-header-btn:focus-visible { outline: 2px solid rgba(255,255,255,0.5); outline-offset: 2px; }',
            '',
            '/* ===== MORE DROPDOWN ===== */',
            '.ai-chat-more-wrap { position: relative; }',
            '.ai-chat-more-menu { position: absolute; top: calc(100% + 6px); right: 0; min-width: 200px; background: var(--ai-bg); border: 1px solid var(--ai-border); border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.06); padding: 6px; z-index: 2147483002 !important; opacity: 0; transform: translateY(-4px) scale(0.96); pointer-events: none; transition: opacity 0.15s ease, transform 0.15s ease; }',
            '.ai-chat-more-menu.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }',
            '.ai-chat-more-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; color: var(--ai-text); background: transparent; border: none; width: 100%; text-align: left; transition: background 0.1s ease; font-family: inherit; }',
            '.ai-chat-more-item:hover { background: rgba(var(--ai-primary-rgb), 0.08); color: var(--ai-primary); }',
            '.ai-chat-more-item svg { flex-shrink: 0; width: 16px; height: 16px; }',
            '.ai-chat-more-divider { height: 1px; background: var(--ai-border); margin: 4px 8px; }',
':host-context([dir="rtl"]) .ai-chat-more-menu, [dir="rtl"] .ai-chat-more-menu { right: auto; left: 0; }',
':host-context([dir="rtl"]) .ai-chat-more-item, [dir="rtl"] .ai-chat-more-item { text-align: right; }',
            '',
            '/* ===== CONFIRM OVERLAY ===== */',
            '.ai-confirm-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 2147483003 !important; border-radius: 16px; opacity: 0; pointer-events: none; transition: opacity 0.2s; }',
            '.ai-confirm-overlay.open { opacity: 1; pointer-events: auto; }',
            '.ai-confirm-box { background: var(--ai-bg); border-radius: 14px; padding: 20px 24px; max-width: 260px; box-shadow: 0 4px 24px rgba(0,0,0,0.12), 0 0 0 1px var(--ai-border); text-align: center; }',
            '.ai-confirm-text { font-size: 14px; color: var(--ai-text); margin-bottom: 16px; line-height: 1.5; }',
            '.ai-confirm-actions { display: flex; gap: 8px; justify-content: center; }',
            '.ai-confirm-btn { padding: 7px 18px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.12s; font-family: inherit; }',
            '.ai-confirm-btn-cancel { background: var(--ai-border); color: var(--ai-text-secondary); }',
            '.ai-confirm-btn-cancel:hover { background: var(--ai-text-muted); }',
            '.ai-confirm-btn-danger { background: #ef4444; color: #fff; }',
            '.ai-confirm-btn-danger:hover { background: #dc2626; }',
            '',
            '/* ===== MESSAGES AREA ===== */',
            '.ai-chat-messages {',
            '  flex: 1; overflow-y: auto; padding: 20px 20px 24px;',
            '  display: flex; flex-direction: column; gap: 14px;',
            '  scroll-behavior: smooth;',
            '  background: ' + bg + ';',
            '}',
            '',
            '.ai-chat-messages::-webkit-scrollbar { width: 4px; }',
            '.ai-chat-messages::-webkit-scrollbar-track { background: transparent; }',
            '.ai-chat-messages::-webkit-scrollbar-thumb {',
            '  background: ' + (isDark ? '#475569' : '#cbd5e1') + '; border-radius: 4px;',
            '}',
            '.ai-chat-messages::-webkit-scrollbar-thumb:hover {',
            '  background: ' + (isDark ? '#64748b' : '#94a3b8') + ';',
            '}',
            '',
            '/* ===== MESSAGE BUBBLES ===== */',
            '.ai-msg {',
            '  max-width: 85%; display: flex; flex-direction: column;',
            '  animation: ai-msg-in 0.25s ease-out;',
            '  position: relative;',
            '}',
            '',
            '@keyframes ai-msg-in {',
            '  from { opacity: 0; transform: translateY(6px); }',
            '  to { opacity: 1; transform: translateY(0); }',
            '}',
            '',
            '.ai-msg-user { align-self: flex-end; }',
            '.ai-msg-assistant { align-self: flex-start; }',
            '',
            '.ai-msg-bubble {',
            '  padding: 10px 16px; border-radius: 18px; line-height: 1.6;',
            '  word-wrap: break-word; position: relative;',
            '  font-size: 14px;',
            '}',
            '',
            '.ai-msg-user .ai-msg-bubble {',
            '  background: ' + msgBgUser + '; color: #fff;',
            '  border-bottom-right-radius: 6px;',
            '  box-shadow: 0 1px 4px rgba(0,0,0,0.08);',
            '}',
            '',
            '.ai-msg-assistant .ai-msg-bubble {',
            '  background: ' + msgBgAssistant + '; color: var(--ai-text);',
            '  border-bottom-left-radius: 6px;',
            '  box-shadow: 0 1px 2px rgba(0,0,0,0.04);',
            '}',
            '',
            '.ai-msg-bubble li {',
            '  margin-left: 16px; padding: 1px 0;',
            '}',
            '',
            '.ai-msg-bubble p { margin: 6px 0; }',
            '.ai-msg-bubble p:first-child { margin-top: 0; }',
            '.ai-msg-bubble p:last-child { margin-bottom: 0; }',
            '',
            '.ai-msg-bubble pre {',
            '  background: ' + (isDark ? '#0f172a' : '#e2e8f0') + ';',
            '  padding: 12px 14px; border-radius: var(--ai-radius-xs); overflow-x: auto;',
            '  margin: 8px 0; font-size: 13px; line-height: 1.5;',
            '  border: 1px solid ' + (isDark ? '#1e293b' : '#e2e8f0') + ';',
            '}',
            '',
            '.ai-msg-bubble code {',
            '  font-family: var(--ai-font-mono); font-size: 13px;',
            '}',
            '',
            '.ai-msg-bubble code:not(pre code) {',
            '  background: ' + (isDark ? '#1e293b' : '#e2e8f0') + '; padding: 2px 7px; border-radius: 4px; font-size: 13px;',
            '}',
            '',
            '.ai-code-block {',
            '  position: relative; margin: 10px 0;',
            '  background: ' + (isDark ? '#0f172a' : '#e2e8f0') + ';',
            '  border-radius: var(--ai-radius-xs); overflow: hidden;',
            '  border: 1px solid ' + (isDark ? '#1e293b' : '#d1d5db') + ';',
            '}',
            '',
            '.ai-code-lang {',
            '  display: block; padding: 6px 12px; font-size: 11px;',
            '  text-transform: uppercase; letter-spacing: 0.05em;',
            '  background: ' + (isDark ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.03)') + ';',
            '  color: ' + mutedColor + ';',
            '  border-bottom: 1px solid ' + (isDark ? '#1e293b' : '#d1d5db') + ';',
            '}',
            '',
            '.ai-code-copy {',
            '  position: absolute; top: 6px; right: 6px;',
            '  background: ' + (isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)') + ';',
            '  border: none; color: ' + mutedColor + ';',
            '  width: 28px; height: 28px; border-radius: 6px;',
            '  cursor: pointer; display: flex; align-items: center; justify-content: center;',
            '  transition: background 0.15s, color 0.15s;',
            '  opacity: 0;',
            '}',
            '',
            '.ai-code-block:hover .ai-code-copy { opacity: 1; }',
            '.ai-code-copy:hover { background: ' + (isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)') + '; color: var(--ai-text); }',
            '.ai-code-copy.copied { opacity: 1 !important; color: #22c55e; }',
            '',
            '.ai-msg-bubble h2, .ai-msg-bubble h3, .ai-msg-bubble h4 {',
            '  margin: 12px 0 6px; line-height: 1.3;',
            '}',
            '',
            '.ai-msg-bubble h2 { font-size: 16px; }',
            '.ai-msg-bubble h3 { font-size: 15px; }',
            '.ai-msg-bubble h4 { font-size: 14px; }',
            '',
            '.ai-msg-meta {',
            '  display: flex; align-items: center; gap: 4px;',
            '  margin-top: 4px; padding: 0 4px;',
            '  position: relative;',
            '}',
            '',
            '.ai-msg-time { font-size: 10px; color: var(--ai-text-muted); opacity: 0.6; }',
            '.ai-msg-user .ai-msg-meta { justify-content: flex-end; }',
            '.ai-msg-assistant .ai-msg-meta { justify-content: flex-start; flex-wrap: wrap; }',
            '',
            '.ai-msg-action-btn {',
            '  font-size: 0; background: none; border: none; cursor: pointer;',
            '  padding: 4px; border-radius: 6px;',
            '  color: ' + mutedColor + ';',
            '  opacity: 0; transition: opacity 0.2s, background 0.2s, color 0.2s;',
            '  display: inline-flex; align-items: center; justify-content: center;',
            '  line-height: 1;',
            '}',
            '',
            '.ai-msg:hover .ai-msg-action-btn { opacity: 0.5; }',
            '.ai-msg-action-btn:hover { opacity: 1 !important; background: ' + hoverBg + '; color: var(--ai-text); }',
            '.ai-msg-action-btn.active { opacity: 1 !important; color: ' + primary + '; }',
            '.ai-msg-action-btn.copied { opacity: 1 !important; color: #22c55e; }',
            '.ai-msg-action-btn:focus-visible { outline: 2px solid ' + primary + '; outline-offset: 2px; opacity: 1; }',
            '',
            '.ai-msg-feedback {',
            '  display: inline-flex; align-items: center; gap: 1px;',
            '  margin-left: 2px;',
            '}',
            '',
            '/* ===== THINKING INDICATOR ===== */',
            '.ai-thinking-indicator {',
            '  display: flex; align-items: center; gap: 8px;',
            '  padding: 10px 14px; align-self: flex-start;',
            '  animation: ai-msg-in 0.2s ease-out;',
            '  color: var(--ai-text-secondary); font-size: 13px;',
            '  background: ' + msgBgAssistant + '; border-radius: 16px; border-bottom-left-radius: 5px;',
            '}',
            '',
            '.ai-thinking-dots { display: flex; gap: 4px; align-items: center; padding: 2px 0; }',
            '.ai-thinking-dots span {',
            '  width: 7px; height: 7px; background: ' + primary + '; border-radius: 50%;',
            '  animation: ai-think-bounce 1.4s infinite ease-in-out both;',
            '}',
            '.ai-thinking-dots span:nth-child(1) { animation-delay: 0s; }',
            '.ai-thinking-dots span:nth-child(2) { animation-delay: 0.16s; }',
            '.ai-thinking-dots span:nth-child(3) { animation-delay: 0.32s; }',
            '',
            '@keyframes ai-think-bounce {',
            '  0%, 80%, 100% { transform: scale(0.6); opacity: 0.3; }',
            '  40% { transform: scale(1); opacity: 1; }',
            '}',
            '',
            '/* ===== LOADING SKELETON ===== */',
            '.ai-loading-skeleton {',
            '  padding: 20px; display: flex; flex-direction: column; gap: 14px;',
            '  animation: ai-msg-in 0.3s ease-out;',
            '}',
            '',
            '.ai-skeleton-line {',
            '  height: 12px; border-radius: 6px;',
            '  background: linear-gradient(90deg, ' + skeletonBg + ' 25%, ' + skeletonShine + ' 50%, ' + skeletonBg + ' 75%);',
            '  background-size: 200% 100%;',
            '  animation: ai-shimmer 1.5s infinite;',
            '}',
            '',
            '.ai-skeleton-line:nth-child(1) { width: 75%; }',
            '.ai-skeleton-line:nth-child(2) { width: 100%; }',
            '.ai-skeleton-line:nth-child(3) { width: 60%; }',
            '.ai-skeleton-line:nth-child(4) { width: 85%; }',
            '.ai-skeleton-line:nth-child(5) { width: 45%; }',
            '.ai-skeleton-line:last-child { width: 30%; }',
            '',
            '@keyframes ai-shimmer {',
            '  0% { background-position: 200% 0; }',
            '  100% { background-position: -200% 0; }',
            '}',
            '',
            '/* ===== EMPTY STATE ===== */',
            '.ai-empty-state {',
            '  flex: 1; display: flex; flex-direction: column; align-items: center;',
            '  justify-content: center; padding: 40px 24px 28px; text-align: center;',
            '  gap: 10px;',
            '  animation: ai-fade-in-up 0.4s ease-out;',
            '}',
            '',
            '.ai-empty-icon {',
            '  width: 64px; height: 64px; border-radius: 50%;',
            '  background: linear-gradient(135deg, ' + primary + '18, ' + primary + '08);',
            '  display: flex; align-items: center; justify-content: center;',
            '  font-size: 28px; margin-bottom: 4px;',
            '  position: relative;',
            '}',
            '',
            '.ai-empty-icon svg {',
            '  width: 30px; height: 30px;',
            '  stroke: ' + primary + ';',
            '}',
            '',
            '',
            '',
            '.ai-empty-title { font-size: 17px; font-weight: 600; color: var(--ai-text); line-height: 1.3; letter-spacing: -0.01em; }',
            '.ai-empty-desc { font-size: 13px; color: var(--ai-text-secondary); max-width: 260px; line-height: 1.55; }',
            '',
            '.ai-suggested-prompts {',
            '  display: flex; flex-wrap: wrap; gap: 6px;',
            '  justify-content: center; margin-top: 16px;',
            '  max-width: 300px;',
            '}',
            '',
            '.ai-suggested-prompt {',
            '  padding: 8px 14px; border-radius: 8px;',
            '  background: ' + (isDark ? '#1e293b' : '#f9fafb') + ';',
            '  border: 1px solid ' + inputBorder + ';',
            '  color: var(--ai-text-secondary); cursor: pointer; font-size: 12px;',
            '  transition: all 0.15s ease; line-height: 1.45;',
            '  font-family: inherit;',
            '  font-weight: 500;',
            '}',
            '',
            '.ai-suggested-prompt:hover {',
            '  border-color: ' + primary + '66;',
            '  background: ' + primary + '0c;',
            '  color: var(--ai-text);',
            '  box-shadow: 0 1px 4px rgba(0,0,0,0.04);',
            '  transform: translateY(-1px);',
            '}',
            '',
            '.ai-suggested-prompt:focus-visible {',
            '  outline: 2px solid ' + primary + '; outline-offset: 2px;',
            '}',
            '',
            '@keyframes ai-fade-in-up {',
            '  from { opacity: 0; transform: translateY(16px); }',
            '  to { opacity: 1; transform: translateY(0); }',
            '}',
            '',
            '',
            '',
            '/* ===== ERROR STATE ===== */',
            '.ai-error-state {',
            '  margin: 8px 16px; padding: 10px 12px; border-radius: 10px;',
            '  background: ' + (isDark ? '#450a0a' : '#fef2f2') + ';',
            '  border: 1px solid ' + (isDark ? '#7f1d1d' : '#fecaca') + ';',
            '  color: ' + (isDark ? '#fca5a5' : '#dc2626') + '; font-size: 13px; line-height: 1.5;',
            '  display: flex; align-items: center; gap: 10px;',
            '  animation: ai-msg-in 0.2s ease-out;',
            '}',
            '',
            '.ai-error-retry {',
            '  margin-left: auto; padding: 5px 12px; border-radius: 8px;',
            '  border: 1px solid ' + (isDark ? '#7f1d1d' : '#fca5a5') + ';',
            '  background: ' + (isDark ? 'transparent' : '#fff') + ';',
            '  color: ' + (isDark ? '#fca5a5' : '#dc2626') + ';',
            '  cursor: pointer; font-size: 12px; font-weight: 600; white-space: nowrap;',
            '  transition: background 0.15s; flex-shrink: 0;',
            '  display: flex; align-items: center; gap: 5px;',
            '  font-family: inherit;',
            '}',
            '',
            '.ai-error-retry:hover { background: ' + (isDark ? '#7f1d1d' : '#fef2f2') + '; }',
            '.ai-error-retry:focus-visible { outline: 2px solid #ef4444; outline-offset: 2px; }',
            '',
            '/* ===== INPUT AREA ===== */',
            '.ai-chat-input-container {',
            '  padding: 12px 16px 12px; border-top: 1px solid var(--ai-border);',
            '  background: ' + bg + '; flex-shrink: 0;',
            '}',
            '',
            '.ai-chat-input-row {',
            '  display: flex; align-items: flex-end; gap: 8px;',
            '}',
            '',
            '.ai-chat-textarea {',
            '  flex: 1; resize: none; border: 1px solid var(--ai-border);',
            '  border-radius: 12px; padding: 10px 14px; font-size: 14px;',
            '  font-family: inherit; color: var(--ai-text); background: ' + inputBg + ';',
            '  outline: none; max-height: 120px; min-height: 42px; line-height: 1.5;',
            '  transition: border-color 0.15s ease, box-shadow 0.15s ease;',
            '}',
            '',
            '.ai-chat-textarea:focus {',
            '  border-color: var(--ai-primary);',
            '  box-shadow: 0 0 0 2px rgba(var(--ai-primary-rgb), 0.1);',
            '}',
            '',
            '.ai-chat-textarea::placeholder { color: ' + (isDark ? '#64748b' : '#94a3b8') + '; }',
            '',
            '.ai-chat-textarea:disabled { opacity: 0.5; cursor: not-allowed; }',
            '',
            '.ai-chat-send {',
            '  width: 42px; height: 42px; border-radius: 12px;',
            '  background: var(--ai-primary); color: #fff; border: none; cursor: pointer;',
            '  display: flex; align-items: center; justify-content: center;',
            '  font-size: 18px; flex-shrink: 0;',
            '  transition: opacity 0.15s ease, background 0.15s ease;',
            '}',
            '',
            '.ai-chat-send:hover:not(:disabled) { background: color-mix(in srgb, var(--ai-primary) 85%, white); }',
            '.ai-chat-send:active:not(:disabled) { background: color-mix(in srgb, var(--ai-primary) 90%, black); }',
            '.ai-chat-send:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }',
            '.ai-chat-send:focus-visible { outline: 2px solid var(--ai-primary); outline-offset: 2px; }',
            '',
            '.ai-chat-send.loading { animation: ai-send-pulse 0.8s ease-in-out infinite; }',
            '',
            '@keyframes ai-send-pulse {',
            '  0%, 100% { transform: scale(1); opacity: 1; }',
            '  50% { transform: scale(0.92); opacity: 0.7; }',
            '}',
            '',
            '/* ===== STOP BUTTON ===== */',
            '.ai-chat-stop-btn {',
            '  align-self: center; margin-bottom: 8px;',
            '  padding: 6px 14px; border-radius: 8px;',
            '  border: 1px solid var(--ai-border); background: ' + bg + ';',
            '  color: var(--ai-text-secondary); cursor: pointer; font-size: 12px; font-weight: 500;',
            '  display: flex; align-items: center; gap: 6px;',
            '  transition: all 0.15s ease; animation: ai-fade-in-up 0.15s ease-out;',
            '  font-family: inherit;',
            '}',
            '',
            '.ai-chat-stop-btn:hover {',
            '  background: ' + (isDark ? '#1e293b' : '#f3f4f6') + ';',
            '  border-color: ' + (isDark ? '#475569' : '#d1d5db') + ';',
            '  color: var(--ai-text);',
            '}',
            '.ai-chat-stop-btn:focus-visible { outline: 2px solid var(--ai-primary); outline-offset: 2px; }',
            '',
            '/* ===== STREAMING CURSOR ===== */',
            '.ai-chat-streaming-cursor::after {',
            '  content: "";',
            '  display: inline-block;',
            '  width: 2px; height: 14px;',
            '  background: var(--ai-primary);',
            '  margin-left: 1px;',
            '  border-radius: 1px;',
            '  animation: ai-blink 0.9s step-end infinite;',
            '  vertical-align: text-bottom;',
            '}',
            '',
            '@keyframes ai-blink {',
            '  0%, 100% { opacity: 1; }',
            '  50% { opacity: 0; }',
            '}',
            '',
            '/* ===== FOOTER ===== */',
            '.ai-chat-footer {',
            '  padding: 8px 16px 10px; text-align: center; font-size: 10px;',
            '  color: var(--ai-text-muted); flex-shrink: 0;',
            '  opacity: 0.4;',
            '  letter-spacing: 0.02em;',
            '}',
            '',
            '/* ===== RTL SUPPORT ===== */',
            ':host-context([dir="rtl"]), .ai-chat-window[dir="rtl"] {',
            '  --ai-font: "IBM Plex Sans Arabic", "Tajawal", "Cairo", "Noto Kufi Arabic", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;',
            '}',
            ':host-context([dir="rtl"]) .ai-msg-user { align-self: flex-start; }',
            ':host-context([dir="rtl"]) .ai-msg-assistant { align-self: flex-end; }',
            ':host-context([dir="rtl"]) .ai-msg-user .ai-msg-bubble { border-bottom-right-radius: 18px; border-bottom-left-radius: 6px; }',
            ':host-context([dir="rtl"]) .ai-msg-assistant .ai-msg-bubble { border-bottom-left-radius: 18px; border-bottom-right-radius: 6px; }',
            ':host-context([dir="rtl"]) .ai-chat-header-left { flex-direction: row-reverse; }',
            ':host-context([dir="rtl"]) .ai-chat-header-actions { margin-left: 0; margin-right: 8px; }',
            ':host-context([dir="rtl"]) .ai-msg-feedback { margin-left: 0; margin-right: 2px; }',
            ':host-context([dir="rtl"]) .ai-chat-textarea { direction: rtl; }',
            ':host-context([dir="rtl"]) .ai-msg-meta { flex-direction: row-reverse; }',
            ':host-context([dir="rtl"]) .ai-code-copy { right: auto; left: 6px; }',
            ':host-context([dir="rtl"]) .ai-msg-bubble li { margin-left: 0; margin-right: 16px; }',
            ':host-context([dir="rtl"]) .ai-thinking-indicator { align-self: flex-end; border-bottom-left-radius: 18px; border-bottom-right-radius: 6px; }',
            ':host-context([dir="rtl"]) .ai-suggested-prompts { flex-direction: row-reverse; }',
            ':host-context([dir="rtl"]) .ai-empty-state { direction: rtl; }',
            ':host-context([dir="rtl"]) .ai-chat-more-divider { margin: 4px 8px; }',
            ':host-context([dir="rtl"]) .ai-chat-input-row { flex-direction: row-reverse; }',
            '',
            '[dir="rtl"] .ai-msg-user { align-self: flex-start; }',
            '[dir="rtl"] .ai-msg-assistant { align-self: flex-end; }',
            '[dir="rtl"] .ai-msg-user .ai-msg-bubble { border-bottom-right-radius: 18px; border-bottom-left-radius: 6px; }',
            '[dir="rtl"] .ai-msg-assistant .ai-msg-bubble { border-bottom-left-radius: 18px; border-bottom-right-radius: 6px; }',
            '[dir="rtl"] .ai-chat-header-left { flex-direction: row-reverse; }',
            '[dir="rtl"] .ai-chat-header-actions { margin-left: 0; margin-right: 8px; }',
            '[dir="rtl"] .ai-msg-feedback { margin-left: 0; margin-right: 2px; }',
            '[dir="rtl"] .ai-chat-textarea { direction: rtl; }',
            '[dir="rtl"] .ai-msg-meta { flex-direction: row-reverse; }',
            '[dir="rtl"] .ai-code-copy { right: auto; left: 6px; }',
            '[dir="rtl"] .ai-msg-bubble li { margin-left: 0; margin-right: 16px; }',
            '[dir="rtl"] .ai-chat-bubble.pos-bottom-right { right: auto; left: 24px; }',
            '[dir="rtl"] .ai-chat-bubble.pos-bottom-left { left: auto; right: 24px; }',
            '[dir="rtl"] .ai-chat-window.pos-bottom-right { right: auto; left: 24px; }',
            '[dir="rtl"] .ai-chat-window.pos-bottom-left { left: auto; right: 24px; }',
            '[dir="rtl"] .ai-thinking-indicator { align-self: flex-end; border-bottom-left-radius: 18px; border-bottom-right-radius: 6px; }',
            '[dir="rtl"] .ai-suggested-prompts { flex-direction: row-reverse; }',
            '[dir="rtl"] .ai-empty-state { direction: rtl; }',
            '[dir="rtl"] .ai-chat-more-divider { margin: 4px 8px; }',
            '[dir="rtl"] .ai-chat-input-row { flex-direction: row-reverse; }',
            '',
            '/* ===== MOBILE RESPONSIVE ===== */',
            '@media (max-width: 768px) {',
            '  .ai-chat-avatar { width: 34px; height: 34px; font-size: 16px; }',
            '  .ai-chat-avatar svg { width: 18px; height: 18px; }',
            '  .ai-chat-header { padding: 12px 14px; min-height: 58px; }',
            '  .ai-chat-header-left { gap: 10px; }',
            '  .ai-chat-header-info h3 { font-size: 14px; }',
            '  .ai-chat-header-info p { font-size: 11px; }',
            '  .ai-chat-header-btn { width: 30px; height: 30px; border-radius: 7px; font-size: 14px; }',
            '  .ai-chat-header-btn svg { width: 15px; height: 15px; }',
            '  .ai-msg-bubble { padding: 10px 14px; font-size: 14px; }',
            '  .ai-chat-messages { padding: 16px 12px; }',
            '}',
            '',
            '@media (max-width: 480px) {',
            '  .ai-chat-window {',
            '    width: 100% !important; max-width: 100% !important;',
            '    height: 85dvh !important; max-height: 85dvh !important;',
            '    border-radius: 20px 20px 0 0 !important; bottom: 0 !important;',
            '    right: 0 !important; top: auto !important; left: 0 !important;',
            '    transform: translateY(100%);',
            '    transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s ease;',
            '  }',
            '',
            '  .ai-chat-window.open {',
            '    transform: translateY(0);',
            '    border-radius: 20px 20px 0 0 !important;',
            '  }',
            '',
            '  .ai-chat-window.fullscreen {',
            '    height: 100dvh !important; max-height: 100dvh !important;',
            '    border-radius: 0 !important;',
            '    top: 0 !important;',
            '  }',
            '',
            '  .ai-chat-window.minimized {',
            '    transform: translateY(0) !important;',
            '  }',
            '',
            '  .ai-chat-bubble {',
            '    width: 60px; height: 60px;',
            '    font-size: 22px;',
            '  }',
            '',
            '  .ai-chat-bubble.pos-bottom-right { bottom: 16px; right: 16px; }',
            '  .ai-chat-bubble.pos-bottom-left { bottom: 16px; left: 16px; }',
            '',
            '  .ai-msg { max-width: 92%; }',
            '',
            '  .ai-chat-header { padding: 10px 12px; border-radius: 20px 20px 0 0; min-height: 54px; }',
            '  .ai-chat-header-info h3 { font-size: 14px; }',
            '  .ai-chat-header-info p { font-size: 11px; }',
            '  .ai-chat-header-btn { width: 28px; height: 28px; border-radius: 6px; }',
            '  .ai-chat-header-btn svg { width: 14px; height: 14px; }',
            '',
            '  .ai-chat-messages { padding: 12px 10px; gap: 10px; }',
            '',
            '  .ai-chat-input-container { padding: 8px 10px 10px; }',
            '  .ai-chat-textarea { font-size: 15px; padding: 9px 12px; }',
            '  .ai-chat-send { width: 40px; height: 40px; border-radius: 12px; }',
            '',
            '  .ai-empty-state { padding: 24px 16px 20px; }',
            '  .ai-empty-icon { width: 60px; height: 60px; font-size: 24px; }',
            '  .ai-empty-title { font-size: 16px; }',
            '  .ai-empty-desc { font-size: 13px; }',
            '',
            '  .ai-chat-bubble::before { border-width: 1.5px; }',
            '}',
            '',
            '@media (max-width: 380px) {',
            '  .ai-chat-header { padding: 8px 10px; min-height: 48px; }',
            '  .ai-chat-header-left { gap: 8px; }',
            '  .ai-chat-avatar { width: 30px; height: 30px; font-size: 14px; }',
            '  .ai-chat-avatar svg { width: 16px; height: 16px; }',
            '  .ai-chat-header-info h3 { font-size: 13px; }',
            '  .ai-chat-header-btn { width: 26px; height: 26px; border-radius: 6px; }',
            '  .ai-chat-header-btn svg { width: 13px; height: 13px; }',
            '  .ai-chat-messages { padding: 10px 8px; gap: 8px; }',
            '  .ai-chat-input-container { padding: 6px 8px 8px; }',
            '  .ai-chat-textarea { font-size: 14px; padding: 8px 10px; min-height: 38px; }',
            '  .ai-chat-send { width: 36px; height: 36px; border-radius: 10px; }',
            '  .ai-msg { max-width: 95%; }',
            '  .ai-msg-bubble { padding: 8px 12px; font-size: 13px; }',
            '  .ai-empty-state { padding: 20px 12px 16px; }',
            '  .ai-empty-desc { max-width: 240px; }',
            '}',
            '',
            '/* ===== REDUCED MOTION ===== */',
            '@media (prefers-reduced-motion: reduce) {',
            '  .ai-chat-bubble,',
            '  .ai-chat-window,',
            '  .ai-msg,',
            '  .ai-empty-state,',
            '  .ai-chat-status.online::after {',
            '    animation: none !important;',
            '    transition: none !important;',
            '  }',
            '}',
        ].join('\n');
    }

    function hexToRgb(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result
            ? parseInt(result[1], 16) + ', ' + parseInt(result[2], 16) + ', ' + parseInt(result[3], 16)
            : '81, 150, 243';
    }

    function buildBubble() {
        bubble = document.createElement('button');
        bubble.className = 'ai-chat-bubble pos-' + config.position;
        bubble.setAttribute('dir', currentLang === 'ar' ? 'rtl' : 'ltr');
        bubble.setAttribute('aria-label', __('openChat', 'Open chat'));
        bubble.setAttribute('type', 'button');

        var iconHtml = config.avatar
            ? '<img src="' + escapeHtml(config.avatar) + '" alt="Chat" style="width:26px;height:26px;border-radius:50%;">'
            : '<span class="ai-bubble-icon">' + svgIcon('launcher') + '</span>';

        bubble.innerHTML = iconHtml;

        bubble.addEventListener('click', toggle);

        return bubble;
    }

    function updateBadge(count) {
        unreadCount = count;
        var existing = bubble.querySelector('.ai-badge');
        if (existing) existing.remove();
        if (count > 0) {
            var badge = document.createElement('span');
            badge.className = 'ai-badge';
            badge.textContent = count > 99 ? '99+' : count;
            bubble.appendChild(badge);
        }
    }

    function toggleLanguage() {
        currentLang = currentLang === 'ar' ? 'en' : 'ar';
        setStoredLanguage(currentLang);
        applyLanguage();
    }

    function swapPosition(pos) {
        if (pos.indexOf('bottom-right') !== -1) return pos.replace('bottom-right', 'bottom-left');
        if (pos.indexOf('bottom-left') !== -1) return pos.replace('bottom-left', 'bottom-right');
        if (pos.indexOf('top-right') !== -1) return pos.replace('top-right', 'top-left');
        if (pos.indexOf('top-left') !== -1) return pos.replace('top-left', 'top-right');
        return pos;
    }

    function applyLanguage() {
        isRtl = currentLang === 'ar';

        if (container) {
            container.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
        }

        if (windowEl) {
            windowEl.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
            var currentPos = 'pos-' + config.position;
            var newPos = isRtl ? swapPosition(currentPos) : currentPos;
            windowEl.className = windowEl.className.replace(/pos-\S+/g, newPos);
        }

        if (bubble) {
            bubble.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
            var currentPos = 'pos-' + config.position;
            var newPos = isRtl ? swapPosition(currentPos) : currentPos;
            bubble.className = bubble.className.replace(/pos-\S+/g, newPos);
            bubble.setAttribute('aria-label', isRtl ? __('closeChat', 'Close chat') : (STATE.isOpen ? __('closeChat', 'Close chat') : __('openChat', 'Open chat')));
        }

        if (!windowEl) return;

        var headerEl = windowEl.querySelector('.ai-chat-header');
        if (headerEl) {
            headerEl.setAttribute('aria-label', __('minimizeToggle', 'Toggle minimize'));
        }

        var headerTitle = windowEl.querySelector('.ai-chat-header-info h3');
        if (headerTitle) {
            headerTitle.textContent = currentLang === 'ar' ? __('aiTitle', config.title) : config.title;
        }

        var headerSubtitle = windowEl.querySelector('.ai-chat-header-info p');
        if (headerSubtitle) {
            headerSubtitle.textContent = __('online', 'Online');
        }

        var langBtn = windowEl.querySelector('.ai-chat-lang-btn');
        if (langBtn) {
            langBtn.innerHTML = (currentLang === 'ar' ? svgIcon('globeAr') : svgIcon('globe')) + '<span class="ai-chat-header-lang">' + (currentLang === 'ar' ? 'EN' : 'ع') + '</span>';
            langBtn.setAttribute('title', __('toggleLanguage', 'Switch language'));
            langBtn.setAttribute('aria-label', __('toggleLanguage', 'Switch language'));
        }

        var langMenuItem = windowEl.querySelector('.ai-chat-more-item[data-action="lang"]');
        if (langMenuItem) {
            var langItemIcon = langMenuItem.querySelector('svg');
            var langItemSpan = langMenuItem.querySelector('span');
            if (langItemIcon) {
                langMenuItem.innerHTML = (currentLang === 'ar' ? svgIcon('globeAr') : svgIcon('globe')) + '<span>' + __('toggleLanguage', currentLang === 'ar' ? 'English' : 'العربية') + '</span>';
            }
        }

        var moreBtn = windowEl.querySelector('.ai-chat-more-btn');
        if (moreBtn) {
            moreBtn.setAttribute('title', __('moreOptions', 'More options'));
            moreBtn.setAttribute('aria-label', __('moreOptions', 'More options'));
        }

        var moreMenu = windowEl.querySelector('.ai-chat-more-menu');
        if (moreMenu) {
            var items = moreMenu.querySelectorAll('.ai-chat-more-item');
            items.forEach(function (item) {
                var action = item.dataset.action;
                var span = item.querySelector('span');
                if (!span) return;
                if (action === 'new') {
                    span.textContent = __('newConversation', 'New conversation');
                } else if (action === 'lang') {
                    span.textContent = __('toggleLanguage', currentLang === 'ar' ? 'English' : 'العربية');
                } else if (action === 'fullscreen') {
                    span.textContent = STATE.isFullscreen ? __('exitFullscreen', 'Exit fullscreen') : __('fullscreen', 'Fullscreen');
                    var fsIcon = item.querySelector('svg');
                    if (fsIcon) fsIcon.outerHTML = STATE.isFullscreen ? svgIcon('minimize2') : svgIcon('maximize');
                } else if (action === 'clear') {
                    span.textContent = __('clearConversation', 'Clear conversation');
                }
            });
        }

        var minimizeBtn = windowEl.querySelector('.ai-chat-minimize-btn');
        if (minimizeBtn) {
            minimizeBtn.setAttribute('title', __('minimize', 'Minimize'));
            minimizeBtn.setAttribute('aria-label', __('minimize', 'Minimize'));
        }

        var closeBtn = windowEl.querySelector('.ai-chat-close-btn');
        if (closeBtn) {
            closeBtn.setAttribute('title', __('close', 'Close'));
            closeBtn.setAttribute('aria-label', __('close', 'Close'));
        }

        var fullscreenBtn = windowEl.querySelector('.ai-chat-fullscreen-btn');
        if (fullscreenBtn && !STATE.isFullscreen) {
            fullscreenBtn.setAttribute('title', __('fullscreen', 'Toggle fullscreen'));
            fullscreenBtn.setAttribute('aria-label', __('fullscreen', 'Toggle fullscreen'));
        }

        var textarea = windowEl.querySelector('.ai-chat-textarea');
        if (textarea) {
            textarea.setAttribute('placeholder', __('inputPlaceholder', 'Type a message...'));
            textarea.setAttribute('aria-label', __('sendMessage', 'Chat message'));
            textarea.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
        }

        var sendBtn = windowEl.querySelector('.ai-chat-send');
        if (sendBtn) {
            sendBtn.setAttribute('aria-label', __('sendMessage', 'Send message'));
        }

        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        if (messagesEl) {
            messagesEl.setAttribute('aria-label', __('chatMessages', 'Chat messages'));
        }

        var footerEl = windowEl.querySelector('.ai-chat-footer');
        if (footerEl) {
            footerEl.textContent = currentLang === 'ar' ? __('brandingText', config.brandingText) : config.brandingText;
        }

        var thinkingEl = windowEl.querySelector('.ai-thinking-indicator');
        if (thinkingEl) {
            var thinkingSpan = thinkingEl.querySelector('span');
            if (thinkingSpan) thinkingSpan.textContent = __('thinking', 'Thinking...');
        }

        var stopBtn = windowEl.querySelector('.ai-chat-stop-btn');
        if (stopBtn) {
            stopBtn.innerHTML = svgIcon('stop') + ' ' + __('stopGenerating', 'Stop generating');
        }

        var errorRetryBtns = windowEl.querySelectorAll('.ai-error-retry');
        errorRetryBtns.forEach(function (btn) {
            btn.innerHTML = svgIcon('refresh') + ' ' + __('retry', 'Retry');
        });

        refreshEmptyState();
    }

    function refreshEmptyState() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var emptyState = messagesEl && messagesEl.querySelector('.ai-empty-state');
        if (emptyState) {
            showEmptyState();
        }
    }

    function buildWindow() {
        windowEl = document.createElement('div');
        windowEl.className = 'ai-chat-window pos-' + config.position;

        windowEl.setAttribute('dir', currentLang === 'ar' ? 'rtl' : 'ltr');

        var fullscreenBtn = '';
        if (config.allowFullscreen) {
            fullscreenBtn = '<button class="ai-chat-header-btn ai-chat-fullscreen-btn" title="' + __('fullscreen', 'Toggle fullscreen') + '" aria-label="' + __('fullscreen', 'Toggle fullscreen') + '">' + svgIcon('maximize') + '</button>';
        }

        var statusDot = '<span class="ai-chat-status ' + escapeHtml(config.onlineStatus) + '"></span>';

        var avatarHtml = config.agentAvatar
            ? '<div class="ai-chat-avatar"><img src="' + escapeHtml(config.agentAvatar) + '" alt="' + escapeHtml(config.title) + '">' + statusDot + '</div>'
            : '<div class="ai-chat-avatar">' + svgIcon('wakeb') + statusDot + '</div>';

        var headerTitleText = currentLang === 'ar' ? __('aiTitle', config.title) : config.title;
        var headerSubtitle = __('online', 'Online');
        var inputPlaceholder = __('inputPlaceholder', 'Type a message...');
        var footerText = currentLang === 'ar' ? __('brandingText', config.brandingText) : config.brandingText;

        windowEl.innerHTML = [
            '<div class="ai-chat-header" role="button" tabindex="0" aria-label="' + __('minimizeToggle', 'Toggle minimize') + '">',
            '  <div class="ai-chat-header-left">',
            '    ' + avatarHtml,
            '    <div class="ai-chat-header-info">',
            '      <h3>' + escapeHtml(headerTitleText) + '</h3>',
            '      <p>' + headerSubtitle + '</p>',
            '    </div>',
            '  </div>',
            '  <div class="ai-chat-header-actions">',
            '    <button class="ai-chat-header-btn ai-chat-lang-btn" title="' + __('toggleLanguage', 'Switch language') + '" aria-label="' + __('toggleLanguage', 'Switch language') + '">' + (currentLang === 'ar' ? svgIcon('globeAr') : svgIcon('globe')) + '<span class="ai-chat-header-lang">' + (currentLang === 'ar' ? 'EN' : 'ع') + '</span></button>',
            '    <div class="ai-chat-more-wrap">',
            '      <button class="ai-chat-header-btn ai-chat-more-btn" title="' + __('moreOptions', 'More options') + '" aria-label="' + __('moreOptions', 'More options') + '" aria-haspopup="true" aria-expanded="false">' + svgIcon('more') + '</button>',
            '      <div class="ai-chat-more-menu" role="menu">',
            '        <button class="ai-chat-more-item" data-action="new" role="menuitem">' + svgIcon('newChat') + '<span>' + __('newConversation', 'New conversation') + '</span></button>',
            '        <button class="ai-chat-more-item" data-action="lang" role="menuitem">' + (currentLang === 'ar' ? svgIcon('globeAr') : svgIcon('globe')) + '<span>' + __('toggleLanguage', 'English') + '</span></button>',
            (config.allowFullscreen ? '        <div class="ai-chat-more-divider"></div><button class="ai-chat-more-item" data-action="fullscreen" role="menuitem">' + svgIcon('maximize') + '<span>' + __('fullscreen', 'Fullscreen') + '</span></button>' : ''),
            '        <div class="ai-chat-more-divider"></div>',
            '        <button class="ai-chat-more-item" data-action="clear" role="menuitem">' + svgIcon('trash') + '<span>' + __('clearConversation', 'Clear conversation') + '</span></button>',
            '      </div>',
            '    </div>',
            '    <button class="ai-chat-header-btn ai-chat-minimize-btn" title="' + __('minimize', 'Minimize') + '" aria-label="' + __('minimize', 'Minimize') + '">' + svgIcon('minimize') + '</button>',
            fullscreenBtn,
            '    <button class="ai-chat-header-btn ai-chat-close-btn" title="' + __('close', 'Close') + '" aria-label="' + __('close', 'Close') + '">' + svgIcon('close') + '</button>',
            '  </div>',
            '</div>',
            '<div class="ai-chat-messages" role="log" aria-live="polite" aria-label="' + __('chatMessages', 'Chat messages') + '"></div>',
            '<div class="ai-chat-input-container">',
            '  <div class="ai-chat-input-row">',
            '    <textarea class="ai-chat-textarea" rows="1" placeholder="' + inputPlaceholder + '" aria-label="' + __('sendMessage', 'Chat message') + '" enterkeyhint="send"></textarea>',
            '    <button class="ai-chat-send" disabled aria-label="' + __('sendMessage', 'Send message') + '"></button>',
            '  </div>',
            '</div>',
            config.showBranding ? '<div class="ai-chat-footer">' + escapeHtml(footerText) + '</div>' : '',
        ].join('\n');

        var sendBtn = windowEl.querySelector('.ai-chat-send');
        sendBtn.innerHTML = svgIcon('send');

        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var textarea = windowEl.querySelector('.ai-chat-textarea');
        var closeBtn = windowEl.querySelector('.ai-chat-close-btn');
        var minimizeBtn = windowEl.querySelector('.ai-chat-minimize-btn');
        var fullscreenBtnEl = windowEl.querySelector('.ai-chat-fullscreen-btn');
        var headerEl = windowEl.querySelector('.ai-chat-header');

        function onInput() {
            if (textarea.value.trim()) {
                sendBtn.disabled = false;
            } else {
                sendBtn.disabled = true;
            }
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        textarea.addEventListener('input', onInput);

        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!sendBtn.disabled) sendMessage();
            }
        });

        sendBtn.addEventListener('click', sendMessage);

        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            close();
        });

        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleMinimize();
            });
        }

        if (fullscreenBtnEl) {
            fullscreenBtnEl.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleFullscreen();
            });
        }

        var langBtn = windowEl.querySelector('.ai-chat-lang-btn');
        if (langBtn) {
            langBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleLanguage();
            });
        }

        var moreBtn = windowEl.querySelector('.ai-chat-more-btn');
        var moreMenu = windowEl.querySelector('.ai-chat-more-menu');

        function closeMoreMenu() {
            if (moreMenu) {
                moreMenu.classList.remove('open');
                if (moreBtn) moreBtn.setAttribute('aria-expanded', 'false');
            }
        }

        if (moreBtn && moreMenu) {
            moreBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = moreMenu.classList.contains('open');
                moreMenu.classList.toggle('open');
                moreBtn.setAttribute('aria-expanded', String(!isOpen));
            });

            moreMenu.addEventListener('click', function (e) {
                var item = e.target.closest('.ai-chat-more-item');
                if (!item) return;
                e.stopPropagation();
                closeMoreMenu();

                var action = item.dataset.action;
                if (action === 'new') {
                    resetConversation();
                } else if (action === 'lang') {
                    toggleLanguage();
                } else if (action === 'fullscreen') {
                    toggleFullscreen();
                } else if (action === 'clear') {
                    showClearConfirm();
                }
            });
        }

        document.addEventListener('click', function (e) {
            if (moreMenu && !e.composedPath().some(function (el) {
                return el.classList && el.classList.contains('ai-chat-more-wrap');
            })) {
                closeMoreMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && moreMenu && moreMenu.classList.contains('open')) {
                closeMoreMenu();
            }
        });

        headerEl.addEventListener('click', function (e) {
            if (e.target.closest('.ai-chat-header-actions')) return;
            if (STATE.isFullscreen) return;
            toggleMinimize();
        });

        headerEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (STATE.isFullscreen) return;
                toggleMinimize();
            }
        });

        return windowEl;
    }

    function updateLauncherIcon(isOpen) {
        var iconContainer = bubble.querySelector('.ai-bubble-icon');
        if (iconContainer) {
            iconContainer.innerHTML = isOpen ? svgIcon('launcherClose') : svgIcon('launcher');
        }
        bubble.setAttribute('aria-label', isOpen ? __('closeChat', 'Close chat') : __('openChat', 'Open chat'));
    }

    function toggle() {
        STATE.isOpen = !STATE.isOpen;
        if (STATE.isOpen) {
            STATE.isMinimized = false;
            windowEl.classList.remove('minimized');
            windowEl.classList.add('open');
            bubble.style.display = 'none';
            if (typeof config.onOpen === 'function') config.onOpen();
            loadMessages();
            var textarea = windowEl.querySelector('.ai-chat-textarea');
            setTimeout(function () { textarea.focus(); }, 350);
            updateBadge(0);
        } else {
            windowEl.classList.remove('open');
            windowEl.classList.remove('fullscreen');
            STATE.isFullscreen = false;
            STATE.isMinimized = false;
            bubble.style.display = 'flex';
            if (typeof config.onClose === 'function') config.onClose();
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
        }
        bubble.setAttribute('aria-label', STATE.isOpen ? __('closeChat', 'Close chat') : __('openChat', 'Open chat'));
    }

    function open() {
        if (!STATE.isOpen) toggle();
    }

    function close() {
        if (STATE.isOpen) toggle();
    }

    function toggleMinimize() {
        STATE.isMinimized = !STATE.isMinimized;
        windowEl.classList.toggle('minimized', STATE.isMinimized);
    }

    function toggleFullscreen() {
        STATE.isFullscreen = !STATE.isFullscreen;
        windowEl.classList.toggle('fullscreen', STATE.isFullscreen);
        if (STATE.isFullscreen) {
            STATE.isMinimized = false;
            windowEl.classList.remove('minimized');
            var textarea = windowEl.querySelector('.ai-chat-textarea');
            if (textarea) textarea.focus();
        }
        var btn = windowEl.querySelector('.ai-chat-fullscreen-btn');
        if (btn) {
            btn.innerHTML = STATE.isFullscreen ? svgIcon('minimize2') : svgIcon('maximize');
            btn.title = STATE.isFullscreen ? 'Exit fullscreen' : 'Toggle fullscreen';
        }
        var fsItem = windowEl.querySelector('.ai-chat-more-item[data-action="fullscreen"]');
        if (fsItem) {
            var span = fsItem.querySelector('span');
            if (span) {
                span.textContent = STATE.isFullscreen ? __('exitFullscreen', 'Exit fullscreen') : __('fullscreen', 'Fullscreen');
            }
            var icon = fsItem.querySelector('svg');
            if (icon) icon.outerHTML = STATE.isFullscreen ? svgIcon('minimize2') : svgIcon('maximize');
        }
    }

    function appendMessage(role, content, timestamp, id) {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var msg = document.createElement('div');
        msg.className = 'ai-msg ai-msg-' + role;
        if (id) msg.dataset.messageId = id;

        var bubbleHtml = '<div class="ai-msg-bubble">' + renderMarkdown(content) + '</div>';

        var time = timestamp ? formatTime(new Date(timestamp)) : formatTime(new Date());
        var metaHtml = '<div class="ai-msg-meta"><span class="ai-msg-time">' + time + '</span>';

        if (role === 'assistant') {
            metaHtml += '<button class="ai-msg-action-btn ai-msg-copy" title="Copy reply" aria-label="Copy reply">' + svgIcon('copy') + '</button>';
            if (config.showFeedback) {
                metaHtml += '<span class="ai-msg-feedback">';
                metaHtml += '<button class="ai-msg-action-btn ai-msg-upvote" title="Helpful" aria-label="Mark as helpful">' + svgIcon('thumbsUp') + '</button>';
                metaHtml += '<button class="ai-msg-action-btn ai-msg-downvote" title="Not helpful" aria-label="Mark as not helpful">' + svgIcon('thumbsDown') + '</button>';
                metaHtml += '</span>';
            }
        }

        metaHtml += '</div>';

        msg.innerHTML = bubbleHtml + metaHtml;
        messagesEl.appendChild(msg);

        var copyBtn = msg.querySelector('.ai-msg-copy');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                copyToClipboard(content, copyBtn);
            });
        }

        var upvoteBtn = msg.querySelector('.ai-msg-upvote');
        var downvoteBtn = msg.querySelector('.ai-msg-downvote');
        if (upvoteBtn && downvoteBtn) {
            upvoteBtn.addEventListener('click', function () {
                if (upvoteBtn.classList.contains('active')) {
                    upvoteBtn.classList.remove('active');
                } else {
                    upvoteBtn.classList.add('active');
                    downvoteBtn.classList.remove('active');
                    submitFeedback(content, 'helpful');
                }
            });
            downvoteBtn.addEventListener('click', function () {
                if (downvoteBtn.classList.contains('active')) {
                    downvoteBtn.classList.remove('active');
                } else {
                    downvoteBtn.classList.add('active');
                    upvoteBtn.classList.remove('active');
                    submitFeedback(content, 'not_helpful');
                }
            });
        }

        attachCodeCopyListeners(msg);

        scrollToBottom();
        return msg;
    }

    function attachCodeCopyListeners(container) {
        var btns = container.querySelectorAll('.ai-code-copy');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var code = btn.getAttribute('data-code') || '';
                copyToClipboard(code, btn);
            });
        });
    }

    function appendThinkingIndicator() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var el = document.createElement('div');
        el.className = 'ai-thinking-indicator';
        el.innerHTML = '<div class="ai-thinking-dots"><span></span><span></span><span></span></div><span>Thinking...</span>';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-label', 'AI is thinking');
        messagesEl.appendChild(el);
        scrollToBottom();
        return el;
    }

    function removeThinkingIndicator() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var el = messagesEl.querySelector('.ai-thinking-indicator');
        if (el) el.remove();
    }

    function showLoadingSkeleton() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var skel = document.createElement('div');
        skel.className = 'ai-loading-skeleton';
        skel.innerHTML = [
            '<div class="ai-skeleton-line"></div>',
            '<div class="ai-skeleton-line"></div>',
            '<div class="ai-skeleton-line"></div>',
            '<div class="ai-skeleton-line"></div>',
            '<div class="ai-skeleton-line"></div>',
            '<div class="ai-skeleton-line"></div>',
        ].join('\n');
        messagesEl.appendChild(skel);
        scrollToBottom();
        return skel;
    }

    function removeLoadingSkeleton() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var el = messagesEl.querySelector('.ai-loading-skeleton');
        if (el) el.remove();
    }

    function showEmptyState() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var welcomeMsg = currentLang === 'ar' ? __('welcomeMessage', config.welcomeMessage) : config.welcomeMessage;
        var html = [
            '<div class="ai-empty-state">',
            '  <div class="ai-empty-icon">' + svgIcon('sparkles') + '</div>',
            '  <div class="ai-empty-title">' + escapeHtml(config.title) + '</div>',
            '  <div class="ai-empty-desc">' + escapeHtml(welcomeMsg) + '</div>',
        ].join('\n');

        var prompts = config.suggestedPrompts;
        if (currentLang === 'ar') {
            var arPrompts = __('suggestedPromptsList', null);
            if (arPrompts && Array.isArray(arPrompts)) {
                prompts = arPrompts;
            }
        }

        if (config.showSuggestions && prompts && prompts.length > 0) {
            html += '<div class="ai-suggested-prompts" role="group" aria-label="' + __('suggestedPrompts', 'Suggested questions') + '">';
            for (var i = 0; i < prompts.length; i++) {
                var prompt = prompts[i];
                html += '<button class="ai-suggested-prompt" type="button" data-prompt="' + escapeHtml(prompt) + '">' + escapeHtml(prompt) + '</button>';
            }
            html += '</div>';
        }

        html += '</div>';
        messagesEl.innerHTML = html;

        var promptBtns = messagesEl.querySelectorAll('.ai-suggested-prompt');
        promptBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-prompt') || btn.textContent;
                sendTextMessage(text);
            });
        });
    }

    function showError(message, retryFn) {
        removeExistingError();
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var err = document.createElement('div');
        err.className = 'ai-error-state';
        err.setAttribute('role', 'alert');

        var closeBtn = document.createElement('button');
        closeBtn.innerHTML = svgIcon('close');
        closeBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:inherit;flex-shrink:0;padding:2px;opacity:0.6;';
        closeBtn.setAttribute('aria-label', 'Dismiss error');

        var span = document.createElement('span');
        span.textContent = message;

        err.appendChild(span);

        if (typeof retryFn === 'function') {
            var retryBtn = document.createElement('button');
            retryBtn.className = 'ai-error-retry';
            retryBtn.innerHTML = svgIcon('refresh') + ' Retry';
            retryBtn.addEventListener('click', function () {
                err.remove();
                retryFn();
            });
            err.appendChild(retryBtn);
        }

        closeBtn.addEventListener('click', function () { err.remove(); });
        err.appendChild(closeBtn);
        messagesEl.appendChild(err);
        scrollToBottom();
        return err;
    }

    function removeExistingError() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var existing = messagesEl.querySelector('.ai-error-state');
        if (existing) existing.remove();
    }

    function scrollToBottom() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        requestAnimationFrame(function () {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        });
    }

    function copyToClipboard(text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showCopiedFeedback(btn);
            }).catch(function () {
                fallbackCopy(text, btn);
            });
        } else {
            fallbackCopy(text, btn);
        }
    }

    function fallbackCopy(text, btn) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        ta.style.pointerEvents = 'none';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            showCopiedFeedback(btn);
        } catch (e) {}
        document.body.removeChild(ta);
    }

    function showCopiedFeedback(btn) {
        btn.classList.add('copied');
        btn.innerHTML = svgIcon('check');
        setTimeout(function () {
            btn.classList.remove('copied');
            btn.innerHTML = svgIcon('copy');
        }, 2000);
    }

    function submitFeedback(content, type) {
        if (!config.apiBaseUrl) return;
        var body = {
            message: content,
            feedback_type: type,
            session_id: getSessionId(),
        };
        if (STATE.currentConversationId) {
            body.conversation_id = STATE.currentConversationId;
        }
        fetch(config.apiBaseUrl + '/feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        }).catch(function () {});
    }

    function setLoading(loading) {
        STATE.isLoading = loading;
        var sendBtn = windowEl.querySelector('.ai-chat-send');
        var textarea = windowEl.querySelector('.ai-chat-textarea');
        if (sendBtn) {
            sendBtn.disabled = loading || !(textarea && textarea.value.trim());
            sendBtn.classList.toggle('loading', loading);
        }
        if (textarea) textarea.disabled = loading;
    }

    function loadMessages() {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var stored = loadMessagesFromStorage();
        var conversationId = getStoredConversationId();

        if (stored && stored.length > 0) {
            STATE.messages = stored;
            STATE.currentConversationId = conversationId;
            messagesEl.innerHTML = '';
            for (var i = 0; i < stored.length; i++) {
                var sm = stored[i];
                appendMessage(sm.role, sm.content, sm.timestamp || null);
            }
            scrollToBottom();

            if (conversationId) {
                fetch(config.apiBaseUrl + '/conversations/' + encodeURIComponent(conversationId) + '?session_id=' + encodeURIComponent(getSessionId()), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(function (response) {
                    if (!response.ok) {
                        if (response.status === 404) {
                            setStoredConversationId(null);
                            STATE.currentConversationId = null;
                        }
                        return null;
                    }
                    return response.json();
                }).then(function (data) {
                    if (!data) return;
                    var conversation = data.data || data;
                    var msgList = conversation.messages || [];
                    if (msgList.length > STATE.messages.length && msgList.length > 0) {
                        STATE.messages = [];
                        messagesEl.innerHTML = '';
                        for (var j = 0; j < msgList.length; j++) {
                            var m = msgList[j];
                            var role = m.role === 'user' ? 'user' : 'assistant';
                            appendMessage(role, m.content, m.created_at, m.id);
                            STATE.messages.push({ role: role, content: m.content, timestamp: m.created_at });
                        }
                        saveMessages();
                        scrollToBottom();
                    }
                }).catch(function () {});
            }
            return;
        }

        if (!conversationId) {
            showEmptyState();
            return;
        }

        STATE.currentConversationId = conversationId;
        messagesEl.innerHTML = '';

        var skeleton = showLoadingSkeleton();

        fetch(config.apiBaseUrl + '/conversations/' + encodeURIComponent(conversationId) + '?session_id=' + encodeURIComponent(getSessionId()), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            if (!response.ok) {
                if (response.status === 404) {
                    setStoredConversationId(null);
                    STATE.currentConversationId = null;
                    return null;
                }
                throw new Error('Failed to load conversation');
            }
            return response.json();
        }).then(function (data) {
            skeleton.remove();
            if (!data) {
                showEmptyState();
                return;
            }
            var conversation = data.data || data;
            var msgList = conversation.messages || [];
            messagesEl.innerHTML = '';

            if (msgList.length === 0) {
                showEmptyState();
                return;
            }

            for (var i = 0; i < msgList.length; i++) {
                var m = msgList[i];
                var role = m.role === 'user' ? 'user' : 'assistant';
                appendMessage(role, m.content, m.created_at, m.id);
                STATE.messages.push({ role: role, content: m.content, timestamp: m.created_at });
            }
            saveMessages();
            scrollToBottom();
        }).catch(function (error) {
            skeleton.remove();
            console.error('[AIChatWidget] Failed to load conversation:', error);
            setStoredConversationId(null);
            STATE.currentConversationId = null;
            clearStoredMessages();
            showEmptyState();
        });
    }

    function resetConversation() {
        if (STATE.isLoading || STATE.isStreaming) {
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
        }
        STATE.messages = [];
        STATE.currentConversationId = null;
        setStoredConversationId(null);
        clearStoredMessages();
        STATE.isStreaming = false;
        removeThinkingIndicator();
        removeExistingError();
        showEmptyState();
        var textarea = windowEl.querySelector('.ai-chat-textarea');
        if (textarea) {
            textarea.value = '';
            textarea.style.height = 'auto';
            textarea.focus();
        }
        setLoading(false);
    }

    function showClearConfirm() {
        if (confirmOverlay) confirmOverlay.remove();

        confirmOverlay = document.createElement('div');
        confirmOverlay.className = 'ai-confirm-overlay';
        confirmOverlay.innerHTML = '<div class="ai-confirm-box">'
            + '<div class="ai-confirm-text">' + __('clearConfirm', 'This will clear all messages. Are you sure?') + '</div>'
            + '<div class="ai-confirm-actions">'
            + '<button class="ai-confirm-btn ai-confirm-btn-cancel">' + __('cancel', 'Cancel') + '</button>'
            + '<button class="ai-confirm-btn ai-confirm-btn-danger">' + __('clearConversation', 'Clear') + '</button>'
            + '</div>'
            + '</div>';

        windowEl.appendChild(confirmOverlay);

        requestAnimationFrame(function () {
            confirmOverlay.classList.add('open');
        });

        var cancelBtn = confirmOverlay.querySelector('.ai-confirm-btn-cancel');
        var confirmBtn = confirmOverlay.querySelector('.ai-confirm-btn-danger');

        function closeConfirm() {
            confirmOverlay.classList.remove('open');
            setTimeout(function () {
                if (confirmOverlay && confirmOverlay.parentNode) {
                    confirmOverlay.parentNode.removeChild(confirmOverlay);
                }
                confirmOverlay = null;
            }, 200);
        }

        cancelBtn.addEventListener('click', closeConfirm);
        confirmBtn.addEventListener('click', function () {
            closeConfirm();
            STATE.messages = [];
            STATE.currentConversationId = null;
            setStoredConversationId(null);
            clearStoredMessages();
            STATE.isStreaming = false;
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
            removeThinkingIndicator();
            removeExistingError();
            var messagesEl = windowEl.querySelector('.ai-chat-messages');
            if (messagesEl) {
                messagesEl.innerHTML = '';
            }
            showEmptyState();
            var textarea = windowEl.querySelector('.ai-chat-textarea');
            if (textarea) {
                textarea.value = '';
                textarea.style.height = 'auto';
                textarea.focus();
            }
            setLoading(false);
        });

        confirmOverlay.addEventListener('click', function (e) {
            if (e.target === confirmOverlay) closeConfirm();
        });
    }

    function sendTextMessage(text) {
        var textarea = windowEl.querySelector('.ai-chat-textarea');
        if (textarea) {
            textarea.value = text;
            textarea.dispatchEvent(new Event('input'));
        }
        sendMessage();
    }

    function sendMessage() {
        var textarea = windowEl.querySelector('.ai-chat-textarea');
        var message = textarea.value.trim();
        if (!message || STATE.isLoading || STATE.isStreaming) return;

        if (STATE.messages.length === 0) {
            var messagesEl = windowEl.querySelector('.ai-chat-messages');
            messagesEl.innerHTML = '';
        }

        removeExistingError();
        appendMessage('user', message);
        STATE.messages.push({ role: 'user', content: message, timestamp: new Date().toISOString() });
        saveMessages();
        textarea.value = '';
        textarea.style.height = 'auto';
        setLoading(true);

        var body = {
            message: message,
            session_id: getSessionId(),
            stream: true,
        };

        if (STATE.currentConversationId) {
            body.conversation_id = STATE.currentConversationId;
        }

        var lastMessageContent = '';
        var assistantMsgEl = null;
        var thinkingEl = null;
        var stopBtn = null;

        abortController = new AbortController();

        fetch(config.apiBaseUrl + '/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream, application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
            signal: abortController.signal,
        }).then(function (response) {
            if (!response.ok) {
                return response.json().then(function (data) {
                    throw new Error(data.message || data.data?.message || 'Request failed: ' + response.status);
                });
            }

            var canStream = response.body && typeof response.body.getReader === 'function';

            if (canStream) {
                STATE.isStreaming = true;
                showStopButton();
                return streamResponse(response);
            }

            STATE.isStreaming = false;
            hideStopButton();
            return response.json().then(function (data) {
                var resp = data.data || data;
                var reply = resp.message?.content || resp.message || resp.text || '';
                if (reply) {
                    appendMessage('assistant', reply);
                    STATE.messages.push({ role: 'assistant', content: reply, timestamp: new Date().toISOString() });
                    saveMessages();
                }
                if (resp.conversation_id) {
                    STATE.currentConversationId = resp.conversation_id;
                    setStoredConversationId(resp.conversation_id);
                }
                if (typeof config.onMessage === 'function') {
                    config.onMessage({ role: 'assistant', content: reply });
                }
            });
        }).catch(function (error) {
            if (error.name === 'AbortError') {
                STATE.isStreaming = false;
                hideStopButton();
                return;
            }
            STATE.isStreaming = false;
            hideStopButton();
            removeThinkingIndicator();
            var lastMsg = STATE.messages[STATE.messages.length - 1];
            if (lastMsg && lastMsg.role === 'user') {
                showError(error.message || 'Failed to send message', function () {
                    STATE.messages.pop();
                    sendMessage();
                });
            } else {
                showError(error.message || 'Failed to send message', sendMessage);
            }
            if (typeof config.onError === 'function') config.onError(error);
        }).finally(function () {
            if (!STATE.isStreaming) {
                setLoading(false);
            }
            abortController = null;
        });

        function showStopButton() {
            var inputContainer = windowEl.querySelector('.ai-chat-input-container');
            if (inputContainer && !inputContainer.querySelector('.ai-chat-stop-btn')) {
                stopBtn = document.createElement('button');
                stopBtn.className = 'ai-chat-stop-btn';
                stopBtn.innerHTML = svgIcon('stop') + ' Stop generating';
                stopBtn.setAttribute('type', 'button');
                stopBtn.addEventListener('click', function () {
                    if (abortController) {
                        abortController.abort();
                        abortController = null;
                    }
                    STATE.isStreaming = false;
                    hideStopButton();
                    setLoading(false);
                    removeThinkingIndicator();
                    finalizeStream(lastMessageContent);
                });
                inputContainer.insertBefore(stopBtn, inputContainer.firstChild);
            }
        }

        function hideStopButton() {
            var inputContainer = windowEl.querySelector('.ai-chat-input-container');
            var existing = inputContainer && inputContainer.querySelector('.ai-chat-stop-btn');
            if (existing) existing.remove();
            stopBtn = null;
        }

        function streamResponse(response) {
            var fullText = '';
            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';
            var currentEvent = '';
            var hasContent = false;
            var finalizing = false;

            thinkingEl = appendThinkingIndicator();

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
                                setStoredConversationId(parsed.conversation_id);
                            }
                        } catch (e) {}
                        currentEvent = '';
                        continue;
                    }

                    currentEvent = '';

                    if (data === '[DONE]') {
                        STATE.isStreaming = false;
                        hideStopButton();
                        continue;
                    }

                    if (data === '[ABORT]' || data === '[ERROR]') {
                        STATE.isStreaming = false;
                        hideStopButton();
                        continue;
                    }

                    try {
                        var parsed = JSON.parse(data);

                        if (parsed.type === 'thinking_start' || parsed.type === 'reasoning_start') {
                            if (!thinkingEl) thinkingEl = appendThinkingIndicator();
                            continue;
                        }

                        if (parsed.type === 'thinking_end' || parsed.type === 'reasoning_end') {
                            if (thinkingEl) {
                                thinkingEl.remove();
                                thinkingEl = null;
                            }
                            continue;
                        }

                        if (parsed.type === 'thinking_delta' || parsed.type === 'reasoning_delta') {
                            continue;
                        }

                        if (parsed.type === 'text_delta' || parsed.type === 'text_start') {
                            var delta = parsed.delta || parsed.content || '';
                            if (delta) {
                                hasContent = true;
                                fullText += delta;
                                lastMessageContent = fullText;
                                if (thinkingEl) {
                                    thinkingEl.remove();
                                    thinkingEl = null;
                                }
                                updateLastAssistantMessage(fullText, true);
                            }
                            continue;
                        }

            if (parsed.type === 'text_end') {
                continue;
            }

            if (parsed.type === 'stream_end') {
                STATE.isStreaming = false;
                hideStopButton();
                if (fullText && !finalizing) {
                    finalizeStream(fullText);
                }
                continue;
            }

            if (parsed.error) {
                STATE.isStreaming = false;
                hideStopButton();
                throw new Error(parsed.error);
            }

            if (parsed.message && typeof parsed.message === 'string') {
                STATE.isStreaming = false;
                hideStopButton();
                throw new Error(parsed.message);
            }

                        if (parsed.text !== undefined) {
                            hasContent = true;
                            fullText += parsed.text;
                            lastMessageContent = fullText;
                            if (thinkingEl) {
                                thinkingEl.remove();
                                thinkingEl = null;
                            }
                            updateLastAssistantMessage(fullText, true);
                            continue;
                        }

                        if (parsed.content !== undefined && !parsed.type) {
                            hasContent = true;
                            fullText += parsed.content;
                            lastMessageContent = fullText;
                            if (thinkingEl) {
                                thinkingEl.remove();
                                thinkingEl = null;
                            }
                            updateLastAssistantMessage(fullText, true);
                        }
                    } catch (e) {
                        if (data && !data.startsWith('{') && !data.startsWith('[')) {
                            hasContent = true;
                            fullText += data;
                            lastMessageContent = fullText;
                            updateLastAssistantMessage(fullText, true);
                        }
                    }
                }
            }

            function finalizeStream(text) {
                if (finalizing) return;
                finalizing = true;
                STATE.isStreaming = false;
                hideStopButton();
                if (thinkingEl) {
                    thinkingEl.remove();
                    thinkingEl = null;
                }
                if (text) {
                    updateLastAssistantMessage(text, false);
                    STATE.messages.push({ role: 'assistant', content: text, timestamp: new Date().toISOString() });
                    saveMessages();
                    if (typeof config.onMessage === 'function') {
                        config.onMessage({ role: 'assistant', content: text });
                    }
                }
                setLoading(false);
            }

            return reader.read().then(processChunk);
        }
    }

    function updateLastAssistantMessage(content, isStreaming) {
        var messagesEl = windowEl.querySelector('.ai-chat-messages');
        var lastMsg = messagesEl.querySelector('.ai-msg-assistant:last-child');
        if (!lastMsg) {
            lastMsg = appendMessage('assistant', '');
        }
        var bubble = lastMsg.querySelector('.ai-msg-bubble');
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.className = 'ai-msg-bubble';
            var meta = lastMsg.querySelector('.ai-msg-meta');
            if (meta) {
                lastMsg.insertBefore(bubble, meta);
            } else {
                lastMsg.appendChild(bubble);
            }
        }

        bubble.innerHTML = renderMarkdown(content);

        if (isStreaming) {
            bubble.classList.add('ai-chat-streaming-cursor');
        } else {
            bubble.classList.remove('ai-chat-streaming-cursor');
        }

        attachCodeCopyListeners(lastMsg);

        if (!isStreaming) {
            var existingCopy = lastMsg.querySelector('.ai-msg-copy');
            if (!existingCopy) {
                var meta = lastMsg.querySelector('.ai-msg-meta');
                if (meta) {
                    var copyBtn = document.createElement('button');
                    copyBtn.className = 'ai-msg-action-btn ai-msg-copy';
                    copyBtn.title = 'Copy reply';
                    copyBtn.setAttribute('aria-label', 'Copy reply');
                    copyBtn.innerHTML = svgIcon('copy');
                    copyBtn.addEventListener('click', function () {
                        copyToClipboard(content, copyBtn);
                    });
                    meta.insertBefore(copyBtn, meta.querySelector('.ai-msg-feedback'));
                }
            }
        }

        var metaEl = lastMsg.querySelector('.ai-msg-meta');
        if (metaEl) {
            var timeEl = metaEl.querySelector('.ai-msg-time');
            if (timeEl) {
                timeEl.textContent = formatTime(new Date());
            } else {
                var newTime = document.createElement('span');
                newTime.className = 'ai-msg-time';
                newTime.textContent = formatTime(new Date());
                metaEl.insertBefore(newTime, metaEl.firstChild);
            }
        }

        scrollToBottom();
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

        var storedLang = getStoredLanguage();
        var docLang = document.documentElement.getAttribute('lang') || '';
        currentLang = storedLang || (docLang.startsWith('ar') ? 'ar' : 'ar');
        isRtl = currentLang === 'ar';

        if (typeof ShadowRoot !== 'undefined') {
            container = document.createElement('div');
            container.style.position = 'relative';
            container.style.zIndex = '2147482999';
            container.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
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

        var storedId = getStoredConversationId();
        if (storedId) {
            STATE.currentConversationId = storedId;
        }

        if (!STATE.isOpen && !config.autoOpen) {
            updateBadge(1);
        }

        if (config.autoOpen) {
            setTimeout(toggle, config.openDelay || 3000);
        }
    }

    window.AIChatWidget = {
        init: init,
        open: function () { if (!STATE.isOpen) toggle(); },
        close: function () { if (STATE.isOpen) toggle(); },
        toggle: function () { toggle(); },
        expand: function () {
            if (!STATE.isOpen) toggle();
            if (STATE.isMinimized) toggleMinimize();
        },
        reset: resetConversation,
        destroy: function () {
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
            if (container) container.remove();
        },
        setLanguage: function (lang) {
            if (lang === 'ar' || lang === 'en') {
                currentLang = lang;
                setStoredLanguage(lang);
                isRtl = lang === 'ar';
                applyLanguage();
            }
        },
        getLanguage: function () {
            return currentLang;
        },
        sendMessage: function (msg) {
            sendTextMessage(msg);
        },
        getState: function () {
            return {
                isOpen: STATE.isOpen,
                isMinimized: STATE.isMinimized,
                isFullscreen: STATE.isFullscreen,
                isLoading: STATE.isLoading,
                isStreaming: STATE.isStreaming,
                currentConversationId: STATE.currentConversationId,
                messageCount: STATE.messages.length,
            };
        },
        VERSION: VERSION,
    };
})();
