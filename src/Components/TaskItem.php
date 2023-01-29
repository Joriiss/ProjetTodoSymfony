<?php
namespace App\Components;

use App\Entity\Task;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('taskitem')]
class TaskItem
{
    public Task $task;
}