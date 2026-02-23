<?php
namespace App\Controller\Architect;

use App\Entity\Architect\Profile;
use App\Entity\Architect\User;
use App\Form\Architect\ProfileType;
use App\Service\AvatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        AvatarService $avatarService
    ): Response {
        // Get the currently authenticated user
        $user = $this->getUser();
        
        // Redirect to login if not authenticated
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get or create profile for the current user
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $em->persist($profile);
        }
        
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatarFile')->getData();
            
            if ($avatarFile) {
                // Delete old avatar if exists
                if ($profile->getAvatar()) {
                    $oldAvatarPath = $this->getParameter('avatars_directory') . '/' . $profile->getAvatar();
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();
                
                $avatarFile->move(
                    $this->getParameter('avatars_directory'),
                    $newFilename
                );
                
                $profile->setAvatar($newFilename);
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile_edit');
        }
        
        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
            'user' => $user,
        ]);
    }

#[Route('/profile/generate-avatar', name: 'app_generate_avatar', methods: ['POST'])]
public function generateAvatar(Request $request, EntityManagerInterface $em): JsonResponse
{
    try {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['success' => false, 'error' => 'Not logged in'], 401);

        $profile = $user->getProfile();
        if (!$profile) return new JsonResponse(['success' => false, 'error' => 'No profile found'], 404);

        $data  = json_decode($request->getContent(), true) ?? [];
        $style = $data['style'] ?? 'avataaars';
        $seed  = $data['seed']  ?? uniqid();
        $bg    = $data['backgroundColor'] ?? 'b6e3f4';
        $flip  = !empty($data['flip'])   ? 'true' : 'false';
        $radius= !empty($data['radius']) ? '50'   : '0';

        $url = "https://api.dicebear.com/7.x/{$style}/svg?seed=" . urlencode($seed)
             . "&backgroundColor={$bg}&flip={$flip}&radius={$radius}";

        // Use curl instead of file_get_contents (more reliable)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $svg = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$svg || $httpCode !== 200) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Could not reach avatar API: ' . $curlError
            ], 500);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Delete old avatar
        if ($profile->getAvatar()) {
            $old = $uploadDir . $profile->getAvatar();
            if (file_exists($old)) unlink($old);
        }

        $filename = 'avatar_' . uniqid() . '.svg';
        file_put_contents($uploadDir . $filename, $svg);

        $profile->setAvatar($filename);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'avatar'  => '/uploads/avatars/' . $filename,
            'message' => 'Avatar saved!'
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
#[Route('/profile/avatar-preview', name: 'app_avatar_preview', methods: ['GET'])]
public function avatarPreview(Request $request): Response
{
    $params = $request->query->all();
    $style = $params['style'] ?? 'avataaars';
    unset($params['style']);

    $url = "https://api.dicebear.com/7.x/{$style}/svg?" . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $svg = curl_exec($ch);
    curl_close($ch);

    return new Response($svg ?: '', 200, ['Content-Type' => 'image/svg+xml']);
}

#[Route('/profile/avatar-builder', name: 'app_avatar_builder')]
public function avatarBuilder(): Response
{
    if (!$this->getUser()) return $this->redirectToRoute('app_login');
    return $this->render('architect/avatar_builder.html.twig', [
        'profile' => $this->getUser()->getProfile()
    ]);
}




    #[Route('/profile', name: 'app_profile_view')]
    public function view(EntityManagerInterface $em): Response
    {
        // Get the currently authenticated user
        $user = $this->getUser();
        
        // Redirect to login if not authenticated
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get profile
        $profile = $user->getProfile();
        if (!$profile) {
            // If no profile exists, redirect to edit to create one
            $this->addFlash('info', 'Please complete your profile.');
            return $this->redirectToRoute('app_profile_edit');
        }
        
        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}