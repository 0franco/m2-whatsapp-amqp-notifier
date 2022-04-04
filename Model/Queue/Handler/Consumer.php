<?php
declare(strict_types=1);

namespace OH\WhatsappQueue\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Bulk\OperationInterface as OperationBulkInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OH\Core\Logger\OHLogger;
use OH\Whatsapp\Model\Gateway\Twilio;

class Consumer
{
    /**
     * @var OHLogger
     */
    private OHLogger $logger;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var Twilio
     */
    private Twilio $twilio;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Twilio $twilio,
        OHLogger $logger,
        EntityManager $entityManager,
        SerializerInterface $serializer
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->twilio = $twilio;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * Run operation
     *
     * @return void
     */
    public function execute(OperationInterface $operation): void
    {
        try {
            $unserializedData = $this->serializer->unserialize($operation->getSerializedData());
            $order = $unserializedData['order'];
            $sendResponse = $this->twilio->call($this->getPhoneNumber($unserializedData), $this->getMessageByTopic($operation, $order));
            $this->changeOperationStatus($operation, $sendResponse ? OperationBulkInterface::STATUS_TYPE_COMPLETE : OperationBulkInterface::STATUS_TYPE_RETRIABLY_FAILED);
            $this->logger->debug(sprintf('Consumer %s executed', get_class($this)));
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Retrieve phone number from order
     *
     * @param $data
     * @return string
     */
    private function getPhoneNumber($data): string
    {
        if (!empty($data['shipping'])) {
            return $data['shipping']['telephone'];
        }

        $order = $this->orderRepository->get($data['order']['entity_id']);
        return $order->getShippingAddress()->getTelephone();
    }

    /**
     * Get right message for topic
     *
     * @param $op
     * @param $order
     * @return string
     */
    private function getMessageByTopic($op, $order): string
    {
        $this->logger->debug('TOPIC NAME: ' . $op->getTopicName());

        switch ($op->getTopicName()) {
            case \OH\WhatsappQueue\Observer\Sales\Invoice\Create::TOPIC_NAME:
                return sprintf(
                    'Hello %s, your order #%s is being processing',
                    $order['customer_firstname'],
                    $order['increment_id']
                );
            case \OH\WhatsappQueue\Observer\Sales\Shipment\Create::TOPIC_NAME:
                return sprintf(
                    'Hello %s, your order #%s is on its way!',
                    $order['customer_firstname'],
                    $order['increment_id']
                );
            default:
                return sprintf(
                    'Hello %s, your order #%s status was updated',
                    $order['customer_firstname'],
                    $order['increment_id']
                );
        }
    }

    /**
     * Change operation status after process
     *
     * @param $op
     * @param $status
     * @return void
     * @throws \Exception
     */
    private function changeOperationStatus($op, $status): void
    {
        $op->setStatus($status);
        $this->entityManager->save($op);
    }
}
