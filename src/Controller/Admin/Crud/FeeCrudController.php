<?php

namespace Base\Health\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Health\Entity\Fee;
use Base\Office\Controller\Admin\OpenToTrait;
use Base\Field\AssociationField;
use Base\Field\IdField;
use Base\Field\IntegerField;
use Base\Field\TextField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The fees as they are displayed: a practitioner's, or the whole practice's.
 */
class FeeCrudController extends AbstractCrudController
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
        return Fee::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-euro-sign';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setDefaultSort(['position' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->openTo(parent::configureActions($actions), 'ROLE_ADMIN');

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('label', '@health.admin.field.fee_label')->setColumns(6);
        yield TextField::new('amount', '@health.admin.field.amount')->setColumns(3)->setHelp('@health.admin.help.amount');
        yield IntegerField::new('position', '@health.admin.field.position')->setColumns(3)->hideOnIndex();
        yield AssociationField::new('practitioner', '@health.admin.field.practitioner')->setColumns(6)->setRequired(false)->setHelp('@health.admin.help.fee_practitioner');
        yield TextField::new('category', '@health.admin.field.category')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield TextField::new('reimbursement', '@health.admin.field.reimbursement')->setRequired(false);
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
