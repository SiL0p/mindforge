<?php
// src/Controller/Planner/ExamController.php
namespace App\Controller\Planner;

use App\Entity\Planner\Exam;
use App\Form\Planner\ExamType;
use App\Repository\Planner\ExamRepository;
use App\Service\Analyst\ExamAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    // ─── AJAX Table UI endpoints ───────────────────────────────────────────

    #[Route('/ajax/list', name: 'app_planner_exams_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, ExamRepository $examRepository): JsonResponse
    {
        $user   = $this->getUser();
        $filter = $request->query->get('filter', 'all'); // all | 1..10
        $sort   = $request->query->get('sort', '');

        $qb = $examRepository->createQueryBuilder('e')
            ->where('e.owner = :user')
            ->setParameter('user', $user);

        if (is_numeric($filter)) {
            $qb->andWhere('e.importance = :imp')->setParameter('imp', (int)$filter);
        }

        $qb->orderBy('e.' . ($sort === 'date' ? 'examDate' : 'id'), 'ASC');
        $exams = $qb->getQuery()->getResult();

        $rows = [];
        foreach ($exams as $e) {
            $rows[] = [
                'id'          => $e->getId(),
                'title'       => $e->getTitle(),
                'description' => $e->getDescription(),
                'date'        => $e->getExamDate() ? $e->getExamDate()->format('Y-m-d H:i:s') : null,
                'duration'    => $e->getDurationMinutes(),
                'location'    => $e->getLocation(),
                'importance'  => $e->getImportance(),
                'ownerId'     => $e->getOwner()?->getId(),
            ];
        }

        return $this->json($rows);
    }

    #[Route('/ajax/add', name: 'app_planner_exams_ajax_add', methods: ['POST'])]
    public function ajaxAdd(
        Request $request,
        EntityManagerInterface $em,
        ExamRepository $examRepository
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $exam = new Exam();
        $exam->setOwner($user);
        $exam->setTitle(substr(trim($data['title'] ?? ''), 0, 150));
        $exam->setDescription(trim($data['description'] ?? '') ?: null);
        $exam->setLocation(trim($data['location'] ?? '') ?: null);
        $exam->setImportance(max(1, min(10, (int)($data['importance'] ?? 5))));
        $exam->setDurationMinutes(isset($data['duration']) && (int)$data['duration'] > 0 ? (int)$data['duration'] : null);

        if (!empty($data['date'])) {
            try { $exam->setExamDate(new \DateTimeImmutable($data['date'])); } catch (\Exception) {}
        }

        if (empty($exam->getTitle()) || !$exam->getExamDate()) {
            return $this->json(['error' => 'Title and date are required.'], 400);
        }

        if ($this->checkExamOverlapRaw($exam, $examRepository)) {
            return $this->json(['error' => 'This exam overlaps with an existing one.'], 409);
        }

        $em->persist($exam);
        $em->flush();

        return $this->json(['success' => true, 'exam' => $this->serializeExam($exam)]);
    }

    #[Route('/{id}/ajax/edit', name: 'app_planner_exam_ajax_edit', methods: ['POST'])]
    public function ajaxEdit(
        Exam $exam,
        Request $request,
        EntityManagerInterface $em,
        ExamRepository $examRepository
    ): JsonResponse {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title']))       $exam->setTitle(substr(trim($data['title']), 0, 150));
        if (array_key_exists('description', $data)) $exam->setDescription(trim($data['description']) ?: null);
        if (array_key_exists('location', $data))    $exam->setLocation(trim($data['location']) ?: null);
        if (isset($data['importance']))  $exam->setImportance(max(1, min(10, (int)$data['importance'])));
        if (isset($data['duration']))    $exam->setDurationMinutes((int)$data['duration'] > 0 ? (int)$data['duration'] : null);
        if (!empty($data['date'])) {
            try { $exam->setExamDate(new \DateTimeImmutable($data['date'])); } catch (\Exception) {}
        }

        $em->flush();
        return $this->json(['success' => true, 'exam' => $this->serializeExam($exam)]);
    }

    #[Route('/{id}/ajax/delete', name: 'app_planner_exam_ajax_delete', methods: ['DELETE'])]
    public function ajaxDelete(Exam $exam, EntityManagerInterface $em): JsonResponse
    {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], 403);
        }
        $em->remove($exam);
        $em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/ajax/analytics', name: 'app_planner_exam_ajax_analytics', methods: ['GET'])]
    public function ajaxAnalytics(Exam $exam, ExamAIService $aiService): JsonResponse
    {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $successRate = $aiService->predictSuccessProbability($exam, $this->getUser());
        $plan        = $aiService->generateRevisionPlan($exam);

        return $this->json([
            'successRate' => $successRate,
            'plan'        => $plan,
        ]);
    }

    #[Route('/{id}/ajax/chat', name: 'app_planner_exam_ajax_chat', methods: ['POST'])]
    public function ajaxChat(Exam $exam, Request $request, ExamAIService $aiService): JsonResponse
    {
        if ($exam->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Empty message.'], 400);
        }

        $reply = $aiService->chatWithGroq($exam, $message);
        return $this->json(['reply' => $reply]);
    }

    // ─── Private helpers ───────────────────────────────────────────────────

    private function serializeExam(Exam $e): array
    {
        return [
            'id'          => $e->getId(),
            'title'       => $e->getTitle(),
            'description' => $e->getDescription(),
            'date'        => $e->getExamDate() ? $e->getExamDate()->format('Y-m-d H:i:s') : null,
            'duration'    => $e->getDurationMinutes(),
            'location'    => $e->getLocation(),
            'importance'  => $e->getImportance(),
            'ownerId'     => $e->getOwner()?->getId(),
        ];
    }

    private function checkExamOverlapRaw(Exam $exam, ExamRepository $examRepository): bool
    {
        if (!$exam->getExamDate()) return false;
        $examsThatDay = $examRepository->findExamsByDate($this->getUser(), $exam->getExamDate());
        $newStart = $exam->getExamDate();
        $newEnd = $exam->getDurationMinutes() ? $newStart->modify("+{$exam->getDurationMinutes()} minutes") : $newStart;
        foreach ($examsThatDay as $ex) {
            if ($exam->getId() && $ex->getId() === $exam->getId()) continue;
            $exStart = $ex->getExamDate();
            $exEnd   = $ex->getDurationMinutes() ? $exStart->modify("+{$ex->getDurationMinutes()} minutes") : $exStart;
            if ($newStart < $exEnd && $newEnd > $exStart) return true;
        }
        return false;
    }
}