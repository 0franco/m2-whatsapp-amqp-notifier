<?php

declare(strict_types=1);

namespace OH\WhatsappQueue\Observer\Sales\Invoice;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OH\Core\Logger\OHLogger;
use OH\WhatsappQueue\Model\Queue\Handler\Scheduler;

class Create implements ObserverInterface
{
    const TOPIC_NAME = 'wp.notify.new.order';

    /**
     * @var Scheduler
     */
    private Scheduler $scheduler;

    /**
     * @var OHLogger
     */
    private OHLogger $logger;

    public function __construct(
        Scheduler $scheduler,
        OHLogger $logger
    ) {
        $this->scheduler = $scheduler;
        $this->logger = $logger;
    }

    /**
     * Schedule new job
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getOrder();

            $this->scheduler->execute(
                [
                    'order' => $order->getData(),
                    'shipping' => $order->getShippingAddress()->getData(),
                    'billing' => $order->getBillingAddress()->getData(),
                    'invoice' => $order->getInvoiceCollection()->getFirstItem()->getData(),
                ],
                self::TOPIC_NAME
            );
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Error scheduling new whatsapp job, error: %s', $exception->getMessage()));
        }
    }
}