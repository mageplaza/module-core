<?php


namespace Mageplaza\Core\Cron;


use Mageplaza\Core\Model\ResourceModel\Behavior\Collection;

class BehaviorSubmit
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * BehaviorSubmit constructor.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Cronjob Description
     *
     * @return void
     */
    public function execute()
    {

        $data = [];
        foreach ($this->collection->getItems() as $behavior) {
            $data[] = $behavior->getData();
        }
        \Mageplaza\Core\Helper\BehaviorSubmit::submitData($data);

        $this->collection->getConnection()->truncateTable($this->collection->getMainTable());
    }
}
