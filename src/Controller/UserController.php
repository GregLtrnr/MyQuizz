<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\LoginType;
use App\Repository\CategorieRepository;
use App\Repository\HistoriqueRepository;
use App\Repository\QuestionRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class UserController extends AbstractController
{
    private $requestStack;
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    #[Route('/profile', name: 'user_profile')]
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        if($this->getUser()){
            $user = $this->getUser();
            $form = $this->createForm(LoginType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            }
            return $this->render('user/profile.html.twig', [
                'form' => $form->createView(),
                'user' => $user,
            ]);
        }else{
            return $this->redirectToRoute('home');
        }
    }
    #[Route('/history', name: 'user_history')]
    public function history(HistoriqueRepository $historiqueRepository, QuestionRepository $questionRepository, CategorieRepository $categorieRepository)
    {
        // dump($categorieRepository->findById($hist["id"])->getResult());
        $historique = array();
        if($this->getUser()){
            //get user historiques
            $history = $historiqueRepository->findById($this->getUser()->getId());
            $historique = array();
            foreach($history as $hist){
                $data = array();
                $data["nb_questions"] = count($questionRepository->findByIdCategorie($hist->getId())->getResult());
                $data["score"] = $hist->getScore();
                $data["categorie_name"] = trim($hist->getCategorie()->getName());
                $data["categorie_id"] = $hist->getCategorie()->getId();
                $historique[] = $data;
            }
        }else{
            $session = $this->requestStack->getSession();
            $history = $session->get('PartieFini');
            if(!empty($history)){
                $historique = array();
                foreach($history as $key => $hist){
                    $data = array();
                    $data["nb_questions"] = count($questionRepository->findByIdCategorie($key)->getResult());
                    $data["score"] = $hist["score"];
                    $data["categorie_name"] = trim($categorieRepository->findById($key)[0]->getName());
                    $data["categorie_id"] = $hist["id"];
                    $historique[] = $data;
                }
            }
        }
        return $this->render('user/history.html.twig',
            [
                'history' => $historique,
            ]);
    }
    #[Route('/email')]
    public function sendEmail(MailerInterface $mailer)
    {
        $email = (new Email())
            ->from('support@myquiz.fr')
            ->to($this->getUser()->getEmail())
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $mailer->send($email);

        return $this->render('user/test.html.twig');
    }
}
