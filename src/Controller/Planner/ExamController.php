<?php
// src/Controller/Planner/ExamController.php
namespace App\Controller\Planner;

use App\Entity\Planner\Exam;
use App\Form\Planner\ExamType;
use App\Repository\Planner\ExamRepository;
use App\Service\Analyst\ExamAIService;
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
    public function new(Request $request, EntityManagerInterface $em, ExamRepository $examRepository): Response
    {
        $exam = new Exam();
        $exam->setOwner($this->getUser());

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->checkExamOverlap($exam, $examRepository)) {
                $em->persist($exam);
                $em->flush();

                $this->addFlash('success', 'Examen ajouté au calendrier.');
                return $this->redirectToRoute('app_planner_calendar');
            }
        }

        return $this->render('planner/exam/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_planner_exam_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exam $exam, EntityManagerInterface $em, ExamRepository $examRepository): Response
    {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet examen.');
        }

        $form = $this->createForm(ExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->checkExamOverlap($exam, $examRepository)) {
                $em->flush();
                $this->addFlash('success', 'Examen mis à jour.');
                return $this->redirectToRoute('app_planner_calendar');
            }
        }

        return $this->render('planner/exam/edit.html.twig', [
            'form' => $form->createView(),
            'exam' => $exam,
        ]);
    }

    #[Route('/{id}', name: 'app_planner_exam_show', methods: ['GET'])]
    public function show(Exam $exam, ExamAIService $aiService, EntityManagerInterface $em): Response
    {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas voir cet examen.');
        }

        if (empty($exam->getAiRevisionPlan())) {
            $plan = $aiService->generateRevisionPlan($exam);
            $exam->setAiRevisionPlan($plan);
            $em->flush();
        }

        $prepScore = $aiService->calculatePreparationScore($exam, $this->getUser());
        $successProbability = $aiService->predictSuccessProbability($exam, $this->getUser());

        return $this->render('planner/exam/show.html.twig', [
            'exam' => $exam,
            'prepScore' => $prepScore,
            'successProbability' => $successProbability,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_planner_exam_delete', methods: ['POST'])]
    public function delete(Request $request, Exam $exam, EntityManagerInterface $em): Response
    {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet examen.');
        }

        if ($this->isCsrfTokenValid('delete'.$exam->getId(), $request->request->get('_token'))) {
            $em->remove($exam);
            $em->flush();
            $this->addFlash('success', 'Examen supprimé.');
        }

        return $this->redirectToRoute('app_planner_calendar');
    }

    private function checkExamOverlap(Exam $exam, ExamRepository $examRepository): bool
    {
        if (!$exam->getExamDate()) {
            return false;
        }

        $examsThatDay = $examRepository->findExamsByDate($this->getUser(), $exam->getExamDate());

        $newStart = $exam->getExamDate();
        $newEnd = $exam->getDurationMinutes() ? $newStart->modify("+{$exam->getDurationMinutes()} minutes") : $newStart;

        foreach ($examsThatDay as $existingExam) {
            if ($exam->getId() && $existingExam->getId() === $exam->getId()) {
                continue;
            }

            $existingStart = $existingExam->getExamDate();
            $existingEnd = $existingExam->getDurationMinutes() ? $existingStart->modify("+{$existingExam->getDurationMinutes()} minutes") : $existingStart;

            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                $this->addFlash('warning', sprintf('⚠ This exam overlaps with "%s".', $existingExam->getTitle()));
                return true;
            }
        }

        return false;
    }
}