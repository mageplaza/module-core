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
     * @var \Mageplaza\Core\Helper\BehaviorSubmit
     */
    protected $behaviorSubmit;

    /**
     * BehaviorSubmit constructor.
     *
     * @param Collection $collection
     * @param \Mageplaza\Core\Helper\BehaviorSubmit $behaviorSubmit
     */
    public function __construct(
        Collection $collection,
        \Mageplaza\Core\Helper\BehaviorSubmit $behaviorSubmit
    ) {
        $this->collection     = $collection;
        $this->behaviorSubmit = $behaviorSubmit;
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
        $data = array_merge($data, $this->behaviorSubmit->getDataFormCache() ?? []);
        \Mageplaza\Core\Helper\BehaviorSubmit::submitData($data);

        //clean cache and DB
        $this->behaviorSubmit->clearCacheBehavior();
        $this->collection->getConnection()->truncateTable($this->collection->getMainTable());
    }
}
