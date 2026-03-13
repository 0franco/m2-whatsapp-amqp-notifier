<?php
declare(strict_types=1);

namespace OH\WhatsappQueue\Observer\Sales\Invoice;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use OH\Core\Logger\OHLogger;
use OH\WhatsappQueue\Model\Queue\Handler\Scheduler;

class Create implements ObserverInterface
{
    public const TOPIC_NAME = 'wp.notify.new.order';

    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly OHLogger $logger,
    ) {}

    public function execute(Observer $observer): void
    {
        try {
            /** @var Order $order */
            $order = $observer->getOrder();

            $this->scheduler->execute([
                'order'    => $order->getData(),
                'shipping' => $order->getShippingAddress()->getData(),
                'billing'  => $order->getBillingAddress()->getData(),
                'invoice'  => $order->getInvoiceCollection()->getFirstItem()->getData(),
            ], self::TOPIC_NAME);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error scheduling WhatsApp job: %s', $e->getMessage()));
        }
    }
}