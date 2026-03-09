define(['jquery'], function ($) {
    'use strict';

    var STORAGE_KEY = 'gtstudio_admin_chat';
    var MAX_STORED_MESSAGES = 50;
    var MAX_HISTORY_CONTEXT = 6;

    function loadState(defaultModel) {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {
                messages: [],
                minimized: false,
                totalTokens: 0,
                model: defaultModel,
                provider: 'anthropic'
            };
        } catch (e) {
            return {
                messages: [],
                minimized: false,
                totalTokens: 0,
                model: defaultModel,
                provider: 'anthropic'
            };
        }
    }

    function saveState(state) {
        try {
            if (state.messages.length > MAX_STORED_MESSAGES) {
                state.messages = state.messages.slice(-MAX_STORED_MESSAGES);
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // localStorage unavailable
        }
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

    function renderMessage(msg) {
        var cssClass = msg.role === 'user' ? 'gtai-msg gtai-msg--user' : 'gtai-msg gtai-msg--assistant';
        var content = escapeHtml(msg.content);

        // Convert URLs to links
        content = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="text-decoration: underline; opacity: 0.9;">$1</a>');

        return '<div class="' + cssClass + '">' + content + '</div>';
    }

    function calculateCost(tokens, model, pricingTable) {
        model = (model || '').toLowerCase();
        var table = pricingTable || {};
        if (!Object.prototype.hasOwnProperty.call(table, model)) {
            return null; // unknown model — price not available
        }
        return tokens * table[model] / 1000;
    }

    function formatCost(cost) {
        if (cost === null || cost === undefined) { return '—'; }
        var n = parseFloat(cost) || 0;
        if (n === 0) { return '$0.00'; }
        if (n < 0.01) { return '$' + n.toFixed(4); }
        return '$' + n.toFixed(2);
    }

    function updateTokenDisplay(state, $tokenCount, $costDisplay, pricingTable) {
        var totalTokens = state.totalTokens || 0;
        $tokenCount.text(totalTokens.toLocaleString());

        var cost = calculateCost(totalTokens, state.model, pricingTable);
        $costDisplay.text(formatCost(cost));
    }

    return function (config) {
        var endpointUrl  = config.endpointUrl;
        var formKey      = config.formKey;
        var pricingTable = config.pricingTable || {};
        var defaultModel = config.defaultModel || '';

        var $panel = $('#gtai-chat-panel');
        var $toggle = $('#gtai-chat-toggle');
        var $messages = $('#gtai-chat-messages');
        var $input = $('#gtai-chat-input');
        var $send = $('#gtai-chat-send');
        var $minBtn = $('#gtai-chat-minimize');
        var $clearBtn = $('#gtai-chat-clear');
        var $exportBtn = $('#gtai-chat-export');
        var $tokenCount = $('#gtai-token-count');
        var $costDisplay = $('#gtai-estimated-cost');

        // Export modal elements
        var $modal = $('#gtai-export-modal');
        var $modalClose = $('#gtai-modal-close');
        var $modalCancel = $('#gtai-modal-cancel');
        var $modalDownload = $('#gtai-modal-download');
        var $exportFilename = $('#gtai-export-filename');

        var state = loadState(defaultModel);

        if (state.minimized) {
            $panel.hide();
        } else {
            $panel.show();
        }

        // Render existing messages
        $.each(state.messages, function (_, msg) {
            $messages.append(renderMessage(msg));
        });

        // Update token display
        updateTokenDisplay(state, $tokenCount, $costDisplay, pricingTable);
        $messages.scrollTop($messages[0].scrollHeight);

        // Toggle minimize
        $toggle.on('click', function () {
            state.minimized = !state.minimized;
            $panel.toggle();
            saveState(state);
        });

        $minBtn.on('click', function (e) {
            e.stopPropagation();
            state.minimized = true;
            $panel.hide();
            saveState(state);
        });

        // Clear conversation
        $clearBtn.on('click', function (e) {
            e.preventDefault();
            if (confirm('Clear all messages? This cannot be undone.')) {
                state.messages = [];
                state.totalTokens = 0;
                $messages.empty();
                $messages.append(
                    '<div class="gtai-welcome-message">' +
                    '<div class="gtai-welcome-icon">⚡</div>' +
                    '<p>Ask questions about your store data.</p>' +
                    '<small>Examples: "Top 10 products", "Revenue trends"</small>' +
                    '</div>'
                );
                updateTokenDisplay(state, $tokenCount, $costDisplay, pricingTable);
                saveState(state);
            }
        });

        // Export functionality
        $exportBtn.on('click', function (e) {
            e.preventDefault();

            if (state.messages.length === 0) {
                alert('No conversation to export.');
                return;
            }

            // Update modal with current data
            $('#gtai-export-msg-count').text(state.messages.length);
            $('#gtai-export-token-count').text((state.totalTokens || 0).toLocaleString());
            $('#gtai-export-cost-display').text(formatCost(calculateCost(state.totalTokens || 0, state.model, pricingTable)));

            $modal.addClass('active');
        });

        // Close modal
        function closeModal() {
            $modal.removeClass('active');
        }

        $modalClose.on('click', closeModal);
        $modalCancel.on('click', closeModal);

        // Download conversation
        $modalDownload.on('click', function (e) {
            e.preventDefault();

            var format = $('input[name="export-format"]:checked').val() || 'json';
            var filename = $exportFilename.val().trim() || 'conversation';

            if (format === 'json') {
                filename = filename + '.json';
            } else {
                filename = filename + '.txt';
            }

            var content;
            if (format === 'json') {
                content = window.gtaiExporter.exportAsJSON(state.messages, {
                    model: state.model,
                    provider: state.provider,
                    total_tokens: state.totalTokens,
                    estimated_cost: formatCost(calculateCost(state.totalTokens, state.model, pricingTable))
                });
                window.gtaiExporter.downloadFile(content, filename, 'application/json');
            } else {
                content = window.gtaiExporter.exportAsTXT(state.messages, {
                    model: state.model,
                    provider: state.provider,
                    total_tokens: state.totalTokens,
                    estimated_cost: formatCost(calculateCost(state.totalTokens, state.model, pricingTable))
                });
                window.gtaiExporter.downloadFile(content, filename, 'text/plain');
            }

            closeModal();
        });

        // Close modal on overlay click
        $modal.on('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        function sendMessage() {
            var text = $input.val().trim();
            if (!text) {
                return;
            }

            var historyContext = state.messages.slice(-MAX_HISTORY_CONTEXT);

            $input.val('');
            $send.prop('disabled', true).html(
                '<svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;">' +
                '<circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" opacity="0.3"/>' +
                '<circle cx="12" cy="2" r="1.5" fill="currentColor"/></svg>'
            );

            var userMsg = {
                role: 'user',
                content: text,
                timestamp: new Date().toISOString()
            };
            state.messages.push(userMsg);
            $messages.html('');
            $.each(state.messages, function (_, msg) {
                $messages.append(renderMessage(msg));
            });
            $messages.scrollTop($messages[0].scrollHeight);

            $.ajax({
                url: endpointUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    message: text,
                    history: JSON.stringify(historyContext),
                    form_key: formKey
                }
            }).done(function (response) {
                var content = (response && response.success && response.content)
                    ? response.content
                    : 'No response received.';

                var tokens = response && response.tokens ? parseInt(response.tokens, 10) : Math.ceil(content.length / 4);
                state.totalTokens += tokens;

                if (response && response.model)    { state.model    = response.model; }
                if (response && response.provider) { state.provider = response.provider; }

                var assistantMsg = {
                    role: 'assistant',
                    content: content,
                    timestamp: new Date().toISOString(),
                    tokens: tokens
                };
                state.messages.push(assistantMsg);
                $messages.append(renderMessage(assistantMsg));
                $messages.scrollTop($messages[0].scrollHeight);
                updateTokenDisplay(state, $tokenCount, $costDisplay, pricingTable);
                saveState(state);
            }).fail(function () {
                var errMsg = {
                    role: 'assistant',
                    content: 'Request failed. Please try again.',
                    timestamp: new Date().toISOString()
                };
                state.messages.push(errMsg);
                $messages.append(renderMessage(errMsg));
                $messages.scrollTop($messages[0].scrollHeight);
                saveState(state);
            }).always(function () {
                $send.prop('disabled', false).html(
                    '<svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">' +
                    '<path d="M16.6915026,12.4744748 L3.50612381,13.2599618 C3.19218622,13.2599618 3.03521743,13.4170592 3.03521743,13.5741566 L1.15159189,20.0151496 C0.8376543,20.8006365 0.99,21.89 1.77946707,22.52 C2.41,22.99 3.50612381,23.1 4.13399899,22.8429026 L21.714504,14.0454487 C22.6563168,12.6563168 22.6563168,11.3436832 21.714504,9.95455133 L4.13399899,1.16346272 C3.34915502,0.9 2.40734225,1.00636533 1.77946707,1.4830188 C0.994623095,2.10604706 0.837654299,3.0744191 1.15159189,3.98404026 L3.03521743,10.4084696 C3.03521743,10.5655670 3.34915502,10.7226644 3.50612381,10.7226644 L16.6915026,11.5081513 C16.6915026,11.5081513 17.1272231,11.5081513 17.1272231,12.0848049 C17.1272231,12.3429026 17.1272231,12.4744748 16.6915026,12.4744748 Z"/>' +
                    '</svg>'
                );
            });
        }

        $send.on('click', sendMessage);

        $input.on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    };
});

