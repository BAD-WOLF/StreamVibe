<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();
    }

    /**
     * only when there is a redirect
     *
     * @return void
     */
    private function debug_redirect(): void {
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();
        $location = $response->headers->get('Location');

        if (!$response->isRedirection() || !$location) {
            echo "\n\n[STATUS]: $statusCode";
            echo "\n[LOCATION]: " . var_export($location, true);
            echo "\n[RESPONSE BODY]: " . ["show body disabled for now", "\n".$response->getContent()][0];
            echo "\nExpected a redirection, but response was $statusCode with Location: " . $location ?? 'null';
            exit(1);
        } else {
            echo "\n[OK] Redirected to: $location\n";
        }
    }

    public function testRegister(): void
    {
        // Register a new user
        $crawler = $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Register');

        // Select from by "Register" button
        $form = $crawler->selectButton('Register')->form();
        // Obtain CSRF token
        $csrfToken = $form->get('registration_form[_token]')->getValue();

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'me@test.com',
            'registration_form[plainPassword]' => 'password',
            'registration_form[agreeTerms]' => true,
            'registration_form[_token]' => $csrfToken,
        ]);

        $this->debug_redirect();

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        self::assertResponseRedirects('/login');
        self::assertCount(1, $this->userRepository->findAll());
        self::assertFalse(($user = $this->userRepository->findAll()[0])->isVerified());

        // Ensure the verification email was sent
        // Use either assertQueuedEmailCount() || assertEmailCount() depending on your mailer setup
        // self::assertQueuedEmailCount(1);
        self::assertEmailCount(1);

        self::assertCount(1, $messages = $this->getMailerMessages());
        self::assertEmailAddressContains($messages[0], 'from', 'matheu@vieiratechnology.shop');
        self::assertEmailAddressContains($messages[0], 'to', 'me@test.com');
        self::assertEmailTextBodyContains($messages[0], 'This link will expire in 1 hour.');

        // Login the new user
        $this->client->followRedirect();
        $this->client->loginUser($user);

        // Get the verification link from the email
        /** @var TemplatedEmail $templatedEmail */
        $templatedEmail = $messages[0];
        $messageBody = $templatedEmail->getHtmlBody();
        self::assertIsString($messageBody);

        preg_match('#https?://(localhost|127\.0\.0\.1)(:\d+)?/verify/email\?[^"]+#', $messageBody, $resetLink);

        if (!isset($resetLink[0])) {
            $this->fail("Verification URL not found in the email.");
        }

        $verification_email_url = $resetLink[0];

        // Extrai a query string da URL
        $query = parse_url($verification_email_url, PHP_URL_QUERY);

        // Converte a query string em array
        parse_str($query, $params);

        // We now have:
        $expires   = $params['expires'] ?? null;
        $id        = $params['id'] ?? null;
        $signature = $params['signature'] ?? null;
        $token     = $params['token'] ?? null;

        echo "\n[DEBUG] expires=$expires, id=$id, signature=$signature, token=$token\n";

        // "Click" the link and see if the user is verified
        $this->client->request('GET', $verification_email_url);

        self::assertTrue(static::getContainer()->get(UserRepository::class)->findAll()[0]->isVerified());
    }
}
