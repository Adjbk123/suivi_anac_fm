<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/update', name: 'app_profile_update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les données du formulaire
        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $email = $request->request->get('email');
        $matricule = $request->request->get('matricule');
        $photoFile = $request->files->get('photo');
        
        // Validation basique
        if (empty($nom) || empty($prenom) || empty($email)) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs obligatoires doivent être remplis'
            ]);
        }
        
        // Mettre à jour les informations de base
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setMatricule($matricule);
        
        // Gestion de l'upload de photo
        if ($photoFile) {
            // Debug temporaire
            $uploadDir = $this->getParameter('photos_directory');
            error_log("Upload directory: " . $uploadDir);
            error_log("Photo file received: " . $photoFile->getClientOriginalName());
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
            
            $uploadDir = $this->getParameter('photos_directory');
            
            // Vérifier que le dossier existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            try {
                $photoFile->move(
                    $uploadDir,
                    $newFilename
                );
                
                // Supprimer l'ancienne photo si elle existe
                if ($user->getPhoto() && file_exists($this->getParameter('photos_directory').'/'.$user->getPhoto())) {
                    unlink($this->getParameter('photos_directory').'/'.$user->getPhoto());
                }
                
                $user->setPhoto($newFilename);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Erreur lors du téléchargement de la photo: ' . $e->getMessage()
                ]);
            }
        }
        
        try {
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès !',
                'photo' => $user->getPhoto()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }



    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'ancien mot de passe
            $currentPassword = $form->get('currentPassword')->getData();
            
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->render('profile/change_password.html.twig', [
                    'form' => $form,
                ]);
            }
            
            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if ($passwordHasher->isPasswordValid($user, $form->get('plainPassword')->getData())) {
                $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
                return $this->render('profile/change_password.html.twig', [
                    'form' => $form,
                ]);
            }
            
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->flush();
            
            $this->addFlash('success', 'Mot de passe modifié avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}
