<?php

declare(strict_types=1);

namespace Componenta\Auth\Session\App\Resolver;

use Componenta\Auth\Session\App\Attribute\CurrentSession;
use Componenta\Auth\Session\AuthSession;
use Componenta\DI\Attribute\Composition\AttributePlan;
use Componenta\DI\Exception\ResolutionException;
use Componenta\DI\Resolver\Attribute\ParameterAttributeHandlerInterface;
use Componenta\DI\Resolver\Parameter\ParameterAttributeValue;
use Componenta\DI\Resolver\Parameter\ParameterResolutionContext;
use Componenta\DI\Resolver\Target\ParameterTarget;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CurrentSessionHandler implements ParameterAttributeHandlerInterface
{
    public function resolveParameter(
        object $attribute,
        ParameterTarget $target,
        ParameterResolutionContext $context,
        AttributePlan $plan,
        ParameterAttributeValue $value,
    ): ParameterAttributeValue {
        if (!$attribute instanceof CurrentSession) {
            throw new LogicException(
                'CurrentSessionHandler received an unsupported parameter attribute.',
            );
        }

        $request = $context->provided[ServerRequestInterface::class] ?? null;

        if (!$request instanceof ServerRequestInterface) {
            throw ResolutionException::forParameter(
                $target->reflection,
                reason: sprintf(
                    'PSR-7 request is required for #[%s]',
                    $attribute::class,
                ),
                providedParameters: $context->provided,
                resolvedParameters: $context->resolved,
            );
        }

        $session = $request->getAttribute(AuthSession::class);

        if ($session !== null && !$session instanceof AuthSession) {
            throw ResolutionException::forParameter(
                $target->reflection,
                reason: sprintf(
                    'request attribute "%s" must be %s; got %s',
                    AuthSession::class,
                    AuthSession::class,
                    get_debug_type($session),
                ),
                providedParameters: $context->provided,
                resolvedParameters: $context->resolved,
            );
        }

        if ($session === null) {
            if ($target->allowsNull) {
                return ParameterAttributeValue::resolved(null);
            }

            throw ResolutionException::forParameter(
                $target->reflection,
                reason: 'current authenticated session is required but unavailable',
                providedParameters: $context->provided,
                resolvedParameters: $context->resolved,
            );
        }

        if (!$target->accepts($session)) {
            throw ResolutionException::forParameter(
                $target->reflection,
                reason: sprintf(
                    'resolved #[%s] value of type %s does not satisfy declared parameter type',
                    $attribute::class,
                    $session::class,
                ),
                providedParameters: $context->provided,
                resolvedParameters: $context->resolved,
            );
        }

        return ParameterAttributeValue::resolved($session);
    }
}
