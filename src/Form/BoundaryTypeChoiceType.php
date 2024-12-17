<?php

declare(strict_types=1);

namespace Andante\PeriodBundle\Form;

use League\Period\Bounds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BoundaryTypeChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Include start and exclude end' => Bounds::IncludeStartExcludeEnd,
                'Include both start and end' => Bounds::IncludeAll,
                'Exclude start and include end' => Bounds::ExcludeStartIncludeEnd,
                'Exclude both start and end' => Bounds::ExcludeAll,
            ],
            'empty_data' => Bounds::IncludeStartExcludeEnd,
            'multiple' => false,
            'expanded' => false,
            'choice_translation_domain' => 'AndantePeriodBundle',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
