<?php

namespace Base\Health\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Health\Entity\Practitioner;
use Base\Office\Controller\Admin\OpenToTrait;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\SelectField;
use Base\Field\TextField;
use Base\Health\Enum\Sector;
use Base\Health\Service\PractitionerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The team's health professionals: profession, agreement with the health
 * insurance, Carte Vitale, MSSanté address. "Lire l'Annuaire Santé" reads
 * the profession and the MSSanté address from the RPPS number on the
 * member's record.
 */
class PractitionerCrudController extends AbstractCrudController
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
        return Practitioner::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-id-card-clip';
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->openTo(parent::configureActions($actions), 'ROLE_ADMIN');
        $actions->setPermission('registry', 'ROLE_ADMIN');
        if ($this->registry?->isAvailable()) {
            foreach ([Actions::PAGE_INDEX, Actions::PAGE_DETAIL] as $page) {
                $actions->add($page, Action::new('registry', '@health.admin.registry.action', 'fa-solid fa-address-card')->linkToCrudAction('registry'));
            }
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('member', '@health.admin.field.member')->setColumns(5);
        yield TextField::new('profession', '@health.admin.field.profession')->setColumns(4)->setRequired(false);
        yield TextField::new('professionCode', '@health.admin.field.profession_code')->setColumns(3)->setRequired(false)->setHelp('@health.admin.help.profession_code')->hideOnIndex();
        yield TextField::new('specialty', '@health.admin.field.specialty')->setColumns(5)->setRequired(false);
        yield SelectField::new('sectorValue', '@health.admin.field.sector')->setChoices($this->choices(Sector::cases()))->setColumns(4)->setRequired(false);
        yield TextField::new('mssante', '@health.admin.field.mssante')->setColumns(5)->setRequired(false)->hideOnIndex();
        yield BooleanField::new('carteVitale', '@health.admin.field.carte_vitale')->setColumns(3);
        yield BooleanField::new('thirdPartyPayment', '@health.admin.field.third_party')->setColumns(3);
        yield BooleanField::new('acceptsNewPatients', '@health.admin.field.new_patients')->setColumns(3);
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

    private ?PractitionerRegistry $registry = null;

    #[Required]
    public function setRegistry(PractitionerRegistry $registry): void
    {
        $this->registry = $registry;
    }

    /** "Lire l'Annuaire Santé". */
    #[AdminAction('/{entityId}/registry')]
    public function registry(Request $request, string $entityId): Response
    {
        /** @var Practitioner $practitioner */
        $practitioner = $this->findEntity($entityId);
        try {
            $professional = $this->registry?->refresh($practitioner);
            if (null === $professional) {
                $this->addFlash('warning', $this->translator?->trans(null === $practitioner->getRpps() ? 'admin.registry.no_rpps' : 'admin.registry.unknown', [], 'health') ?? '');
            } else {
                $this->addFlash('success', $this->translator?->trans('admin.registry.read', ['name' => $professional->name(), 'profession' => (string) ($professional->profession ?? '—')], 'health') ?? '');
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', $this->translator?->trans('admin.registry.unavailable', ['error' => $e->getMessage()], 'health') ?? $e->getMessage());
        }

        return $this->redirect((string) ($request->headers->get('referer') ?: $this->generateUrl('admin_crud_practitioners_index')));
    }
}
