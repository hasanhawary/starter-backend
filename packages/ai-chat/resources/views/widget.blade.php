@once
<div id="ai-chat-widget-mount"></div>
<script src="{{ asset('vendor/ai-chat/ai-chat-widget.min.js') }}"></script>
<script>
    if (typeof AIChatWidget !== 'undefined') {
        AIChatWidget.init({
            apiBaseUrl: '{{ url('/api/ai-chat') }}',
            title: '{{ config('ai-chat.widget.title', 'WaKeb AI') }}',
            subtitle: '{{ config('ai-chat.widget.subtitle', 'How can we help you?') }}',
            theme: '{{ config('ai-chat.widget.theme', 'light') }}',
            position: '{{ config('ai-chat.widget.position', 'bottom-right') }}',
            primaryColor: '{{ config('ai-chat.widget.primary_color', '#5196F3') }}',
            agentAvatar: '{{ config('ai-chat.widget.agent_avatar', '') }}',
            welcomeMessage: '{{ config('ai-chat.widget.welcome_message', 'Hello! How can I help you today?') }}',
            allowFullscreen: {{ config('ai-chat.widget.allow_fullscreen', true) ? 'true' : 'false' }},
            autoOpen: {{ config('ai-chat.widget.auto_open', false) ? 'true' : 'false' }},
            openDelay: {{ (int) config('ai-chat.widget.open_delay', 3000) }},
            height: {{ (int) config('ai-chat.widget.height', 600) }},
            width: {{ (int) config('ai-chat.widget.width', 380) }},
            showFeedback: {{ config('ai-chat.widget.show_feedback', true) ? 'true' : 'false' }},
            showSuggestions: {{ config('ai-chat.widget.show_suggestions', true) ? 'true' : 'false' }},
            showBranding: {{ config('ai-chat.widget.show_branding', true) ? 'true' : 'false' }},
            suggestedPrompts: {!! json_encode(config('ai-chat.widget.suggested_prompts', [
                'What AI solutions does WaKeb offer?',
                'Tell me about WaKeb products',
                'How can WaKeb help my business?',
                'Schedule a consultation',
            ])) !!},
            onlineStatus: '{{ config('ai-chat.widget.online_status', 'online') }}',
        });
    }
</script>
@endonce
