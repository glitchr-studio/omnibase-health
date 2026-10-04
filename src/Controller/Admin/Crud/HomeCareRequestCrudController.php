<?php

namespace Base\Health\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Health\Entity\HomeCareRequest;
use Base\Office\Controller\Admin\OpenToTrait;
use Base\Field\AssociationField;
use Base\Field\DateField;
use Base\Field\DateTimeField;
use Base\Field\IdField;
use Base\Field\TextField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Every home care request, as a list; the screen to answer them is the
 * agenda's "Soins à domicile" page.
 */
class HomeCareRequestCrudController extends AbstractCrudController
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
        return HomeCareRequest::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-house-medical';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->openTo(parent::configureActions($actions)->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE), 'ROLE_STAFF');

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield DateTimeField::new('createdAt', '@health.admin.field.created_at')->setColumns(3)->setDisabled();
        yield AssociationField::new('patient', '@health.admin.field.patient')->setColumns(3)->setDisabled();
        yield TextField::new('city', '@health.admin.field.city')->setColumns(3)->setDisabled();
        yield TextField::new('careType', '@health.admin.field.care_type')->setColumns(3)->setDisabled();
        yield DateField::new('startDate', '@health.admin.field.start_date')->setColumns(3)->setDisabled();
        yield DateField::new('endDate', '@health.admin.field.end_date')->setColumns(3)->setDisabled()->hideOnIndex();
        yield TextField::new('statusValue', '@health.admin.field.status')->setColumns(3)->setDisabled();
        yield AssociationField::new('assignedTo', '@health.admin.field.assigned_to')->setColumns(3)->setDisabled()->hideOnIndex();
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
