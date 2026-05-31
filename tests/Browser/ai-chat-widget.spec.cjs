/**
 * AI Chat Widget — Playwright Browser Tests
 *
 * Prerequisites:
 *   composer run dev   (or php artisan serve)
 *
 * Usage:
 *   npx playwright test tests/Browser/ai-chat-widget.spec.cjs
 */

const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';

function findSR(page, cb) {
  return page.evaluate(function (fnStr) {
    var sr = null;
    for (var i = 0; i < document.body.children.length; i++) {
      var el = document.body.children[i];
      if (el.shadowRoot) { sr = el.shadowRoot; break; }
    }
    if (!sr) return null;
    return new Function('sr', fnStr)(sr);
  }, cb.toString());
}

function clickSR(page, sel) {
  return page.evaluate(function (s) {
    for (var i = 0; i < document.body.children.length; i++) {
      var el = document.body.children[i];
      if (el.shadowRoot) {
        var t = el.shadowRoot.querySelector(s);
        if (t) { t.click(); return true; }
      }
    }
    return false;
  }, sel);
}

test.describe('AI Chat Widget — Visual & Behavioral Tests', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto(BASE_URL, { waitUntil: 'load' });
  });

  test('page loads with widget mount point', async ({ page }) => {
    await expect(page.locator('#ai-chat-widget-mount')).toBeAttached();
    var exists = await page.evaluate(function () { return typeof AIChatWidget !== 'undefined'; });
    expect(exists).toBe(true);
  });

  test('floating launcher button exists in shadow DOM', async ({ page }) => {
    var r = await page.evaluate(function () {
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot && el.shadowRoot.querySelector('.ai-chat-bubble')) return true;
      }
      return false;
    });
    expect(r).toBe(true);
  });

  test('floating launcher has chat icon', async ({ page }) => {
    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var b = sr.querySelector('.ai-chat-bubble');
      if (!b) return false;
      var icon = b.querySelector('.ai-bubble-icon');
      return icon && icon.innerHTML.indexOf('M21 15a2') !== -1;
    });
    expect(r).toBe(true);
  });

  test('clicking launcher opens widget window', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win && win.classList.contains('open');
    });
    expect(r).toBe(true);
  });

  test('opened widget has header with title and status', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { headerExists: false };
      var header = sr.querySelector('.ai-chat-header');
      var title = sr.querySelector('.ai-chat-header-info h3');
      var status = sr.querySelector('.ai-chat-status');
      return {
        headerExists: !!header,
        titleText: title ? title.textContent : '',
        statusExists: !!status,
        statusClass: status ? status.className : '',
      };
    });
    expect(r.headerExists).toBe(true);
    expect(r.titleText).toBeTruthy();
    expect(r.statusExists).toBe(true);
    expect(r.statusClass).toContain('online');
  });

  test('opened widget has messages area and input', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { messages: false };
      return {
        messages: !!sr.querySelector('.ai-chat-messages'),
        input: !!sr.querySelector('.ai-chat-textarea'),
        sendBtn: !!sr.querySelector('.ai-chat-send'),
      };
    });
    expect(r.messages).toBe(true);
    expect(r.input).toBe(true);
    expect(r.sendBtn).toBe(true);
  });

  test('opened widget shows empty state with suggested prompts', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { emptyState: false };
      return {
        emptyState: !!sr.querySelector('.ai-empty-state'),
        suggestedPrompts: sr.querySelectorAll('.ai-suggested-prompt').length > 0,
        welcomeText: sr.querySelector('.ai-empty-title')?.textContent || '',
      };
    });
    expect(r.emptyState).toBe(true);
    expect(r.suggestedPrompts).toBe(true);
    expect(r.welcomeText).toBeTruthy();
  });

  test('header has more-options dropdown, minimize, close buttons', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { langBtn: false };
      return {
        langBtn: !!sr.querySelector('.ai-chat-lang-btn'),
        moreBtn: !!sr.querySelector('.ai-chat-more-btn'),
        moreMenu: !!sr.querySelector('.ai-chat-more-menu'),
        minimizeBtn: !!sr.querySelector('.ai-chat-minimize-btn'),
        closeBtn: !!sr.querySelector('.ai-chat-close-btn'),
        fullscreenBtn: !!sr.querySelector('.ai-chat-fullscreen-btn'),
      };
    });
    expect(r.langBtn).toBe(true);
    expect(r.moreBtn).toBe(true);
    expect(r.moreMenu).toBe(true);
    expect(r.minimizeBtn).toBe(true);
    expect(r.closeBtn).toBe(true);
    expect(r.fullscreenBtn).toBe(true);
  });

  test('close button hides widget', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);
    await clickSR(page, '.ai-chat-close-btn');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return !win.classList.contains('open');
    });
    expect(r).toBe(true);
  });

  test('new conversation from more menu resets the chat', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);
    await clickSR(page, '.ai-chat-more-btn');
    await page.waitForTimeout(200);
    await clickSR(page, '.ai-chat-more-item[data-action="new"]');
    await page.waitForTimeout(200);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      return !!sr.querySelector('.ai-empty-state');
    });
    expect(r).toBe(true);
  });

  test('textarea accepts input and enables send button', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var ta = sr.querySelector('.ai-chat-textarea');
      var btn = sr.querySelector('.ai-chat-send');
      ta.value = 'Hello';
      ta.dispatchEvent(new Event('input'));
      return !btn.disabled;
    });
    expect(r).toBe(true);
  });

  test('empty textarea keeps send button disabled', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var ta = sr.querySelector('.ai-chat-textarea');
      var btn = sr.querySelector('.ai-chat-send');
      ta.value = '';
      ta.dispatchEvent(new Event('input'));
      return btn.disabled;
    });
    expect(r).toBe(true);
  });

  test('clicking suggested prompt sends message', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { clicked: false };
      var p = sr.querySelector('.ai-suggested-prompt');
      if (!p) return { clicked: false };
      var text = p.getAttribute('data-prompt') || p.textContent;
      p.click();
      return { clicked: true, text: text };
    });
    expect(r.clicked).toBe(true);
    expect(r.text).toBeTruthy();
  });

  test('minimize button collapses the widget', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);
    await clickSR(page, '.ai-chat-minimize-btn');
    await page.waitForTimeout(300);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win.classList.contains('minimized');
    });
    expect(r).toBe(true);
  });

  test('fullscreen button expands the widget', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);
    await clickSR(page, '.ai-chat-fullscreen-btn');
    await page.waitForTimeout(300);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win.classList.contains('fullscreen');
    });
    expect(r).toBe(true);
  });

  test('widget has accessible aria-labels', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { launcher: '' };
      return {
        launcher: sr.querySelector('.ai-chat-bubble')?.getAttribute('aria-label'),
        close: sr.querySelector('.ai-chat-close-btn')?.getAttribute('aria-label'),
        minimize: sr.querySelector('.ai-chat-minimize-btn')?.getAttribute('aria-label'),
        textarea: sr.querySelector('.ai-chat-textarea')?.getAttribute('aria-label'),
        send: sr.querySelector('.ai-chat-send')?.getAttribute('aria-label'),
        messages: sr.querySelector('.ai-chat-messages')?.getAttribute('aria-label'),
      };
    });
    expect(r.launcher).toBe('Close chat');
    expect(r.close).toBe('Close');
    expect(r.minimize).toBe('Minimize');
    expect(r.textarea).toBe('Chat message');
    expect(r.send).toBe('Send message');
    expect(r.messages).toBe('Chat messages');
  });

  test('welcome page Open AI Chat button opens widget', async ({ page }) => {
    await page.locator('button', { hasText: 'Open AI Chat' }).click();
    await page.waitForTimeout(400);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win && win.classList.contains('open');
    });
    expect(r).toBe(true);
  });

  test('programmatic API open/close/toggle/reset', async ({ page }) => {
    await page.evaluate(function () { window.AIChatWidget.open(); });
    await page.waitForTimeout(400);
    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win && win.classList.contains('open');
    });
    expect(r).toBe(true);

    await page.evaluate(function () { window.AIChatWidget.close(); });
    await page.waitForTimeout(400);
    r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win && win.classList.contains('open');
    });
    expect(r).toBe(false);

    await page.evaluate(function () { window.AIChatWidget.toggle(); });
    await page.waitForTimeout(400);
    r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var win = sr.querySelector('.ai-chat-window');
      return win && win.classList.contains('open');
    });
    expect(r).toBe(true);

    await page.evaluate(function () { window.AIChatWidget.reset(); });
    await page.waitForTimeout(200);
    r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return null;
      return !!sr.querySelector('.ai-empty-state');
    });
    expect(r).toBe(true);
  });

  test('sendMessage via programmatic API works', async ({ page }) => {
    await page.evaluate(function () { window.AIChatWidget.open(); });
    await page.waitForTimeout(400);
    await page.evaluate(function () { window.AIChatWidget.sendMessage('Hello from test'); });
    await page.waitForTimeout(300);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      return sr.querySelectorAll('.ai-msg-user').length > 0;
    });
    expect(r).toBe(true);
  });

  test('getState returns correct widget state', async ({ page }) => {
    var state = await page.evaluate(function () { return window.AIChatWidget.getState(); });
    expect(state).toHaveProperty('isOpen');
    expect(state).toHaveProperty('isMinimized');
    expect(state).toHaveProperty('isFullscreen');
    expect(state).toHaveProperty('isLoading');
    expect(state).toHaveProperty('isStreaming');
    expect(state).toHaveProperty('currentConversationId');
    expect(state).toHaveProperty('messageCount');
    expect(state.isOpen).toBe(false);
  });

  test('browser console has no errors from widget', async ({ page }) => {
    var errors = [];
    page.on('console', function (msg) {
      if (msg.type() === 'error') { errors.push(msg.text()); }
    });
    await page.goto(BASE_URL, { waitUntil: 'networkidle' });
    await page.evaluate(function () { window.AIChatWidget.open(); });
    await page.waitForTimeout(300);
    await page.evaluate(function () { window.AIChatWidget.close(); });
    await page.waitForTimeout(300);
    await page.evaluate(function () { window.AIChatWidget.toggle(); });
    await page.waitForTimeout(300);
    var widgetErrors = errors.filter(function (e) {
      return e.indexOf('AIChatWidget') !== -1 || e.indexOf('ai-chat') !== -1;
    });
    expect(widgetErrors.length).toBe(0);
  });

  test('launcher has unread badge when widget is closed', async ({ page }) => {
    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var b = sr.querySelector('.ai-chat-bubble');
      if (!b) return false;
      return !!b.querySelector('.ai-badge');
    });
    expect(r).toBe(true);
  });

  test('badge disappears when widget opens', async ({ page }) => {
    await page.evaluate(function () { window.AIChatWidget.open(); });
    await page.waitForTimeout(300);

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return false;
      var b = sr.querySelector('.ai-chat-bubble');
      if (!b) return false;
      return !!b.querySelector('.ai-badge');
    });
    expect(r).toBe(false);
  });

  test('language toggle switches between Arabic and English', async ({ page }) => {
    await clickSR(page, '.ai-chat-bubble');
    await page.waitForTimeout(400);

    var lang = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { dir: '', btnText: '' };
      return {
        dir: sr.querySelector('.ai-chat-window').getAttribute('dir'),
        btnText: sr.querySelector('.ai-chat-header-lang')?.textContent || '',
      };
    });
    expect(lang.dir).toBe('ltr');
    expect(lang.btnText).toBe('ع');

    await clickSR(page, '.ai-chat-lang-btn');
    await page.waitForTimeout(200);

    lang = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { dir: '', btnText: '' };
      return {
        dir: sr.querySelector('.ai-chat-window').getAttribute('dir'),
        btnText: sr.querySelector('.ai-chat-header-lang')?.textContent || '',
      };
    });
    expect(lang.dir).toBe('rtl');
    expect(lang.btnText).toBe('EN');

    await clickSR(page, '.ai-chat-lang-btn');
    await page.waitForTimeout(200);

    lang = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      if (!sr) return { dir: '', btnText: '' };
      return {
        dir: sr.querySelector('.ai-chat-window').getAttribute('dir'),
        btnText: sr.querySelector('.ai-chat-header-lang')?.textContent || '',
      };
    });
    expect(lang.dir).toBe('ltr');
    expect(lang.btnText).toBe('ع');
  });

  test('renders streamed markdown tables, lists, links, and code blocks', async ({ page }) => {
    var markdown = '| Name | Age |\n| --- | --- |\n| Ada | 36 |\n\n- First item\n- Second item\n\n[Example](https://example.com)\n\n```js\nconsole.log("ok");\n```';

    await page.route('**/api/ai-chat/messages', async function (route) {
      await route.fulfill({
        status: 200,
        headers: { 'content-type': 'text/event-stream' },
        body: [
          'event: conversation_id',
          'data: {"conversation_id":"conv_markdown"}',
          '',
          'data: ' + JSON.stringify({ type: 'text_delta', delta: markdown }),
          '',
          'data: [DONE]',
          '',
        ].join('\n'),
      });
    });

    await page.evaluate(function () { window.AIChatWidget.open(); });
    await page.waitForTimeout(300);
    await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      var ta = sr.querySelector('.ai-chat-textarea');
      ta.value = 'Show markdown';
      ta.dispatchEvent(new Event('input'));
      sr.querySelector('.ai-chat-send').click();
    });

    await page.waitForFunction(function () {
      return window.AIChatWidget.getState().isLoading === false
        && window.AIChatWidget.getState().isStreaming === false;
    });

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      var bubble = sr.querySelector('.ai-msg-assistant .ai-msg-bubble');
      return {
        hasTable: !!bubble.querySelector('table'),
        rows: bubble.querySelectorAll('tr').length,
        listItems: bubble.querySelectorAll('li').length,
        linkHref: bubble.querySelector('a')?.getAttribute('href'),
        codeText: bubble.querySelector('.ai-code-block pre code')?.textContent || '',
        codeCopy: !!bubble.querySelector('.ai-code-copy'),
      };
    });

    expect(r.hasTable).toBe(true);
    expect(r.rows).toBe(2);
    expect(r.listItems).toBe(2);
    expect(r.linkHref).toBe('https://example.com');
    expect(r.codeText).toContain('console.log("ok");');
    expect(r.codeCopy).toBe(true);
  });

  test('retry after API failure resends once without duplicating user message', async ({ page }) => {
    var requestCount = 0;

    await page.route('**/api/ai-chat/messages', async function (route) {
      requestCount++;

      if (requestCount === 1) {
        await route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({ message: 'Temporary AI service outage' }),
        });

        return;
      }

      await route.fulfill({
        status: 200,
        headers: { 'content-type': 'text/event-stream' },
        body: [
          'event: conversation_id',
          'data: {"conversation_id":"conv_retry"}',
          '',
          'data: ' + JSON.stringify({ type: 'text_delta', delta: 'Recovered response' }),
          '',
          'data: [DONE]',
          '',
        ].join('\n'),
      });
    });

    await page.evaluate(function () { window.AIChatWidget.open(); });
    await page.waitForTimeout(300);
    await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      var ta = sr.querySelector('.ai-chat-textarea');
      ta.value = 'Retry this message';
      ta.dispatchEvent(new Event('input'));
      sr.querySelector('.ai-chat-send').click();
    });

    await page.waitForSelector('css=body');
    await page.waitForTimeout(400);

    await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      sr.querySelector('.ai-error-retry').click();
    });

    await page.waitForFunction(function () {
      return window.AIChatWidget.getState().isLoading === false
        && window.AIChatWidget.getState().isStreaming === false
        && window.AIChatWidget.getState().messageCount === 2;
    });

    var r = await page.evaluate(function () {
      var sr = null;
      for (var i = 0; i < document.body.children.length; i++) {
        var el = document.body.children[i];
        if (el.shadowRoot) { sr = el.shadowRoot; break; }
      }
      return {
        userMessages: sr.querySelectorAll('.ai-msg-user').length,
        assistantText: sr.querySelector('.ai-msg-assistant .ai-msg-bubble')?.textContent || '',
        hasError: !!sr.querySelector('.ai-error-state'),
      };
    });

    expect(requestCount).toBe(2);
    expect(r.userMessages).toBe(1);
    expect(r.assistantText).toContain('Recovered response');
    expect(r.hasError).toBe(false);
  });

});
