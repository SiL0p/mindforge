<?php

namespace App\Controller\Carriere;

use App\Entity\Carriere\OpportuniteCarriere;
use App\Service\QuizGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/carriere/quiz')]
#[IsGranted('ROLE_USER')]
class QuizController extends AbstractController
{
    #[Route('/{id}', name: 'app_carriere_quiz_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        OpportuniteCarriere $opportunity,
        Request $request,
        QuizGeneratorService $quizGenerator,
    ): Response {
        $session = $request->getSession();
        $id = $opportunity->getId();

        // Already passed → go directly to apply
        if ($session->get("quiz_{$id}_passed")) {
            return $this->redirectToRoute('app_carriere_demande_apply', ['id' => $id]);
        }

        $attempts = (int) ($session->get("quiz_{$id}_attempts") ?? 0);

        // Attempts exhausted → show locked result
        if ($attempts >= 3) {
            return $this->render('carriere/quiz/result.html.twig', [
                'opportunity' => $opportunity,
                'score'       => null,
                'passed'      => false,
                'exhausted'   => true,
                'attempts'    => $attempts,
            ]);
        }

        // Generate questions if not yet in session
        if (!$session->has("quiz_{$id}_questions")) {
            try {
                $questions = $quizGenerator->generate($opportunity);
                $session->set("quiz_{$id}_questions", $questions);
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Could not generate quiz questions. Please try again later.');
                return $this->redirectToRoute('app_carriere_opportunite_show', ['id' => $id]);
            }
        }

        $questions = $session->get("quiz_{$id}_questions");

        return $this->render('carriere/quiz/show.html.twig', [
            'opportunity' => $opportunity,
            'questions'   => $questions,
            'attempts'    => $attempts,
        ]);
    }

    #[Route('/{id}/submit', name: 'app_carriere_quiz_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function submit(
        OpportuniteCarriere $opportunity,
        Request $request,
    ): Response {
        $session = $request->getSession();
        $id = $opportunity->getId();

        // Guard: already passed
        if ($session->get("quiz_{$id}_passed")) {
            return $this->redirectToRoute('app_carriere_demande_apply', ['id' => $id]);
        }

        $questions = $session->get("quiz_{$id}_questions", []);
        $attempts  = (int) ($session->get("quiz_{$id}_attempts") ?? 0);

        // Guard: attempts exhausted
        if ($attempts >= 3 || empty($questions)) {
            return $this->redirectToRoute('app_carriere_quiz_show', ['id' => $id]);
        }

        // Score the submission
        $score = 0;
        foreach ($questions as $index => $q) {
            $submitted = $request->request->get("answer_{$index}");
            if ($submitted !== null && (int) $submitted === (int) $q['correct']) {
                $score++;
            }
        }

        // Increment attempts
        $attempts++;
        $session->set("quiz_{$id}_attempts", $attempts);

        $passed = $score >= 4;

        if ($passed) {
            $session->set("quiz_{$id}_passed", true);
            $this->addFlash('success', "Congratulations! You scored {$score}/5 and passed the quiz.");
            return $this->redirectToRoute('app_carriere_demande_apply', ['id' => $id]);
        }

        return $this->render('carriere/quiz/result.html.twig', [
            'opportunity' => $opportunity,
            'score'       => $score,
            'passed'      => false,
            'exhausted'   => $attempts >= 3,
            'attempts'    => $attempts,
        ]);
    }
}
