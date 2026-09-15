<?php

namespace app\uuid;

class Snowflake
{
    private const EPOCH = 1704067200000;

    private const SEQUENCE_BITS = 12;
    private const WORKER_ID_BITS = 5;
    private const DATACENTER_ID_BITS = 5;

    private const MAX_SEQUENCE = (1 << self::SEQUENCE_BITS) - 1;
    private const MAX_WORKER_ID = (1 << self::WORKER_ID_BITS) - 1;
    private const MAX_DATACENTER_ID = (1 << self::DATACENTER_ID_BITS) - 1;

    private const WORKER_ID_SHIFT = self::SEQUENCE_BITS;
    private const DATACENTER_ID_SHIFT = self::SEQUENCE_BITS + self::WORKER_ID_BITS;
    private const TIMESTAMP_SHIFT = self::SEQUENCE_BITS + self::WORKER_ID_BITS + self::DATACENTER_ID_BITS;

    private int $datacenterId;
    private int $workerId;
    private int $sequence = 0;
    private int $lastTimestamp = -1;

    private static ?self $instance = null;

    public function __construct(int $datacenterId = 0, int $workerId = 0)
    {
        if ($datacenterId < 0 || $datacenterId > self::MAX_DATACENTER_ID) {
            throw new \InvalidArgumentException("datacenter_id must be between 0 and " . self::MAX_DATACENTER_ID);
        }
        if ($workerId < 0 || $workerId > self::MAX_WORKER_ID) {
            throw new \InvalidArgumentException("worker_id must be between 0 and " . self::MAX_WORKER_ID);
        }
        $this->datacenterId = $datacenterId;
        $this->workerId = $workerId;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $datacenterId = (int) config('snowflake.datacenter_id', 0);
            $workerId = (int) config('snowflake.worker_id', 0);
            self::$instance = new self($datacenterId, $workerId);
        }
        return self::$instance;
    }

    public function nextId(): string
    {
        $timestamp = $this->currentTimeMillis();

        if ($timestamp < $this->lastTimestamp) {
            throw new \RuntimeException("Clock moved backwards. Refusing to generate id for " . ($this->lastTimestamp - $timestamp) . " milliseconds");
        }

        if ($timestamp === $this->lastTimestamp) {
            $this->sequence = ($this->sequence + 1) & self::MAX_SEQUENCE;
            if ($this->sequence === 0) {
                $timestamp = $this->waitNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        $id = (($timestamp - self::EPOCH) << self::TIMESTAMP_SHIFT)
            | ($this->datacenterId << self::DATACENTER_ID_SHIFT)
            | ($this->workerId << self::WORKER_ID_SHIFT)
            | $this->sequence;

        return (string) $id;
    }

    private function currentTimeMillis(): int
    {
        return (int) (microtime(true) * 1000);
    }

    private function waitNextMillis(int $lastTimestamp): int
    {
        $timestamp = $this->currentTimeMillis();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->currentTimeMillis();
        }
        return $timestamp;
    }

    public static function generate(): string
    {
        return self::getInstance()->nextId();
    }
}