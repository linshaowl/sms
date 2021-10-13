<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms;

use Overtrue\EasySms\Contracts\GatewayInterface;

class EasySms extends \Overtrue\EasySms\EasySms
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, $message, array $gateways = [])
    {
        $message = $this->formatMessage($message);
        $gateways = empty($gateways) ? $message->getGateways() : $gateways;

        if (empty($gateways)) {
            $gateways = $this->config->get('default.gateways', []);
        }

        return $this->getMessenger()->send($phoneContainer, $message, $this->formatGateways($gateways));
    }

    /**
     * {@inheritdoc}
     */
    public function getMessenger()
    {
        return $this->messenger ?: $this->messenger = new Messenger($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatGatewayClassName($name)
    {
        if (\class_exists($name) && \in_array(GatewayInterface::class, \class_implements($name))) {
            return $name;
        }

        $name = \ucfirst(\str_replace(['-', '_', ''], '', $name));

        return __NAMESPACE__ . "\\Gateways\\{$name}Gateway";
    }
}
