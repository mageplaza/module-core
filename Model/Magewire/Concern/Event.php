<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Mageplaza\Core\Model\Magewire\Concern;

trait Event
{
    protected $listeners = [];

    public function getListeners(): array
    {
        return $this->listeners;
    }
}
