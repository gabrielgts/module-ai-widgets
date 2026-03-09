# Gtstudio_AiWidgets

AI-powered admin widgets for Magento 2. Provides a floating chat assistant on every admin page and an AI content generator inside PageBuilder HTML blocks.

## What It Does

- **Floating Chat Widget** — a persistent chat panel in the Magento admin, powered by the `admin_assistant` agent
- **PageBuilder AI Generator** — an *AI Generate* button inside PageBuilder HTML blocks to create or rewrite content via the `page_builder_generator` agent
- Token and cost estimation displayed in real time for every message

## Requirements

- Magento 2.4.4+
- PHP 8.1+
- `Gtstudio_AiConnector` enabled and configured
- `Gtstudio_AiAgents` enabled
- `Magento_PageBuilder` (bundled with Adobe Commerce; available as OSS extension for Community)

## Installation

```bash
php bin/magento module:enable Gtstudio_AiWidgets
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f --area adminhtml
php bin/magento cache:flush
```

## Setup

### Admin Chat Assistant

1. Go to *AI Studio → Agents → Add New*
2. Set **Code** to `admin_assistant`
3. Fill in Background, Steps, and Output Instructions to define the assistant's persona
4. Save — the widget appears automatically for users with the `Gtstudio_AiWidgets::management` ACL resource

### PageBuilder Generator

1. Create an agent with code `page_builder_generator`
2. In PageBuilder, open any HTML block — an **AI Generate** button appears in the toolbar
3. Type a prompt and click Generate; the result is inserted into the block

## Extensibility

### Swapping the agent used by the chat widget

The agent code is hardcoded as `admin_assistant` in `AdminChatService`. Override the service via DI preference to change this or add additional logic:

```xml
<!-- etc/di.xml -->
<preference for="Gtstudio\AiWidgets\Model\Service\AdminChatService"
            type="Vendor\Module\Model\Service\CustomAdminChatService"/>
```

### Customising the chat widget template

Override via standard Magento layout XML:

```xml
<!-- view/adminhtml/layout/default.xml -->
<page>
    <body>
        <referenceBlock name="gtstudio.ai.chat.widget">
            <action method="setTemplate">
                <argument name="template" xsi:type="string">
                    Vendor_Module::chat/custom-widget.phtml
                </argument>
            </action>
        </referenceBlock>
    </body>
</page>
```

The default templates are in:

```
view/adminhtml/templates/chat/widget.phtml
view/adminhtml/web/js/admin-chat.js
```

### Adding token pricing for a custom model

Extend `TokenCostService` via `di.xml` — see the `Gtstudio_AiConnector` README for the full pattern.

## ACL Resources

| Resource | Controls |
|----------|---------|
| `Gtstudio_AiWidgets::management` | Access to all widget features and the chat API endpoint |
