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
         * Magento_Ui button triggers "action" handler via actions config
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

                    let htmlComponent =
                        registry.get('pagebuilder_aiwidget_form.pagebuilder_aiwidget_form.general.' + this.htmlField) ||
                        registry.get(this.provider + '.general.' + this.htmlField);

                    if (htmlComponent && typeof htmlComponent.visible === 'function') {
                        htmlComponent.visible(true);
                    }

                    document.body.classList.add('aiwidget-generated');
                } else {
                    alert({content: 'AI returned an empty response.'});
                }
            }.bind(this)).fail(function (jqXHR, textStatus, errorThrown) {
                alert(
                    {content: `AI generation failed. Please Check the configurations.
                    For more details check in the browser console`}
                );
                console.error('Ai content generation error', jqXHR, textStatus, errorThrown);
            }).always(function () {
                this.isLoading = false;
                this.disabled(false);
                this.title(this.generatedTitle);
            }.bind(this));
        }
    });
});
