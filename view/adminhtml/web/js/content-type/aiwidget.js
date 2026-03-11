define([
    'Magento_Ui/js/form/components/button',
    'uiRegistry',
    'mage/storage',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'mage/cookies',
    'jquery',
], function (Button, registry, storage, urlBuilder, alert, cookies, $) {
    'use strict';

    return Button.extend({
        defaults: {
            provider: null,
            promptField: 'prompt',
            htmlField: 'html',
            isLoading: false,
            title: 'Generate',
            generatedTitle: 'Generate',
            loadingTitle: 'Generating…',
            generateUrl: null
        },

        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Magento_Ui button trigger
         */
        onClick: function () {
            if (this.isLoading) {
                return;
            }

            let provider = registry.get(this.provider);
            let prompt = provider.get('data.' + this.promptField);
            if (!prompt) {
                alert({content: 'Please enter a prompt.'});
                return;
            }

            this.isLoading = true;
            this.disabled(true);
            this.title(this.loadingTitle);
            document.body.classList.remove('aiwidget-generated');
            document.body.classList.add('aiwidget-loading');
            let formKey = window.FORM_KEY || $.mage.cookies.get('form_key');

            $.ajax({
                url: this.generateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    prompt: prompt,
                    form_key: formKey
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Form-Key': formKey
                }
            }).done(function (response) {
                if (response && response.success && response.content) {
                    provider.set('data.' + this.htmlField, response.content);
                    document.body.classList.add('aiwidget-generated');
                } else {
                    let message = (response && response.error) ? response.error : 'AI returned an empty response.';
                    alert({content: message});
                }
            }.bind(this)).fail(function (jqXHR, textStatus, errorThrown) {
                alert({content: 'AI generation failed. Please check the module configuration.'});
                console.error('Ai content generation error', jqXHR, textStatus, errorThrown);
            }).always(function () {
                this.isLoading = false;
                this.disabled(false);
                this.title(this.generatedTitle);
                document.body.classList.remove('aiwidget-loading');
            }.bind(this));
        }
    });
});
