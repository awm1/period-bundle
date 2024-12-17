<?php

declare(strict_types=1);

namespace Andante\PeriodBundle\Form\DataMapper;

use Andante\PeriodBundle\Exception\InvalidArgumentException;
use League\Period\Bounds;
use League\Period\Period;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class PeriodDataMapper implements DataMapperInterface
{
    private Bounds $defaultBoundaryType;
    private string $startDateChildName;
    private string $endDateChildName;
    private string $boundaryTypeChildName;
    private bool $allowNull;

    private const BOUNDARY_TYPES = [
        Bounds::IncludeStartExcludeEnd,
        Bounds::IncludeAll,
        Bounds::ExcludeStartIncludeEnd,
        Bounds::ExcludeAll,
    ];

    public function __construct(
        Bounds $defaultBoundaryType = Bounds::IncludeStartExcludeEnd,
        string $startDateChildName = 'startDate',
        string $endDateChildName = 'endDate',
        string $boundaryTypeChildName = 'bounds',
        bool $allowNull = true
    ) {
        $this->assertValidBoundaryType($defaultBoundaryType);
        $this->defaultBoundaryType = $defaultBoundaryType;
        $this->startDateChildName = $startDateChildName;
        $this->endDateChildName = $endDateChildName;
        $this->boundaryTypeChildName = $boundaryTypeChildName;
        $this->allowNull = $allowNull;
    }

    private function assertValidBoundaryType($boundaryType): void
    {
        if (!\in_array($boundaryType, self::BOUNDARY_TYPES, true)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Invalid boundary type "%s" provided to %s. Choice between: %s',
                    $boundaryType,
                    self::class,
                    \implode(', ', self::BOUNDARY_TYPES)
                )
            );
        }
    }

    /**
     * @param Period|null                  $viewData
     * @param FormInterface[]|\Traversable $forms
     */
    public function mapDataToForms($viewData, $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof Period) {
            throw new UnexpectedTypeException($viewData, Period::class);
        }

        if ($forms instanceof \Traversable) {
            $forms = \iterator_to_array($forms);
        }

        // initialize form field values
        $forms[$this->startDateChildName]->setData($viewData->startDate);
        $forms[$this->endDateChildName]->setData($viewData->endDate);
        if (isset($forms[$this->boundaryTypeChildName])) {
            $forms[$this->boundaryTypeChildName]->setData($viewData->bounds);
        }
    }

    /**
     * @param FormInterface[]|\Traversable $forms
     * @param mixed                        $viewData
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        if ($forms instanceof \Traversable) {
            $forms = \iterator_to_array($forms);
        }

        $startDate = $forms[$this->startDateChildName]->getData();
        $endDate = $forms[$this->endDateChildName]->getData();
        $boundaryType = isset($forms[$this->boundaryTypeChildName]) ?
            $forms[$this->boundaryTypeChildName]->getData() : $this->defaultBoundaryType;

        $viewData = null;

        if (!$startDate instanceof \DateTimeInterface && $endDate instanceof \DateTimeInterface) {
            $failure = new TransformationFailedException(\sprintf(
                'Start date should be a %s',
                \DateTimeInterface::class
            ));
            $failure->setInvalidMessage(
                'Start date should be valid. {{ startDate }} is not a valid date.',
                ['{{ startDate }}' => \json_encode($startDate)]
            );
            throw $failure;
        }

        if (!$endDate instanceof \DateTimeInterface && $startDate instanceof \DateTimeInterface) {
            $failure = new TransformationFailedException(\sprintf(
                'End date should be a %s',
                \DateTimeInterface::class
            ));
            $failure->setInvalidMessage(
                'End date should be valid. {{ endDate }} is not a valid date.',
                ['{{ endDate }}' => \json_encode($endDate)]
            );
            throw $failure;
        }

        if ($startDate instanceof \DateTimeInterface && $endDate instanceof \DateTimeInterface) {
            if ($startDate > $endDate) {
                $failure = new TransformationFailedException(
                    'Start date should be greater or equals then the end date.'
                );
                $failure->setInvalidMessage('Start date should be greater or equals then the end date.', [
                    '{{ startDate }}' => \json_encode($startDate),
                    '{{ endDate }}' => \json_encode($endDate),
                ]);
                throw $failure;
            }

            try {
                $viewData = Period::fromDate($startDate, $endDate, $boundaryType);
            } catch (\Throwable $e) {
                $failure = new TransformationFailedException('Invalid Period', 0, $e);
                $failure->setInvalidMessage('Invalid Period.', [
                    '{{ startDate }}' => \json_encode($startDate),
                    '{{ endDate }}' => \json_encode($endDate),
                ]);

                throw $failure;
            }
        }

        if (!$this->allowNull && null === $viewData) {
            $failure = new TransformationFailedException('A valid Period is required');
            $failure->setInvalidMessage('A valid Period is required.', [
                '{{ startDate }}' => \json_encode($startDate),
                '{{ endDate }}' => \json_encode($endDate),
            ]);
            throw $failure;
        }
    }
}
