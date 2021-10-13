<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms;

use Lswl\Sms\Contracts\CacheInterface;
use Lswl\Sms\Exceptions\SmsException;
use Lswl\Sms\Utils\FormatPhone;
use Lswl\Sms\Utils\SmsCache;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class Sms
{
    /**
     * 缓存实例
     * @var CacheInterface
     */
    protected $cache;

    /**
     * 短信缓存工具
     * @var SmsCache
     */
    protected $smsCache;

    /**
     * 短信缓存工具容器
     * @var array
     */
    protected $smsCacheContainer;

    /**
     * 配置数组
     * @var array
     */
    protected $config;

    /**
     * @var EasySms
     */
    protected $easySms;

    /**
     * 发送手机号容器
     * @var array
     */
    protected $phoneContainer;

    /**
     * 发送类型
     * @var string
     */
    protected $type = '';

    /**
     * 发送验证码
     * @var string
     */
    protected $code;

    /**
     * @var MessageInterface|array
     */
    protected $message;

    /**
     * @var array
     */
    protected $gateways = [];

    /**
     * 使用缓存
     * @var bool
     */
    protected $useCache = true;

    public function __construct(CacheInterface $cache, array $config)
    {
        $this->cache = $cache;
        $this->smsCache = new SmsCache($this->cache);
        $this->config = $config;
    }

    /**
     * 设置手机号
     * @param string|array|PhoneNumberInterface $phone
     * @param string $delimiter
     * @return $this
     */
    public function setPhone($phone, string $delimiter = ',')
    {
        $this->phoneContainer = FormatPhone::run($phone, $delimiter);
        return $this;
    }

    /**
     * 设置发送类型
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;
        $this->smsCache->setType($type);
        return $this;
    }

    /**
     * 设置发送验证码
     * @param string $code
     * @return $this
     */
    public function setCode(string $code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置发送消息
     * @param MessageInterface|array $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置网关
     * @param array $gateways
     * @return $this
     */
    public function setGateways(array $gateways)
    {
        $this->gateways = $gateways;
        return $this;
    }

    /**
     * 设置发送间隔
     * @param array $interval
     * @return $this
     */
    public function setInterval(array $interval)
    {
        $this->smsCache->setInterval($interval);
        return $this;
    }

    /**
     * 设置有效期
     * @param int $sec
     * @return $this
     */
    public function setValidTime(int $sec)
    {
        $this->smsCache->setValidTime($sec);
        return $this;
    }

    /**
     * 使用缓存
     * @param bool $use
     * @return $this
     */
    public function useCache(bool $use)
    {
        $this->useCache = $use;
        return $this;
    }

    /**
     * 发送
     * @param bool $debug
     * @return array
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     * @throws SmsException
     */
    public function send(bool $debug = false): array
    {
        // 发送检测
        $this->sendCheck();

        // 获取锁定 key
        $lockKey = $this->getLockKey();
        // 验证是否被锁定
        if (!$this->cache->lock($lockKey)) {
            throw new SmsException('Send frequently, please try again later', ErrorCode::SEND_FREQUENTLY);
        }

        // 调用 easySms 发送短信
        !$debug && $this->getEasySms()->send($this->phoneContainer, $this->message, $this->gateways);

        $res = [];
        foreach ($this->phoneContainer as $phone => $class) {
            // 设置发送成功缓存
            $this->useCache && $this->smsCacheContainer[$phone]->send($this->code);

            $res[$phone] = $this->useCache ? $this->smsCacheContainer[$phone]->sendInterval() : 0;
        }

        // 解除锁定
        $this->cache->unlock($lockKey);

        return $res;
    }

    /**
     * 获取 EasySms 实例
     * @return EasySms
     */
    public function getEasySms(): EasySms
    {
        return $this->easySms ?: $this->easySms = new EasySms($this->config);
    }

    /**
     * 发送检测
     * @throws SmsException
     */
    protected function sendCheck()
    {
        // 默认设置验证码为0
        if (is_null($this->code)) {
            $this->setCode(0);
        }

        foreach ($this->phoneContainer as $phone => $class) {
            if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
                throw new SmsException('Incorrect phone number format.', ErrorCode::INVALID_PHONE_FORMAT, [
                    'phone' => $phone,
                ]);
            }

            // 设置缓存对象容器
            if (empty($this->smsCacheContainer[$phone])) {
                $this->smsCacheContainer[$phone] = clone $this->smsCache->setPhone(
                    $phone
                );
            }

            // 缓存发送检测
            $this->useCache && $this->smsCache->sendCheck();
        }

        if (is_null($this->message)) {
            throw new SmsException('Sending a message must.', ErrorCode::MESSAGE_MUST);
        }
    }

    /**
     * 获取锁定key
     * @return string
     */
    protected function getLockKey(): string
    {
        $res = $this->message;

        if ($res instanceof MessageInterface) {
            $res = json_encode($res->getData());
        } elseif (is_array($res)) {
            $res = json_encode($res);
        }

        return 'sms:lock:' . md5(json_encode($this->phoneContainer) . $res);
    }
}
