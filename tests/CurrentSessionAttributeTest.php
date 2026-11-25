<?php

declare(strict_types=1);

namespace Componenta\Auth\Session\App\Tests;

use Componenta\Auth\AuthenticationEvidence;
use Componenta\Auth\AuthenticationStateInterface;
use Componenta\Auth\Session\App\Attribute\CurrentSession;
use Componenta\Auth\Session\App\ConfigProvider;
use Componenta\Auth\Session\AuthSession;
use Componenta\Config\ConfigFactory;
use Componenta\Config\Environment;
use Componenta\DI\Container;
use Componenta\DI\ContainerFactory;
use Componenta\DI\Exception\AttributeCompositionException;
use Componenta\DI\Exception\ResolutionException;
use Componenta\Identity\Uuid;
use DateTimeImmutable;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class CurrentSessionAttributeTest extends TestCase
{
    public function testResolvesTheSessionEstablishedOnTheCurrentRequest(): void
    {
        $session = self::session();

        $resolved = self::container()->call(
            static fn(#[CurrentSession] AuthSession $current): AuthSession => $current,
            [ServerRequestInterface::class => self::request($session)],
        );

        self::assertSame($session, $resolved);
    }

    public function testAcceptsACompatibleAuthenticationStateInterface(): void
    {
        $session = self::session();

        $resolved = self::container()->call(
            static fn(#[CurrentSession] AuthenticationStateInterface $current): AuthenticationStateInterface => $current,
            [ServerRequestInterface::class => self::request($session)],
        );

        self::assertSame($session, $resolved);
    }

    /** @return iterable<string, array{string|int}> */
    public static function callerArgumentSlots(): iterable
    {
        yield 'parameter name' => ['current'];
        yield 'parameter position' => [0];
        yield 'declared type' => [AuthSession::class];
    }

    #[DataProvider('callerArgumentSlots')]
    public function testCallerArgumentsCannotShadowTheRequestSession(string|int $slot): void
    {
        $trusted = self::session();
        $other = self::session('018f6d5d-3f7a-7a9b-8c2f-123456789aad');

        $resolved = self::container()->call(
            static fn(#[CurrentSession] AuthSession $current): AuthSession => $current,
            [
                ServerRequestInterface::class => self::request($trusted),
                $slot => $other,
            ],
        );

        self::assertSame($trusted, $resolved);
    }

    public function testNullableTargetStillRequiresTheCurrentRequest(): void
    {
        $session = self::session();

        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessage('PSR-7 request is required');

        self::container()->call(
            static fn(#[CurrentSession] ?AuthSession $current = null): ?AuthSession => $current,
            ['current' => $session, AuthSession::class => $session],
        );
    }

    public function testAnonymousNullableTargetCannotFallBackToCallerArguments(): void
    {
        $session = self::session();

        $resolved = self::container()->call(
            static fn(#[CurrentSession] ?AuthSession $current): ?AuthSession => $current,
            [
                ServerRequestInterface::class => self::request(),
                'current' => $session,
                AuthSession::class => $session,
            ],
        );

        self::assertNull($resolved);
    }

    public function testAnonymousRequestCannotResolveARequiredSession(): void
    {
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessage('current authenticated session is required but unavailable');

        self::container()->call(
            static fn(#[CurrentSession] AuthSession $current): AuthSession => $current,
            [ServerRequestInterface::class => self::request()],
        );
    }

    public function testInvalidRequestAttributeFailsEvenForANullableTarget(): void
    {
        $request = self::request()->withAttribute(AuthSession::class, 'invalid-session');

        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessage('must be ' . AuthSession::class);

        self::container()->call(
            static fn(#[CurrentSession] ?AuthSession $current): ?AuthSession => $current,
            [ServerRequestInterface::class => $request],
        );
    }

    public function testReusedContainerDoesNotRetainASessionBetweenRequests(): void
    {
        $container = self::container();
        $handler = static fn(#[CurrentSession] ?AuthSession $current): ?AuthSession => $current;
        $first = self::session();
        $second = self::session('018f6d5d-3f7a-7a9b-8c2f-123456789aad');

        foreach ([$first, null, $second] as $session) {
            self::assertSame(
                $session,
                $container->call($handler, [ServerRequestInterface::class => self::request($session)]),
            );
        }
    }

    public function testConstructorResolutionIsRejectedEvenWithARequestAndExplicitSession(): void
    {
        $session = self::session();

        $this->expectException(AttributeCompositionException::class);
        $this->expectExceptionMessage('is invocation-only and cannot target constructor parameter');

        self::container()->make(CurrentSessionConstructorTarget::class, [
            ServerRequestInterface::class => self::request($session),
            'current' => $session,
            AuthSession::class => $session,
        ]);
    }

    public function testSessionCannotSatisfyAnIncompatibleDeclaredParameterType(): void
    {
        $this->expectException(ResolutionException::class);
        $this->expectExceptionMessage('does not satisfy declared parameter type');

        self::container()->call(
            static fn(#[CurrentSession] string $current): string => $current,
            [ServerRequestInterface::class => self::request(self::session())],
        );
    }

    private static function container(): Container
    {
        $composition = (new ConfigFactory())->create(
            new Environment([]),
            new ConfigProvider(),
        );

        return (new ContainerFactory())->create(
            $composition->config,
            $composition->dependencies,
        )->container;
    }

    private static function request(?AuthSession $session = null): ServerRequestInterface
    {
        $request = new ServerRequest('GET', 'https://example.test/current-session');

        return $session === null
            ? $request
            : $request->withAttribute(AuthSession::class, $session);
    }

    private static function session(
        string $id = '018f6d5d-3f7a-7a9b-8c2f-123456789aac',
    ): AuthSession {
        $now = new DateTimeImmutable('2030-01-01T00:00:00Z');

        return new AuthSession(
            uuid: Uuid::fromString($id),
            subjectId: Uuid::fromString('018f6d5d-3f7a-7a9b-8c2f-123456789abc'),
            evidence: new AuthenticationEvidence(['password']),
            credentialGeneration: 1,
            authenticatedAt: $now,
            reauthenticatedAt: null,
            lastActiveAt: $now,
            idleExpiresAt: $now->modify('+30 minutes'),
            absoluteExpiresAt: $now->modify('+8 hours'),
        );
    }
}

final readonly class CurrentSessionConstructorTarget
{
    public function __construct(#[CurrentSession] public AuthSession $current) {}
}
