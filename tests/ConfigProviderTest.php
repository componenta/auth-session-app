<?php

declare(strict_types=1);

namespace Componenta\Auth\Session\App\Tests;

use Componenta\Auth\Session\App\Attribute\CurrentSession;
use Componenta\Auth\Session\App\ConfigProvider;
use Componenta\Config\ConfigKey;
use Componenta\DI\Attribute\Composition\AttributeDefinition;
use Componenta\DI\Attribute\Composition\Capability\AuthoritativeValueProvider;
use Componenta\DI\Attribute\Composition\Capability\InvocationOnlyValueProvider;
use PHPUnit\Framework\TestCase;

final class ConfigProviderTest extends TestCase
{
    public function testRegistersOnlyCurrentSessionAsInvocationOnlySource(): void
    {
        $config = (new ConfigProvider())();
        $dependencies = $config[ConfigKey::DEPENDENCIES] ?? [];
        $definitions = $dependencies[ConfigKey::ATTRIBUTE_DEFINITIONS] ?? [];

        self::assertCount(1, $definitions);
        self::assertArrayNotHasKey(ConfigKey::PARAMETER_RESOLVERS, $dependencies);

        $definition = $definitions[0] ?? null;

        self::assertInstanceOf(AttributeDefinition::class, $definition);
        self::assertSame(CurrentSession::class, $definition->attribute);
        self::assertContains(
            AuthoritativeValueProvider::class,
            $definition->capabilities,
        );
        self::assertContains(
            InvocationOnlyValueProvider::class,
            $definition->capabilities,
        );
    }
}
