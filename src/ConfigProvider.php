<?php

declare(strict_types=1);

namespace Componenta\Auth\Session\App;

use Componenta\Auth\Session\App\Attribute\CurrentSession;
use Componenta\Auth\Session\App\Resolver\CurrentSessionHandler;
use Componenta\Config\ConfigProvider as BaseConfigProvider;
use Componenta\DI\Attribute\Composition\AttributeDefinition;
use Componenta\DI\Attribute\Composition\Capability\AuthoritativeValueProvider;
use Componenta\DI\Attribute\Composition\Capability\InvocationOnlyValueProvider;

final class ConfigProvider extends BaseConfigProvider
{
    #[\Override]
    protected function getAttributeDefinitions(): array
    {
        return [
            new AttributeDefinition(
                CurrentSession::class,
                new CurrentSessionHandler(),
                [
                    AuthoritativeValueProvider::class,
                    InvocationOnlyValueProvider::class,
                ],
            ),
        ];
    }
}
