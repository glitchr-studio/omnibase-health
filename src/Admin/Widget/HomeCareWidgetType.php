<?php

namespace Base\Health\Admin\Widget;

use Base\Admin\Config\Menu\MenuItem;
use Base\Admin\Widget\DashboardWidgetTypeInterface;
use Base\Health\Repository\HomeCareRequestRepository;

/** The dashboard's "Home care asked": the requests waiting for the practice's answer. */
final class HomeCareWidgetType implements DashboardWidgetTypeInterface
{
    public function __construct(private readonly HomeCareRequestRepository $requests)
    {
    }

    public static function getName(): string
    {
        return 'health_requests';
    }

    public function getTemplate(): string
    {
        return '@Health/admin/widget/home_care.html.twig';
    }

    public function getTemplateVars(MenuItem $widget): array
    {
        return ['count' => $this->requests->countPending(), 'requests' => $this->requests->findPending(5)];
    }
}
