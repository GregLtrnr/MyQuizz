<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class Questions10Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => "Titre du Quizz:", "required" => true, 'label_attr' => ['class' => 'label-create']]);
            for($i = 1; $i <= 10; $i++){
                $builder->add('question'.$i, TextType::class, ['label' => 'Question '.$i.":", "required" => true, 'label_attr' => ['class' => 'label-create'],'attr'=>['placeholder'=>'Question '.$i]]);
                for($j = 1; $j <= 3; $j++){
                    $builder->add($i.'reponse'.$j, TextType::class, ['label' => 'Réponse '.$j.":", "required" => true]);
                }
                $builder->add('bonne_reponse'.$i, ChoiceType::class, ['label' => 'Bonne réponse:', "required" => true, 'choices' => ['1' => '1', '2' => '2', '3' => '3'],'label_attr' => ['class' => 'label-create']]);
            }
            $builder->add('nb_questions', HiddenType::class,['data'=>10]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
