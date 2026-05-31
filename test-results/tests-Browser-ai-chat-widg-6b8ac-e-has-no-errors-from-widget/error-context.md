# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: tests/Browser/ai-chat-widget.spec.cjs >> AI Chat Widget — Visual & Behavioral Tests >> browser console has no errors from widget
- Location: tests/Browser/ai-chat-widget.spec.cjs:465:3

# Error details

```
Error: page.waitForTimeout: Target page, context or browser has been closed
```

```
Error: write EPIPE
```

# Test source

```ts
  376 |   });
  377 | 
  378 |   test('programmatic API open/close/toggle/reset', async ({ page }) => {
  379 |     await page.evaluate(function () { window.AIChatWidget.open(); });
  380 |     await page.waitForTimeout(400);
  381 |     var r = await page.evaluate(function () {
  382 |       var sr = null;
  383 |       for (var i = 0; i < document.body.children.length; i++) {
  384 |         var el = document.body.children[i];
  385 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  386 |       }
  387 |       if (!sr) return false;
  388 |       var win = sr.querySelector('.ai-chat-window');
  389 |       return win && win.classList.contains('open');
  390 |     });
  391 |     expect(r).toBe(true);
  392 | 
  393 |     await page.evaluate(function () { window.AIChatWidget.close(); });
  394 |     await page.waitForTimeout(400);
  395 |     r = await page.evaluate(function () {
  396 |       var sr = null;
  397 |       for (var i = 0; i < document.body.children.length; i++) {
  398 |         var el = document.body.children[i];
  399 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  400 |       }
  401 |       if (!sr) return false;
  402 |       var win = sr.querySelector('.ai-chat-window');
  403 |       return win && win.classList.contains('open');
  404 |     });
  405 |     expect(r).toBe(false);
  406 | 
  407 |     await page.evaluate(function () { window.AIChatWidget.toggle(); });
  408 |     await page.waitForTimeout(400);
  409 |     r = await page.evaluate(function () {
  410 |       var sr = null;
  411 |       for (var i = 0; i < document.body.children.length; i++) {
  412 |         var el = document.body.children[i];
  413 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  414 |       }
  415 |       if (!sr) return false;
  416 |       var win = sr.querySelector('.ai-chat-window');
  417 |       return win && win.classList.contains('open');
  418 |     });
  419 |     expect(r).toBe(true);
  420 | 
  421 |     await page.evaluate(function () { window.AIChatWidget.reset(); });
  422 |     await page.waitForTimeout(200);
  423 |     r = await page.evaluate(function () {
  424 |       var sr = null;
  425 |       for (var i = 0; i < document.body.children.length; i++) {
  426 |         var el = document.body.children[i];
  427 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  428 |       }
  429 |       if (!sr) return null;
  430 |       return !!sr.querySelector('.ai-empty-state');
  431 |     });
  432 |     expect(r).toBe(true);
  433 |   });
  434 | 
  435 |   test('sendMessage via programmatic API works', async ({ page }) => {
  436 |     await page.evaluate(function () { window.AIChatWidget.open(); });
  437 |     await page.waitForTimeout(400);
  438 |     await page.evaluate(function () { window.AIChatWidget.sendMessage('Hello from test'); });
  439 |     await page.waitForTimeout(300);
  440 | 
  441 |     var r = await page.evaluate(function () {
  442 |       var sr = null;
  443 |       for (var i = 0; i < document.body.children.length; i++) {
  444 |         var el = document.body.children[i];
  445 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  446 |       }
  447 |       if (!sr) return false;
  448 |       return sr.querySelectorAll('.ai-msg-user').length > 0;
  449 |     });
  450 |     expect(r).toBe(true);
  451 |   });
  452 | 
  453 |   test('getState returns correct widget state', async ({ page }) => {
  454 |     var state = await page.evaluate(function () { return window.AIChatWidget.getState(); });
  455 |     expect(state).toHaveProperty('isOpen');
  456 |     expect(state).toHaveProperty('isMinimized');
  457 |     expect(state).toHaveProperty('isFullscreen');
  458 |     expect(state).toHaveProperty('isLoading');
  459 |     expect(state).toHaveProperty('isStreaming');
  460 |     expect(state).toHaveProperty('currentConversationId');
  461 |     expect(state).toHaveProperty('messageCount');
  462 |     expect(state.isOpen).toBe(false);
  463 |   });
  464 | 
  465 |   test('browser console has no errors from widget', async ({ page }) => {
  466 |     var errors = [];
  467 |     page.on('console', function (msg) {
  468 |       if (msg.type() === 'error') { errors.push(msg.text()); }
  469 |     });
  470 |     await page.goto(BASE_URL, { waitUntil: 'networkidle' });
  471 |     await page.evaluate(function () { window.AIChatWidget.open(); });
  472 |     await page.waitForTimeout(300);
  473 |     await page.evaluate(function () { window.AIChatWidget.close(); });
  474 |     await page.waitForTimeout(300);
  475 |     await page.evaluate(function () { window.AIChatWidget.toggle(); });
> 476 |     await page.waitForTimeout(300);
      |     ^ Error: write EPIPE
  477 |     var widgetErrors = errors.filter(function (e) {
  478 |       return e.indexOf('AIChatWidget') !== -1 || e.indexOf('ai-chat') !== -1;
  479 |     });
  480 |     expect(widgetErrors.length).toBe(0);
  481 |   });
  482 | 
  483 |   test('launcher has unread badge when widget is closed', async ({ page }) => {
  484 |     var r = await page.evaluate(function () {
  485 |       var sr = null;
  486 |       for (var i = 0; i < document.body.children.length; i++) {
  487 |         var el = document.body.children[i];
  488 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  489 |       }
  490 |       if (!sr) return false;
  491 |       var b = sr.querySelector('.ai-chat-bubble');
  492 |       if (!b) return false;
  493 |       return !!b.querySelector('.ai-badge');
  494 |     });
  495 |     expect(r).toBe(true);
  496 |   });
  497 | 
  498 |   test('badge disappears when widget opens', async ({ page }) => {
  499 |     await page.evaluate(function () { window.AIChatWidget.open(); });
  500 |     await page.waitForTimeout(300);
  501 | 
  502 |     var r = await page.evaluate(function () {
  503 |       var sr = null;
  504 |       for (var i = 0; i < document.body.children.length; i++) {
  505 |         var el = document.body.children[i];
  506 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  507 |       }
  508 |       if (!sr) return false;
  509 |       var b = sr.querySelector('.ai-chat-bubble');
  510 |       if (!b) return false;
  511 |       return !!b.querySelector('.ai-badge');
  512 |     });
  513 |     expect(r).toBe(false);
  514 |   });
  515 | 
  516 |   test('language toggle switches between Arabic and English', async ({ page }) => {
  517 |     await clickSR(page, '.ai-chat-bubble');
  518 |     await page.waitForTimeout(400);
  519 | 
  520 |     var lang = await page.evaluate(function () {
  521 |       var sr = null;
  522 |       for (var i = 0; i < document.body.children.length; i++) {
  523 |         var el = document.body.children[i];
  524 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  525 |       }
  526 |       if (!sr) return { dir: '', btnText: '' };
  527 |       return {
  528 |         dir: sr.querySelector('.ai-chat-window').getAttribute('dir'),
  529 |         btnText: sr.querySelector('.ai-chat-header-lang')?.textContent || '',
  530 |       };
  531 |     });
  532 |     expect(lang.dir).toBe('ltr');
  533 |     expect(lang.btnText).toBe('ع');
  534 | 
  535 |     await clickSR(page, '.ai-chat-lang-btn');
  536 |     await page.waitForTimeout(200);
  537 | 
  538 |     lang = await page.evaluate(function () {
  539 |       var sr = null;
  540 |       for (var i = 0; i < document.body.children.length; i++) {
  541 |         var el = document.body.children[i];
  542 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  543 |       }
  544 |       if (!sr) return { dir: '', btnText: '' };
  545 |       return {
  546 |         dir: sr.querySelector('.ai-chat-window').getAttribute('dir'),
  547 |         btnText: sr.querySelector('.ai-chat-header-lang')?.textContent || '',
  548 |       };
  549 |     });
  550 |     expect(lang.dir).toBe('rtl');
  551 |     expect(lang.btnText).toBe('EN');
  552 | 
  553 |     await clickSR(page, '.ai-chat-lang-btn');
  554 |     await page.waitForTimeout(200);
  555 | 
  556 |     lang = await page.evaluate(function () {
  557 |       var sr = null;
  558 |       for (var i = 0; i < document.body.children.length; i++) {
  559 |         var el = document.body.children[i];
  560 |         if (el.shadowRoot) { sr = el.shadowRoot; break; }
  561 |       }
  562 |       if (!sr) return { dir: '', btnText: '' };
  563 |       return {
  564 |         dir: sr.querySelector('.ai-chat-window').getAttribute('dir'),
  565 |         btnText: sr.querySelector('.ai-chat-header-lang')?.textContent || '',
  566 |       };
  567 |     });
  568 |     expect(lang.dir).toBe('ltr');
  569 |     expect(lang.btnText).toBe('ع');
  570 |   });
  571 | 
  572 |   test('renders streamed markdown tables, lists, links, and code blocks', async ({ page }) => {
  573 |     var markdown = '| Name | Age |\n| --- | --- |\n| Ada | 36 |\n\n- First item\n- Second item\n\n[Example](https://example.com)\n\n```js\nconsole.log("ok");\n```';
  574 | 
  575 |     await page.route('**/api/ai-chat/messages', async function (route) {
  576 |       await route.fulfill({
```