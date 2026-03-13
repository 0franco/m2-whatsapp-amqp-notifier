<?php
declare(strict_types=1);

namespace OH\WhatsappQueue\Observer\Sales\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use OH\Core\Logger\OHLogger;
use OH\WhatsappQueue\Model\Queue\Handler\Scheduler;

class Create implements ObserverInterface
{
    public const TOPIC_NAME = 'wp.notify.new.shipment';

    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly OHLogger $logger,
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getOrigData('entity_id')) {
            return;
        }

        try {
            $this->scheduler->execute([
                'order'    => $shipment->getOrder()->getData(),
                'shipment' => $shipment->getData(),
            ], self::TOPIC_NAME);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error scheduling WhatsApp job: %s', $e->getMessage()));
        }
    }
}