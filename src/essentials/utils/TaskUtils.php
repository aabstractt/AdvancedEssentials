<?php

declare(strict_types=1);

namespace essentials\utils;

use essentials\Essentials;
use essentials\task\EssentialsTask;

class TaskUtils {

    /** @var int[] */
    private static $tasks = [];

    /**
     * @param EssentialsTask $task
     * @param int $ticks
     */
    public static function scheduleRepeatingTask(EssentialsTask $task, int $ticks = 20): void {
        self::addTask($task);

        Essentials::getInstance()->getScheduler()->scheduleRepeatingTask($task, $ticks);
    }

    /**
     * @param EssentialsTask $task
     * @param int $ticks
     * @param int $period
     */
    public static function scheduleDelayedRepeatingTask(EssentialsTask $task, int $ticks = 20, int $period = 20): void {
        self::addTask($task);

        Essentials::getInstance()->getScheduler()->scheduleDelayedRepeatingTask($task, $ticks, $period);
    }

    /**
     * @param EssentialsTask $task
     */
    private static function addTask(EssentialsTask $task): void {
        self::$tasks[$task->getIdentifier()] = $task->getTaskId();
    }

    /**
     * @param string $identifier
     */
    public static function cancelTask(string $identifier): void {
        $taskId = self::$tasks[$identifier] ?? null;

        if ($taskId === null) return;

        Essentials::getInstance()->getScheduler()->cancelTask($taskId);

        self::removeTask($identifier);
    }

    /**
     * @param string $identifier
     */
    public static function removeTask(string $identifier): void {
        if (!isset(self::$tasks[$identifier])) {
            return;
        }

        unset(self::$tasks[$identifier]);
    }
}