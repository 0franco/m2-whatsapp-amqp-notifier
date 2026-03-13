# 📲 OH WhatsApp Queue

> Asynchronous WhatsApp order notifications for Magento 2, powered by Twilio.

Automatically notifies customers via WhatsApp when their order is invoiced or shipped — dispatched through Magento's native message queue for resilience and scalability.

---

## ✨ Features

- 🧾 **Invoice notification** — notifies customer when an order invoice is created
- 🚚 **Shipment notification** — notifies customer when a shipment is dispatched
- ⚡ **Async by design** — leverages Magento's bulk operations queue (no blocking calls)
- 🔁 **Auto-retry** — failed messages are marked retriably failed and re-queued automatically
- 🧩 **Extensible** — add new topics and messages with minimal effort

---

## 📦 Requirements

| Dependency | Version |
|---|---|
| PHP | `>= 8.1` |
| Magento Framework | `*` |
| `oh/whatsapp` | `*` |
| `oh/core` | `*` |

---

## 🚀 Installation

```bash
# Clone into your Magento app/code directory
mkdir -p app/code/OH/WhatsappQueue
git clone https://github.com/oh-commerce/whatsapp-queue.git app/code/OH/WhatsappQueue

# Enable and set up
bin/magento module:enable OH_WhatsappQueue
bin/magento setup:upgrade
```

---

## ⚙️ How It Works

```
Sales Event (Invoice / Shipment)
        │
        ▼
   Observer fires
        │
        ▼
  Scheduler::execute()
  serializes payload → publishes to topic
        │
        ▼
  Consumer::execute()
  resolves phone + message → Twilio::call()
        │
        ▼
  Operation marked COMPLETE or RETRIABLY_FAILED
```

### Topics

| Event | Topic |
|---|---|
| Invoice created | `wp.notify.new.order` |
| Shipment created | `wp.notify.new.shipment` |

---

## 🔧 Running the Consumer

Magento's message queue consumers can be started with:

```bash
bin/magento queue:consumers:start whatsappQueueConsumer
```
---

## 🧪 Adding a New Notification

1. Create a new Observer implementing `ObserverInterface`
2. Define a `public const TOPIC_NAME`
3. Call `Scheduler::execute($payload, self::TOPIC_NAME)`
4. Add a new `match` arm in `Consumer::resolveMessage()`