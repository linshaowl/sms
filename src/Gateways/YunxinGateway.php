<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;

class YunxinGateway extends \Overtrue\EasySms\Gateways\YunxinGateway
{
    /**
     * {@inheritdoc}
     */
    public function send($phoneContainer, MessageInterface $message, Config $config)
    {
        $to = current($phoneContainer);

        $data = $message->getData($this);

        $action = isset($data['action']) ? $data['action'] : self::ENDPOINT_ACTION;

        $endpoint = $this->buildEndpoint('sms', $action);

        switch ($action) {
            case 'sendCode':
                $params = $this->buildSendCodeParams($to, $message, $config);

                break;
            case 'verifyCode':
                $params = $this->buildVerifyCodeParams($to, $message);

                break;
            case 'sendTemplate':
                $params = $this->buildSendTemplateParams($phoneContainer, $message, $config);

                break;
            default:
                throw new GatewayErrorException(sprintf('action: %s not supported', $action), 0);
        }

        $headers = $this->buildHeaders($config);

        try {
            $result = $this->post($endpoint, $params, $headers);

            if (!isset($result['code']) || self::SUCCESS_CODE !== $result['code']) {
                $code = isset($result['code']) ? $result['code'] : 0;
                $error = isset($result['msg']) ? $result['msg'] : json_encode($result, JSON_UNESCAPED_UNICODE);

                throw new GatewayErrorException($error, $code);
            }
        } catch (\Exception $e) {
            throw new GatewayErrorException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    protected function buildSendTemplateParams($phoneContainer, MessageInterface $message, Config $config)
    {
        $phoneNumbers = [];
        foreach ($phoneContainer as $phone => $class) {
            $phoneNumbers[] = $class->getUniversalNumber();
        }
        $phoneNumbers = json_encode($phoneNumbers);

        $data = $message->getData($this);
        $template = $message->getTemplate($this);

        $res = [
            'mobiles' => $phoneNumbers,
            'templateid' => is_string($template) ? $template : '',
            'codeLen' => $config->get('code_length', 4),
            'needUp' => $config->get('need_up', false),
        ];

        if (!empty($data)) {
            $res['params'] = json_encode($data);
        }

        return $res;
    }
}
