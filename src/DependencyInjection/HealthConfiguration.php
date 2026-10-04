<?php

namespace Base\Health\DependencyInjection;

use Base\Bundle\AbstractBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class HealthConfiguration extends AbstractBaseConfiguration
{
    private bool $childrenDeclared = false;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = $this->getTreeBuilder();
        if ($this->childrenDeclared) {
            return $treeBuilder;
        }
        $this->childrenDeclared = true;

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('roles')->addDefaultsIfNotSet()
                    ->info('The roles the groups give (security.yaml\'s hierarchy puts both under ROLE_STAFF).')
                    ->children()
                        ->scalarNode('practitioner')->defaultValue('ROLE_PRACTITIONER')->end()
                        ->scalarNode('secretary')->defaultValue('ROLE_SECRETARY')->end()
                        ->scalarNode('staff')->defaultValue('ROLE_STAFF')->end()
                        ->scalarNode('coordination')->defaultValue('ROLE_ADMIN')->end()
                    ->end()
                ->end()
                ->booleanNode('staff_two_factor')->defaultTrue()
                    ->info('A staff account without a second factor is sent to its security settings before anything else.')->end()
                ->arrayNode('emergency')->addDefaultsIfNotSet()
                    ->info('The permanent banner: who to call.')
                    ->children()
                        ->scalarNode('samu')->defaultValue('15')->end()
                        ->scalarNode('europe')->defaultValue('112')->end()
                        ->scalarNode('deaf')->defaultValue('114')->end()
                        ->scalarNode('pharmacy')->defaultValue('3237')->end()
                        ->scalarNode('oncall')->defaultValue('116 117')
                            ->info('The out-of-hours medical line (permanence des soins).')->end()
                    ->end()
                ->end()
                ->arrayNode('retention')->addDefaultsIfNotSet()
                    ->info('Months each thing is kept once over (health:purge), to be set by the practice with its DPO.')
                    ->children()
                        ->integerNode('appointments')->min(1)->defaultValue(36)->end()
                        ->integerNode('home_care')->min(1)->defaultValue(36)->end()
                        ->integerNode('access_logs')->min(6)->defaultValue(36)->end()
                        ->integerNode('signals')->min(1)->defaultValue(1)->end()
                        ->integerNode('documents_after_expiry')->min(0)->defaultValue(1)->end()
                    ->end()
                ->end()
                ->booleanNode('teleconsultation_referenced')->defaultFalse()
                    ->info('Whether the video solution is listed by the ANS (reimbursable teleconsultation). The one built in is not: the back office says so.')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
