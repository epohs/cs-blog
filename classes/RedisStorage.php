<?php


use Symfony\Component\RateLimiter\Storage\StorageInterface;
//use Symfony\Component\RateLimiter\Storage\Bucket;
use Symfony\Component\RateLimiter\Storage\StorageException;
use Symfony\Component\RateLimiter\LimiterStateInterface;


class RedisStorage implements StorageInterface
{
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Fetch the rate limiter bucket for a given key.
     *
     * @param string $key
     *
     * @return Bucket|null
     */
    public function fetch(string $key): ?LimiterStateInterface
    {
        // Fetch the bucket data from Redis
        $data = $this->redis->get($key);

        if ($data === false) {
            return null;
        }

        // Deserialize the bucket data (you might want to handle it as needed)
        return unserialize($data);
    }





    /**
     * Save the rate limiter state to Redis.
     *
     * @param LimiterStateInterface $limiterState
     */
    public function save(LimiterStateInterface $limiterState): void
    {
        // Here you can convert LimiterStateInterface to the appropriate format to store in Redis
        // In this example, we'll assume you need to serialize the object
        $this->redis->set($limiterState->getId(), serialize($limiterState));
    }



    /**
     * Delete the rate limiter bucket from Redis.
     *
     * @param string $key
     */
    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}
