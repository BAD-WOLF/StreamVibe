<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\System;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

/**
 * GET /{locale}/api/system/...: o painel do sistema, de ROLE_DEV para
 * cima — o servidor, o runtime, as dependências, os processos e os logs.
 *
 * Os logs são lidos de um arquivo de exemplo posto na pasta de logs do
 * ambiente de teste (e apagado no fim), e do test.log que a própria
 * aplicação escreve (FileLogger). GET /system/dependencies vai ao TMDB de
 * verdade, como os testes de filmes; os campos da resposta são os mesmos
 * com ou sem rede.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class SystemApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private const string FIXTURE = 'system-api-test.log';

    private KernelBrowser $client;

    private string $logsDir;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->setupTestDatabase();

        $this->logsDir = (string) static::getContainer()->getParameter('kernel.logs_dir');
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0777, true);
        }
        file_put_contents($this->logsDir . '/' . self::FIXTURE, implode("\n", [
            '[2026-11-08T10:00:00.000+00:00] app.INFO: an info line',
            '[2026-11-08T10:00:01.000+00:00] app.WARNING: a slow query',
            '[2026-11-08T10:00:02.000+00:00] app.ERROR: connection to postgresql://app:pw-123456@database:5432/app failed',
            '[2026-11-08T10:00:03.000+00:00] app.CRITICAL: Authorization: Bearer abc.def.ghi rejected',
            'a line in another format',
        ]) . "\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->logsDir . '/' . self::FIXTURE);
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function routeProvider(): iterable
    {
        yield 'server' => ['/pt_BR/api/system/server'];
        yield 'runtime' => ['/en/api/system/runtime'];
        yield 'dependencies' => ['/es/api/system/dependencies'];
        yield 'processes' => ['/pt_BR/api/system/processes'];
        yield 'log files' => ['/pt_BR/api/system/logs'];
        yield 'a log file' => ['/pt_BR/api/system/logs/' . self::FIXTURE];
    }

    #[DataProvider('routeProvider')]
    public function testAPlainUserCannotSeeThePanel(string $uri): void
    {
        $this->authenticateClient($this->client, $this->makeUser('plain@example.com', Role::User));

        $this->client->request('GET', $uri);

        $error = $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'http_error');
        $this->assertSame('Access Denied.', $error['message']);
    }

    /**
     * O admin e o owner herdam o ROLE_DEV (role_hierarchy).
     */
    public function testTheRolesAboveDevSeeThePanel(): void
    {
        foreach ([Role::Admin, Role::Owner] as $role) {
            $this->authenticateClient($this->client, $this->makeUser(strtolower($role->name) . '@example.com', $role));

            $this->client->request('GET', '/pt_BR/api/system/server');
            $this->assertResponseIsSuccessful();
        }
    }

    public function testTheServer(): void
    {
        $data = $this->getAsDev('/pt_BR/api/system/server');

        $this->assertSame(['host', 'container', 'disks'], array_keys($data));
        $this->assertSame(
            ['hostname', 'os', 'kernel', 'architecture', 'cpus', 'uptime_seconds', 'load_average', 'memory'],
            array_keys($data['host']),
        );
        $this->assertSame(php_uname('m'), $data['host']['architecture']);
        $this->assertIsArray($data['disks']);

        if ($data['host']['memory'] !== null) {
            $this->assertSame(['total_bytes', 'available_bytes', 'swap_total_bytes', 'swap_free_bytes'], array_keys($data['host']['memory']));
        }

        if ($data['container'] !== null) {
            $this->assertSame(
                ['cgroup_version', 'memory_limit_bytes', 'memory_usage_bytes', 'cpu_limit', 'processes_limit', 'processes'],
                array_keys($data['container']),
            );
        }
    }

    public function testTheRuntime(): void
    {
        $data = $this->getAsDev('/en/api/system/runtime');

        $this->assertSame(['php', 'opcache', 'symfony'], array_keys($data));
        $this->assertSame(PHP_VERSION, $data['php']['version']);
        $this->assertContains('Core', $data['php']['extensions']);
        $this->assertSame(Kernel::VERSION, $data['symfony']['version']);
        $this->assertSame('test', $data['symfony']['environment']);
        $this->assertContains('FrameworkBundle', $data['symfony']['bundles']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $data['symfony']['end_of_life']);
    }

    public function testTheDependencies(): void
    {
        $data = $this->getAsDev('/es/api/system/dependencies');

        $this->assertTrue($data['database']['reachable']);
        $this->assertNull($data['database']['error']);
        $this->assertNotNull($data['database']['migrations']);
        $this->assertSame(['executed', 'pending', 'current'], array_keys($data['database']['migrations']));
        // com ou sem rede, o TMDB vem com os mesmos campos
        $this->assertSame(['reachable', 'latency_ms', 'status_code', 'error'], array_keys($data['tmdb']));
        $this->assertIsBool($data['tmdb']['reachable']);
    }

    /**
     * O processo do PHPUnit atende as requisições do teste: tem de estar
     * na lista, com a linha de comando dele.
     */
    public function testTheProcesses(): void
    {
        $data = $this->getAsDev('/pt_BR/api/system/processes');

        $pids = array_column($data['processes'], 'pid');
        $this->assertContains(getmypid(), $pids);
        $self = $data['processes'][array_search(getmypid(), $pids, true)];
        $this->assertStringContainsString('phpunit', $self['command']);
        $this->assertContains($self['state'], ['running', 'sleeping', 'waiting']);
        $this->assertGreaterThan(0, $self['memory_bytes']);
        $sorted = $pids;
        sort($sorted);
        $this->assertSame($sorted, $pids);
    }

    public function testTheLogFiles(): void
    {
        $data = $this->getAsDev('/pt_BR/api/system/logs');

        $this->assertSame($this->logsDir, $data['directory']);
        $files = array_column($data['files'], null, 'name');
        $this->assertArrayHasKey(self::FIXTURE, $files);
        $this->assertSame(filesize($this->logsDir . '/' . self::FIXTURE), $files[self::FIXTURE]['size_bytes']);
    }

    public function testReadingALogFile(): void
    {
        $data = $this->getAsDev('/pt_BR/api/system/logs/' . self::FIXTURE);

        $this->assertSame(self::FIXTURE, $data['file']['name']);
        $this->assertSame([5, true], [$data['scanned_lines'], $data['complete']]);
        $this->assertSame([
            [null, null, null, 'a line in another format'],
            ['2026-11-08T10:00:03.000+00:00', 'app', 'critical', 'Authorization: Bearer *** rejected'],
            ['2026-11-08T10:00:02.000+00:00', 'app', 'error', 'connection to postgresql://app:***@database:5432/app failed'],
            ['2026-11-08T10:00:01.000+00:00', 'app', 'warning', 'a slow query'],
            ['2026-11-08T10:00:00.000+00:00', 'app', 'info', 'an info line'],
        ], array_map(static fn(array $entry): array => [$entry['datetime'], $entry['channel'], $entry['level'], $entry['message']], $data['entries']));
    }

    public function testTheFiltersOfALogFile(): void
    {
        $uri = '/pt_BR/api/system/logs/' . self::FIXTURE;

        $levels = $this->getAsDev("{$uri}?level=error");
        $this->assertSame(['critical', 'error'], array_column($levels['entries'], 'level'));

        $search = $this->getAsDev("{$uri}?search=SLOW");
        $this->assertSame(['a slow query'], array_column($search['entries'], 'message'));

        // a busca vê o texto já sem os segredos
        $secret = $this->getAsDev("{$uri}?search=pw-123456");
        $this->assertSame([], $secret['entries']);

        $limited = $this->getAsDev("{$uri}?limit=2");
        $this->assertSame([2, 2, false], [count($limited['entries']), $limited['scanned_lines'], $limited['complete']]);
    }

    public function testInvalidRequestsForALogFile(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('dev@example.com', Role::Dev));

        $this->client->request('GET', '/pt_BR/api/system/logs/.env');
        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
        $this->assertSame(['File: File must be the name of a log file'], $error['errors']);

        $this->client->request('GET', '/pt_BR/api/system/logs/' . self::FIXTURE . '?limit=1001');
        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
        $this->assertSame(['Limit: Limit must be between 1 and 1000'], $error['errors']);

        $this->client->request('GET', '/pt_BR/api/system/logs/' . self::FIXTURE . '?level=warn');
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');

        $this->client->request('GET', '/pt_BR/api/system/logs/' . self::FIXTURE . '?limit=many');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->client->request('GET', '/pt_BR/api/system/logs/old.log');
        $error = $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, 'business_logic_error');
        $this->assertSame('Log file with identifier "old.log" not found', $error['message']);
    }

    /**
     * O que a aplicação registra vai para <ambiente>.log (FileLogger), que
     * o painel lê: o aviso da validação recusada logo acima está lá.
     */
    public function testTheApplicationLogIsInThePanel(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('dev@example.com', Role::Dev));
        $this->client->request('GET', '/pt_BR/api/system/logs/.env');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $this->client->request('GET', '/pt_BR/api/system/logs');
        $this->assertResponseIsSuccessful();
        $this->assertContains('test.log', array_column($this->responseData($this->client)['data']['files'], 'name'));

        $this->client->request('GET', '/pt_BR/api/system/logs/test.log?level=warning&search=system_log_file_request');
        $this->assertResponseIsSuccessful();
        $entries = $this->responseData($this->client)['data']['entries'];
        $this->assertNotEmpty($entries);
        $this->assertSame(['app', 'warning'], [$entries[0]['channel'], $entries[0]['level']]);
        $this->assertStringStartsWith('Request validation failed', $entries[0]['message']);
    }

    /**
     * @return array<string, mixed> o `data` da resposta
     */
    private function getAsDev(string $uri): array
    {
        $this->authenticateClient($this->client, $this->makeUser('dev@example.com', Role::Dev));
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $body = $this->responseData($this->client);
        $this->assertTrue($body['success']);
        $this->assertIsString($body['message']);

        return $body['data'];
    }

    private function makeUser(string $email, Role $role): User
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing instanceof User) {
            return $existing;
        }

        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('hashed_password_123')
            ->verified(true)
            ->build();
        $user->assignRole($role);
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
