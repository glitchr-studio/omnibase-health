<?php

namespace Base\Health\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Health\Entity\PatientProfile;
use Base\Office\Controller\Admin\OpenToTrait;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\DateField;
use Base\Field\IdField;
use Base\Field\TextField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The patients as the practice knows them: who, how to reach them, where
 * they live. Nothing clinical is kept here.
 */
class PatientProfileCrudController extends AbstractCrudController
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
        return PatientProfile::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-hospital-user';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setDefaultSort(['familyName' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->openTo(parent::configureActions($actions)->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE), 'ROLE_STAFF');

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('familyName', '@health.admin.field.family_name')->setColumns(4);
        yield TextField::new('givenNames', '@health.admin.field.given_names')->setColumns(4);
        yield DateField::new('birthDate', '@health.admin.field.birth_date')->setColumns(4)->setRequired(false);
        yield AssociationField::new('user', '@health.admin.field.account')->setColumns(4)->setDisabled()->hideOnIndex();
        yield TextField::new('phone', '@health.admin.field.phone')->setColumns(4)->setRequired(false);
        yield TextField::new('street', '@health.admin.field.street')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield TextField::new('postalCode', '@health.admin.field.postal_code')->setColumns(2)->setRequired(false)->hideOnIndex();
        yield TextField::new('city', '@health.admin.field.city')->setColumns(4)->setRequired(false);
        yield AssociationField::new('referringPhysician', '@health.admin.field.referring_physician')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield BooleanField::new('sharingOpposition', '@health.admin.field.sharing_opposition')->setColumns(6)->setDisabled()->setHelp('@health.admin.help.sharing_opposition');
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
