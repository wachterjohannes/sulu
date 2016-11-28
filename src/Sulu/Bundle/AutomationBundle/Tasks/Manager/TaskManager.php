<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Tasks\Manager;

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\AutomationBundle\Events\Events;
use Sulu\Bundle\AutomationBundle\Events\TaskCreateEvent;
use Sulu\Bundle\AutomationBundle\Events\TaskRemoveEvent;
use Sulu\Bundle\AutomationBundle\Events\TaskUpdateEvent;
use Sulu\Bundle\AutomationBundle\Exception\TaskNotFoundException;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskRepositoryInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Scheduler\TaskSchedulerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages task-entities.
 */
class TaskManager implements TaskManagerInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $repository;

    /**
     * @var TaskSchedulerInterface
     */
    private $scheduler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param TaskRepositoryInterface $repository
     * @param TaskSchedulerInterface $scheduler
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        TaskRepositoryInterface $repository,
        TaskSchedulerInterface $scheduler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->repository = $repository;
        $this->scheduler = $scheduler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        $task = $this->repository->findById($id);
        if (!$task) {
            throw new TaskNotFoundException($id);
        }

        return $task;
    }

    /**
     * {@inheritdoc}
     */
    public function create(TaskInterface $task)
    {
        $task->setId(Uuid::uuid4()->toString());
        $this->scheduler->schedule($task);

        $this->eventDispatcher->dispatch(Events::TASK_CREATE_EVENT, new TaskCreateEvent($task));

        return $this->repository->save($task);
    }

    /**
     * {@inheritdoc}
     */
    public function update(TaskInterface $task)
    {
        $event = $this->eventDispatcher->dispatch(Events::TASK_UPDATE_EVENT, new TaskUpdateEvent($task));
        $this->scheduler->reschedule($task);

        if ($event->isCanceled()) {
            $task = $this->repository->revert($task);
        }

        return $task;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $task = $this->findById($id);
        $this->scheduler->remove($task);

        $event = $this->eventDispatcher->dispatch(Events::TASK_REMOVE_EVENT, new TaskRemoveEvent($task));
        if ($event->isCanceled()) {
            return;
        }

        $this->repository->remove($task);
    }
}
