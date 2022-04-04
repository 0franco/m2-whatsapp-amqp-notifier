<?php
declare(strict_types=1);

namespace OH\WhatsappQueue\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Scheduler
{
    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var OperationInterfaceFactory
     */
    private OperationInterfaceFactory $operationFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer,
        PublisherInterface $publisher,
        OperationInterfaceFactory $operationFactory
    ) {
        $this->serializer = $serializer;
        $this->publisher = $publisher;
        $this->operationFactory = $operationFactory;
    }

    /**
     * Schedule job
     *
     * @param $operationData
     * @param $topicName
     * @return void
     */
    public function execute($operationData, $topicName): void
    {
        $operation = $this->operationFactory->create();
        $operation->setSerializedData($this->serializer->serialize($operationData));
        $operation->setStatus(OperationInterface::STATUS_TYPE_OPEN);
        $operation->setTopicName($topicName);
        $this->publisher->publish($topicName, $operation);
    }
}