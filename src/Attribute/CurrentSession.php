<?php

declare(strict_types=1);

namespace Componenta\Auth\Session\App\Attribute;

use Attribute;
use Componenta\DI\Resolver\Parameter\ParameterSourceAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class CurrentSession implements ParameterSourceAttributeInterface {}
