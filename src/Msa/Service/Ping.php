<?php
namespace Beehive\Msa\Service;

use Log;

/**
 * @name 容器Ping
 * @service Container.Common.Ping
 * @broadcast false
 * @protocol Beehive\Msa\Protocol\Json
 */
class Ping extends Provider
{
    public function invoke()
    {
        $this->response->setData($this->request->getData());
        return $this->send();
    }
}