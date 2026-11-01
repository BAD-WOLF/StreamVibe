<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . "/vendor/autoload.php";

if (method_exists(Dotenv::class, "bootEnv")) {
    new Dotenv()->bootEnv(dirname(__DIR__) . "/.env");
}

/*
 * NOTA (out/2026): par de chaves JWT só para os testes.
 *
 * phpunit.dist.xml aponta JWT_SECRET_KEY/JWT_PUBLIC_KEY para
 * var/jwt-test/ e usa JWT_PASSPHRASE="test". As chaves de config/jwt/
 * são as de dev (fora do git, com outra passphrase), então não servem
 * aqui. O par é gerado com a passphrase que estiver no ambiente e
 * reaproveitado nas execuções seguintes — mas só se a chave privada
 * abrir com essa passphrase; se não abrir (passphrase mudou, ou o par
 * veio de outra configuração), é gerado de novo. Não vai para o git
 * (var/ é ignorado) nem para a imagem (var/ está no .dockerignore).
 */
(static function (): void {
    $directory = dirname(__DIR__) . "/var/jwt-test";
    $privateKeyPath = $directory . "/private.pem";
    $publicKeyPath = $directory . "/public.pem";
    $passphrase = (string) ($_ENV["JWT_PASSPHRASE"] ?? getenv("JWT_PASSPHRASE") ?: "");

    $existingKeyOpens =
        is_file($privateKeyPath) &&
        is_file($publicKeyPath) &&
        openssl_pkey_get_private((string) file_get_contents($privateKeyPath), $passphrase) !== false;

    // Esvazia a fila de erros do OpenSSL (a tentativa acima e a geração
    // abaixo deixam mensagens inofensivas, como "RAND_load_file: Cannot
    // open file"). Sem isso, elas reaparecem misturadas na mensagem de
    // erro da próxima falha de verdade (ex.: InvalidKeyProvided do lcobucci).
    $clearOpensslErrors = static function (): void {
        while (openssl_error_string() !== false) {
        }
    };

    if ($existingKeyOpens) {
        $clearOpensslErrors();

        return;
    }

    $clearOpensslErrors();

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException(
            sprintf('Could not create the test JWT key directory "%s".', $directory),
        );
    }

    // openssl.cnf mínimo e explícito: openssl_pkey_new()/openssl_pkey_export()
    // falham quando o PHP não encontra um openssl.cnf padrão (comum no PHP
    // de Windows). Com um arquivo próprio, o resultado é o mesmo em
    // qualquer ambiente.
    $opensslConfigPath = $directory . "/openssl.cnf";
    file_put_contents(
        $opensslConfigPath,
        "[ req ]\ndistinguished_name = req_distinguished_name\n[ req_distinguished_name ]\n",
    );
    $opensslOptions = ["config" => $opensslConfigPath];

    $key = openssl_pkey_new($opensslOptions + [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);

    if (
        $key === false ||
        !openssl_pkey_export(
            $key,
            $privateKeyPem,
            $passphrase !== "" ? $passphrase : null,
            $opensslOptions,
        )
    ) {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        throw new RuntimeException(
            "Could not generate the test JWT key pair: " . implode(" | ", $errors),
        );
    }

    $details = openssl_pkey_get_details($key);

    file_put_contents($privateKeyPath, $privateKeyPem);
    file_put_contents($publicKeyPath, $details["key"]);

    $clearOpensslErrors();
})();
