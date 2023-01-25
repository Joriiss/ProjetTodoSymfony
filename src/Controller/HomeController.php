<?php
namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findAll();
        return $this->render('index.html.twig', [
            'tasks' => $tasks
        ]);
    }


    #[Route('/add', name:   'app_task_add')]
    public function addTask(Request $request, EntityManagerInterface $em)
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            $this->addFlash(
                'success',
                'Task was added successfully!'
            );

            return $this->redirectToRoute('app_index');
        }

        return $this->render('addTask.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/update/{id}', name: 'app_task_update')]
    public function updateTask(Request $request, ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException(
                'No task found for id ' . $id
            );
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $name = $form->get('name')->getData();
            $description = $form->get('description')->getData();
            $enddate = $form->get('enddate')->getData();

            $task->setName($name);
            $task->setDescription($description);
            $task->setEndDate($enddate);

            $entityManager->flush();

            return $this->redirectToRoute('app_index');
        }
        return $this->render('updateTask.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/remove/{id}', name: 'app_task_remove')]
    public function removeTask(ManagerRegistry $doctrine, int $id, Request $request): Response
    {
        $entityManager = $doctrine->getManager();
        $task = $entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            throw $this->createNotFoundException(
                'No task found for id ' . $id
            );
        }
        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('delete-task' . $task->getId(), $submittedToken)) {
            $entityManager->remove($task);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_index');
    }
}