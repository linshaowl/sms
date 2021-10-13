<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\Sms\Utils;

use Lswl\Sms\Contracts\CacheInterface;
use Lswl\Sms\ErrorCode;
use Lswl\Sms\Exceptions\SmsException;

class SmsCache
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $codeCacheKey = 'sms:code_cache:%s';

    /**
     * @var string
     */
    protected $numCacheKey = 'sms:num_cache:%s';

    /**
     * @var array
     */
    protected $codeFormat = [
        'code' => 0,
        'time' => 0,
    ];

    /**
     * @var array
     */
    protected $numFormat = [
        'num' => 0,
        'time' => 0,
    ];

    /**
     * 发送间隔时间
     * @var array
     */
    protected $interval = [
        1 => 60,
        2 => 180,
        3 => 600,
    ];

    /**
     * 有效期(秒)
     * @var int
     */
    protected $validTime = 1800;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @var string
     */
    protected $type = '';

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 设置发送间隔
     * @param array $interval
     * @return $this
     */
    public function setInterval(array $interval)
    {
        $this->interval = $interval;
        return $this;
    }

    /**
     * 设置有效期
     * @param int $sec
     * @return $this
     */
    public function setValidTime(int $sec)
    {
        $this->validTime = $sec;
        return $this;
    }

    /**
     * 设置手机号
     * @param string $phone
     * @return $this
     */
    public function setPhone(string $phone)
    {
        $this->phone = $phone;
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
        return $this;
    }

    /**
     * 发送检测
     * @throws SmsException
     * @return void
     */
    public function sendCheck()
    {
        $data = $this->getNumCache();
        $diff = time() - $data['time'];
        $getInterval = $this->getInterval($data['num']);

        if ($getInterval > $diff) {
            $interval = $getInterval - $diff;
            throw new SmsException(
                sprintf(
                    'SMS failed, please try again after %d seconds',
                    $interval
                ),
                ErrorCode::WAIT_INTERVAL,
                [
                    'phone' => $this->phone,
                    'interval' => $interval,
                ]
            );
        }
    }

    /**
     * 发送
     * @param string $code
     * @return void
     */
    public function send(string $code)
    {
        // 当前时间
        $time = time();
        // 设置code缓存
        $this->setCodeCache($code, $time);
        // 设置次数缓存
        $this->setNumCache($time);
    }

    /**
     * 使用验证码
     * @return bool
     */
    public function useCode(): bool
    {
        // 获取验证码缓存标识
        $key = $this->getCodeCacheKey();
        if (!$this->cache->exists($key)) {
            return true;
        }

        // 获取验证码缓存数据
        $data = $this->getCodeCache();
        if (!empty($data['code'])) {
            $this->cache->del($key);
        }

        return true;
    }

    /**
     * 验证
     * @param string $code
     * @throws SmsException
     * @return void
     */
    public function verify(string $code)
    {
        // 获取验证码缓存数据
        $data = $this->getCodeCache();

        if (empty($data['code'])) {
            throw new SmsException('Invalid verification code', ErrorCode::INVALID_CODE);
        } elseif ($data['code'] != $code) {
            throw new SmsException('The verification code is not correct', ErrorCode::CODE_NOT_CORRECT);
        }
    }

    /**
     * 发送间隔
     * @return int|mixed
     */
    public function sendInterval()
    {
        $data = $this->getNumCache();
        return $this->getInterval($data['num']);
    }

    /**
     * 设置验证码缓存
     * @param string $code
     * @param int $time
     * @return bool
     */
    protected function setCodeCache(string $code, int $time): bool
    {
        // 发送验证码
        $key = $this->getCodeCacheKey();
        $res = $this->cache->set($key, [
            'code' => $code,
            'time' => $time,
        ]);

        // 设置验证码过期时间
        if ($res) {
            $this->cache->expire($key, $this->validTime);
        }

        return $res;
    }

    /**
     * 获取验证码缓存
     * @return array
     */
    protected function getCodeCache(): array
    {
        $data = $this->cache->get($this->getCodeCacheKey());
        return !empty($data) ? $data : $this->codeFormat;
    }

    /**
     * 设置次数缓存
     * @param int $time
     * @return bool
     */
    protected function setNumCache(int $time): bool
    {
        $key = $this->getNumCacheKey();
        $exists = $this->cache->exists($key);

        // 保存数据
        $data = $this->getNumCache();
        $data['num']++;
        $data['time'] = $time;

        // 设置发送次数
        $res = $this->cache->set($key, $data);

        // 不存在时设置缓存
        if (!$exists && $res) {
            $this->cache->expire($key, $this->getExpireTime());
        }

        return $res;
    }

    /**
     * 获取次数缓存
     * @return array
     */
    protected function getNumCache(): array
    {
        $data = $this->cache->get($this->getNumCacheKey());
        return !empty($data) ? $data : $this->numFormat;
    }

    /**
     * 获取过期时间
     * @return int
     */
    protected function getExpireTime(): int
    {
        return intval(strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')))) - time();
    }

    /**
     * 获取间隔时间
     * @param int $num
     * @return int|mixed
     */
    protected function getInterval(int $num)
    {
        // 当前次数间隔时间存在
        if (!empty($this->interval[$num])) {
            return $this->interval[$num];
        }

        // 判断最大值
        if ($num >= array_search(max($this->interval), $this->interval, true)) {
            return max($this->interval);
        }

        $interval = 60;
        foreach ($this->interval as $k => $v) {
            if ($k > $num) {
                break;
            }
            $interval = $v;
        }
        return $interval;
    }

    /**
     * 获取验证码缓存标识
     * @return string
     */
    protected function getCodeCacheKey(): string
    {
        return sprintf(
            $this->codeCacheKey,
            !empty($this->type) ? $this->type . '_' . $this->phone : $this->phone
        );
    }

    /**
     * 获取发送数量缓存标识
     * @return string
     */
    protected function getNumCacheKey(): string
    {
        return sprintf(
            $this->numCacheKey,
            !empty($this->type) ? $this->type . '_' . $this->phone : $this->phone
        );
    }
}
