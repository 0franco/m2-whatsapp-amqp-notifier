<?php
declare(strict_types=1);

namespace OH\WhatsappQueue\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OH\Core\Logger\OHLogger;
use OH\Whatsapp\Model\Gateway\Twilio;
use OH\WhatsappQueue\Observer\Sales\Invoice\Create as InvoiceCreate;
use OH\WhatsappQueue\Observer\Sales\Shipment\Create as ShipmentCreate;

class Consumer
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Twilio $twilio,
        private readonly OHLogger $logger,
        private readonly EntityManager $entityManager,
        private readonly SerializerInterface $serializer,
    ) {}

    public function execute(OperationInterface $operation): void
    {
        try {
            $data = $this->serializer->unserialize($operation->getSerializedData());
            $success = $this->twilio->call(
                $this->resolvePhoneNumber($data),
                $this->resolveMessage($operation->getTopicName(), $data['order']),
            );

            $status = $success
                ? BulkOperationInterface::STATUS_TYPE_COMPLETE
                : BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED;

            $this->updateOperationStatus($operation, $status);
            $this->logger->debug(sprintf('%s executed', self::class));
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    private function resolvePhoneNumber(array $data): string
    {
        if (!empty($data['shipping']['telephone'])) {
            return $data['shipping']['telephone'];
        }

        return $this->orderRepository
            ->get($data['order']['entity_id'])
            ->getShippingAddress()
            ->getTelephone();
    }

    private function resolveMessage(string $topic, array $order): string
    {
        $this->logger->debug('Topic: ' . $topic);

        $template = match ($topic) {
            InvoiceCreate::TOPIC_NAME  => 'Hello %s, your order #%s is being processed.',
            ShipmentCreate::TOPIC_NAME => 'Hello %s, your order #%s is on its way!',
            default                    => 'Hello %s, your order #%s status was updated.',
        };

        return sprintf($template, $order['customer_firstname'], $order['increment_id']);
    }

    private function updateOperationStatus(OperationInterface $operation, int $status): void
    {
        $operation->setStatus($status);
        $this->entityManager->save($operation);
    }
}