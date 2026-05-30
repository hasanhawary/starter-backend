@once
<div id="ai-chat-widget-mount"></div>
<script src="{{ asset('vendor/ai-chat/js/ai-chat-widget/ai-chat-widget.js') }}"></script>
<script>
    if (typeof AIChatWidget !== 'undefined') {
        AIChatWidget.init({
            apiBaseUrl: '{{ url("/api/ai-chat") }}',
            title: '{{ config("ai-chat.widget.title", "AI Assistant") }}',
            theme: '{{ config("ai-chat.widget.theme", "light") }}',
            position: '{{ config("ai-chat.widget.position", "bottom-right") }}',
            @if(auth()->check())
            userId: '{{ auth()->id() }}',
            @endif
        });
    }
</script>
@endonce
