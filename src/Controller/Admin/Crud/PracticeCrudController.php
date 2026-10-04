<?php

namespace Base\Health\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Health\Entity\Practice;
use Base\Office\Controller\Admin\OpenToTrait;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\SelectField;
use Base\Field\TextField;
use Base\Field\TextareaField;
use Base\Health\Enum\PracticeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The practice: its kind, its FINESS number, what its pages say of emergencies
 * and of care out of hours.
 */
class PracticeCrudController extends AbstractCrudController
{
    use OpenToTrait;

    private ?TranslatorInterface $translator = null;

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public static function getEntityFqcn(): string
    {
        return Practice::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-hospital';
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->openTo(parent::configureActions($actions), 'ROLE_ADMIN');

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('office', '@health.admin.field.office')->setColumns(6);
        yield SelectField::new('typeValue', '@health.admin.field.type')->setChoices($this->choices(PracticeType::cases()))->setColumns(4);
        yield TextField::new('finess', '@health.admin.field.finess')->setColumns(2)->setRequired(false);
        yield TextareaField::new('presentation', '@health.admin.field.presentation')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('emergencyInstructions', '@health.admin.field.emergency')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('outOfHours', '@health.admin.field.out_of_hours')->setRequired(false)->hideOnIndex();
        yield BooleanField::new('acceptsNewPatients', '@health.admin.field.new_patients')->setColumns(3);
        yield BooleanField::new('teleconsultation', '@health.admin.field.teleconsultation')->setColumns(3);
        yield BooleanField::new('homeCare', '@health.admin.field.home_care')->setColumns(3);
    }

    /** @param list<\UnitEnum> $cases */
    private function choices(array $cases): array
    {
        $choices = [];
        foreach ($cases as $case) {
            $choices[$this->translator?->trans($case->label(), [], 'health') ?? $case->value] = $case->value;
        }

        return $choices;
    }
}
