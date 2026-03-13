<?php
declare(strict_types=1);

namespace OH\WhatsappQueue\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Scheduler
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly PublisherInterface $publisher,
        private readonly OperationInterfaceFactory $operationFactory,
    ) {}

    public function execute(array $operationData, string $topicName): void
    {
        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($operationData));
        $operation->setStatus(OperationInterface::STATUS_TYPE_OPEN);
        $operation->setTopicName($topicName);

        $this->publisher->publish($topicName, $operation);
    }
}