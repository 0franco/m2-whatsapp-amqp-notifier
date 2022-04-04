<?php

declare(strict_types=1);

namespace OH\WhatsappQueue\Observer\Sales\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OH\Core\Logger\OHLogger;
use OH\WhatsappQueue\Model\Queue\Handler\Scheduler;

class Create implements ObserverInterface
{
    const TOPIC_NAME = 'wp.notify.new.shipment';

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

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment->getOrigData('entity_id')) {
            return;
        }

        $this->scheduler->execute(
            [
                'order' => $shipment->getOrder()->getData(),
                'shipment' => $shipment->getData()
            ],
            self::TOPIC_NAME
        );
    }
}