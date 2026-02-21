<?php
// src/Controller/Planner/ExamController.php
namespace App\Controller\Planner;

use App\Entity\Planner\Exam;
use App\Form\Planner\ExamType;
use App\Repository\Planner\ExamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planner/exams')]
#[IsGranted('ROLE_USER')]
class ExamController extends AbstractController
{
    #[Route('', name: 'app_planner_exams', methods: ['GET'])]
    public function index(ExamRepository $examRepository): Response
    {
        $exams = $examRepository->findBy(
            ['owner' => $this->getUser()],
            ['examDate' => 'ASC']
        );

        return $this->render('planner/exam/index.html.twig', [
            'exams' => $exams,
            'upcoming' => array_filter($exams, fn($e) => $e->getExamDate() > new \DateTimeImmutable()),
            'past' => array_filter($exams, fn($e) => $e->getExamDate() <= new \DateTimeImmutable()),
        ]);
    }

    #[Route('/new', name: 'app_planner_exam_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $exam = new Exam();
        $exam->setOwner($this->getUser());

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($exam);
            $em->flush();

            $this->addFlash('success', 'Examen ajouté au calendrier.');
            return $this->redirectToRoute('app_planner_calendar');
        }

        return $this->render('planner/exam/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_planner_exam_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exam $exam, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('EXAM_EDIT', $exam);

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Examen mis à jour.');
            return $this->redirectToRoute('app_planner_calendar');
        }

        return $this->render('planner/exam/edit.html.twig', [
            'form' => $form->createView(),
            'exam' => $exam,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_planner_exam_delete', methods: ['POST'])]
    public function delete(Request $request, Exam $exam, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('EXAM_DELETE', $exam);

        if ($this->isCsrfTokenValid('delete'.$exam->getId(), $request->request->get('_token'))) {
            $em->remove($exam);
            $em->flush();
            $this->addFlash('success', 'Examen supprimé.');
        }

        return $this->redirectToRoute('app_planner_calendar');
    }
}