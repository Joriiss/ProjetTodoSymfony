<?php
namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @param TaskRepository $taskRepository
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route('/', name: 'app_index')]
    public function index(TaskRepository $taskRepository): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('ROLE_USER')) {
            $tasks = $taskRepository->findBy(['userId' => $this->getUser()->getId()]);
            return $this->render('index.html.twig', [
                'tasks' => $tasks
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route('/add', name: 'app_task_add')]
    public function addTask(Request $request, EntityManagerInterface $em)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('ROLE_USER')) {
            $task = new Task();
            $form = $this->createForm(TaskType::class, $task);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userId = $this->getUser();
                $task->setUserId($userId);
                $task->setImage("");
                $em->persist($task);
                $em->flush();

                $uploadedFile = $form['image']->getData();
                if ($uploadedFile != null) {
                    $destination = $this->getParameter('kernel.project_dir') . '/public/upload';
                    $newFilename = "img" . $task->getId() . ".jpg";
                    $uploadedFile->move(
                        $destination,
                        $newFilename
                    );
                    $task->setImage($newFilename);
                    $em->flush();
                }

                $this->addFlash(
                    'success',
                    'Task was added successfully!'
                );
                return $this->redirectToRoute('app_index');
            }

            return $this->render('addTask.html.twig', ['form' => $form->createView()]);
        }
        $this->addFlash(
            'danger',
            'Please login first'
        );
        return $this->redirectToRoute('app_login');
    }

    /**
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @param int $id
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route('/update/{id}', name: 'app_task_update')]
    public function updateTask(Request $request, ManagerRegistry $doctrine, int $id): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('ROLE_USER')) {
            $entityManager = $doctrine->getManager();
            $task = $entityManager->getRepository(Task::class)->find($id);
            $userId = $this->getUser()->getId();
            if (!$task) {
                $this->addFlash(
                    'danger',
                    'Task does not exist'
                );
                return $this->redirectToRoute('app_index');
            }
            if ($task->getUserId()->getId() == $userId) {
                $form = $this->createForm(TaskType::class, $task);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $name = $form->get('name')->getData();
                    $description = $form->get('description')->getData();
                    $enddate = $form->get('enddate')->getData();
                    $image = $form['image']->getData();

                    if ($image != null) {
                        $destination = $this->getParameter('kernel.project_dir') . '/public/upload';
                        $newFilename = "img" . $task->getId() . ".jpg";
                        $image->move(
                            $destination,
                            $newFilename
                        );
                        $task->setImage($newFilename);
                        $entityManager->flush();
                    }


                    $task->setName($name);
                    $task->setDescription($description);
                    $task->setEndDate($enddate);
                    $entityManager->flush();

                    $this->addFlash(
                        'success',
                        'Task updated'
                    );
                    return $this->redirectToRoute('app_index');
                }
                return $this->render('updateTask.html.twig', ['form' => $form->createView()]);
            }
            $this->addFlash(
                'danger',
                'No access to this task'
            );
            return $this->redirectToRoute('app_index');
        }
        $this->addFlash(
            'danger',
            'Please login first'
        );
        return $this->redirectToRoute('app_login');
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param int $id
     * @param Request $request
     * @return Response
     */
    #[Route('/remove/{id}', name: 'app_task_remove')]
    public function removeTask(ManagerRegistry $doctrine, int $id, Request $request): Response
    {
        $entityManager = $doctrine->getManager();
        $task = $entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            $this->addFlash(
                'danger',
                'Task does not exist'
            );
            return $this->redirectToRoute('app_index');
        }

            $submittedToken = $request->request->get('token');
            if ($this->isCsrfTokenValid('delete-task' . $task->getId(), $submittedToken)) {
                $destination = $this->getParameter('kernel.project_dir') . '/public/upload';
                $filename = "img" . $task->getId() . ".jpg";
                $filesystem = new Filesystem();
                $filesystem->remove([$destination . "/" . $filename]);

                $entityManager->remove($task);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Task deleted successfully'
                );
                return $this->redirectToRoute('app_index');
            }
        $this->addFlash(
            'danger',
            'Task can\'t be deleted'
        );
        return $this->redirectToRoute('app_index');
        }
}