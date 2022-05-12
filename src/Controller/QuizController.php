<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\Questions10Type;
use App\Form\Questions20Type;

class QuizController extends AbstractController
{
    private $requestStack;
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('quiz/home.html.twig', [
        ]);
    }

    #[Route('/test', name: 'test')]
    public function test(Request $request): Response
    {
        $form = $this->createForm(Questions10Type::class);
        $form->handleRequest($request);
        return $this->render('quiz/test.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    #[Route('/create', name: 'create_quiz')]
    public function create(ManagerRegistry $doctrine,Request $request): Response
    {
        if($this->getUser()){
            if(!$this->getUser()->isVerified()){
                return $this->redirectToRoute('home');
            }
            if(isset($_POST['nb_questions'])){
                $nb_questions = $_POST['nb_questions'];
                if ($nb_questions == 20){
                    $form = $this->createForm(Questions20Type::class);
                }else{
                    $form = $this->createForm(Questions10Type::class);
                }
            }else{
                $form = $this->createForm(Questions10Type::class);
            }
            $form->handleRequest($request);

            if(isset($_POST['nb_questions']) && $_POST['nb_questions'] != "" && !$form->isSubmitted()){
                return $this->render('quiz/create/quiz_create_2.html.twig', [
                    "form" => $form->createView(),
                    "nb_questions" => $_POST['nb_questions'],
                ]);
            }elseif($form->isSubmitted()){
                if($form->isValid()){
                    $formData = $form->getData();
                    $categorie = new Categorie();
                    $categorie->setName($formData['title']);
                    $doctrine->getManager()->persist($categorie);
                    $doctrine->getManager()->flush();
                    for($i = 1; $i <= $formData['nb_questions']; $i++){
                        $question = new Question();
                        $question->setQuestion($formData['question'.$i]);
                        $question->setCategorie($categorie);
                        $doctrine->getManager()->persist($question);
                        $doctrine->getManager()->flush();
                        for($j = 1; $j <= 3; $j++){
                            $reponse = new Reponse();
                            $reponse->setQuestion($question);
                            $reponse->setReponse($formData[$i.'reponse'.$j]);
                            if($formData["bonne_reponse".$i] == $j){
                                $reponse->setReponseExpected("1");
                            }else{
                                $reponse->setReponseExpected("0");
                            }
                            $doctrine->getManager()->persist($reponse);
                            $doctrine->getManager()->flush();
                        }
                    }
                }else{
                    return $this->render('quiz/create/quiz_create_2.html.twig', [
                        "form" => $form->createView(),
                        "nb_questions" => $_POST['nb_questions'],
                        "Error" => "Erreur: Veuillez remplir tout les champs"
                    ]);
                }

                return $this->render('quiz/create/quiz_create_done.html.twig', [
                    "quiz_id" => $categorie->getId(),

                ]);
            }else{
                return $this->render('quiz/create/quiz_create_1.html.twig', [    

                ]);
            }
        }else{
            return $this->redirectToRoute('home');
        }
    }

    #[Route('/categories', name: 'quiz_list')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $quizz = $doctrine->getRepository(Categorie::class)->findAll();
        return $this->render('quiz/quiz_list.html.twig', [
            "quizz" => $quizz
        ]);
    }
    
    #[Route('/search', name: 'quiz_search')]
    public function search(ManagerRegistry $doctrine): Response
    {
        $quizz = $doctrine->getRepository(Categorie::class)->findAll();
        return $this->render('quiz/quiz_list.html.twig', [
            "quizz" => $quizz
        ]);
    }

    #[Route('/play/{id}', name: 'quiz_show')]
    public function show(ManagerRegistry $doctrine,QuestionRepository $question, $id): Response
    {
        $quizz = $doctrine->getRepository(Categorie::class)->find($id);
        $question_number = count($question->findByIdCategorie($id)->getResult());
        return $this->render('quiz/quiz_show.html.twig', [
            "quizz" => $quizz,
            "question_number" => $question_number
        ]);
    }

    #[Route('/play/{id}/{id_question}', name: 'quiz_play')]
    public function play(ManagerRegistry $doctrine,QuestionRepository $questionRepository, ReponseRepository $reponseRepository, $id_question, $id): Response
    {
        $session = $this->requestStack->getSession();
        if($id_question == 1 && !isset($_POST["id_answer"])){
            $session->set("PartieEnCours", ["$id" => ["score" => []]]);
        }
        $quizz = $doctrine->getRepository(Categorie::class)->find($id);
        $questionRepository = $questionRepository->findByIdCategorie($id)->getResult();
        if(isset($questionRepository[$id_question-1])){
            $question = $questionRepository[$id_question-1];
            $reponses = $reponseRepository->findByIdQuestion($question->getId())->getResult();
            shuffle($reponses);
            if(isset($_POST["id_answer"])){
                $reponse = $reponseRepository->find($_POST["id_answer"])->getReponseExpected();
                $current = $session->get("PartieEnCours")["$id"]["score"];
                if($reponse == 1){
                    $result = "true";
                    array_push($current,1);
                    $session->set("PartieEnCours", ["$id" => ["score" => $current]]);
                }else{
                    $result = "false";
                    array_push($current,0);
                    $session->set("PartieEnCours", ["$id" => ["score" => $current]]);
                }
                if(!isset($session->get("PartieEnCours")["$id"])){
                    $session->set("PartieEnCours",["id" => ["score"=>[]]]);
                }
                    return $this->render('quiz/quiz_play.html.twig', [
                        "question_number" => $id_question,
                        "quizz" => $quizz,
                        "question" => $question,
                        "reponses" => $reponses,
                        "result" => $result,
                        "test" => $session->get("PartieEnCours"),
                    ]);
            }
            return $this->render('quiz/quiz_play.html.twig', [
                "question_number" => $id_question,
                "quizz" => $quizz,
                "question" => $question,
                "reponses" => $reponses,
                "test" => $session->get("PartieEnCours"),
            ]);
        }else{
            $total = 0;
            if(isset($session->get("PartieEnCours")["$id"]["score"])){
                $score = $session->get("PartieEnCours")["$id"]["score"];
                foreach($score as $v){
                    $total+=$v;
                }
                $dataToAdd = $session->get("PartieFini");
                $dataToAdd["$id"] = $total;
                $session->set("PartieFini",$dataToAdd);
            }
            return $this->render('quiz/quiz_end.html.twig', [
                "quizz" => $quizz,
                "score" => $total,
                "total" => count($questionRepository),
                "test" => $session->get("PartieEnCours"),
            ]);
        }
    }
}
