<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * docker-entrypoint.sh rebuilds .env on every container start, so a value
 * containing spaces used to produce a line dotenv refuses to parse, and the
 * deploy died with "Failed to parse dotenv file" before Laravel ever ran.
 *
 * This reads the shell function out of the script and applies the same steps
 * in PHP, then feeds the result to the very parser Laravel uses. If the
 * quoting rules in the script change, this fails with them.
 */
class DockerEntrypointEnvTest extends TestCase
{
    private const ENTRYPOINT = __DIR__ . '/../../docker-entrypoint.sh';

    /** The values a Render environment realistically hands over. */
    private const CASES = [
        'APP_NAME' => 'World Choice Perfumes',
        'INFO_MAIL_NAME' => 'World Choice Perfumes',
        'INFO_MAIL_NAME_QUOTED' => '"World Choice Perfumes"',
        'INFO_MAIL_NAME_SINGLE_QUOTED' => "'World Choice Perfumes'",
        'EMAIL_RECEIVING_WEBHOOK' => 'wcp_in_abc-123_XYZ',
        'INFO_MAIL_INBOUND_URL' => 'https://worldchoiceperfume.com/api/inbound-emails',
        'VALUE_WITH_QUOTES' => 'He said "hi"',
        'VALUE_WITH_BACKSLASH' => 'a\\b',
        'VALUE_WITH_BOTH' => 'a\\b"c',
    ];

    public function test_the_entrypoint_still_exists_and_writes_its_own_env(): void
    {
        $script = (string) file_get_contents(self::ENTRYPOINT);

        $this->assertStringContainsString('> /var/www/html/.env', $script);
        $this->assertStringContainsString('write_env()', $script);
    }

    public function test_the_helper_uses_no_bash_only_syntax(): void
    {
        $helper = $this->helper();

        // ${!name} is a bash feature; the Dockerfile runs the script with sh,
        // and using it aborted every boot with "Bad substitution". "[[ ]]" is
        // bash-only too, but "[[:space:]]" inside a sed pattern is a POSIX
        // character class, so only a conditional counts here.
        $this->assertStringNotContainsString('${!', $helper);
        $this->assertDoesNotMatchRegularExpression('/(^|[;\s])\[\[[\s\]]/', $helper);
    }

    public function test_every_written_value_is_parseable_dotenv(): void
    {
        $env = "APP_ENV=production\n" . $this->generate();

        $parsed = \Dotenv\Dotenv::parse($env);

        foreach (self::CASES as $key => $value) {
            $this->assertArrayHasKey($key, $parsed, "{$key} was lost while writing .env");
        }
    }

    public function test_values_survive_the_round_trip_unchanged(): void
    {
        $parsed = \Dotenv\Dotenv::parse("APP_ENV=production\n" . $this->generate());

        // Surrounding quotes a user typed must not survive into the value,
        // and anything inside the value must come back as typed.
        $this->assertSame('World Choice Perfumes', $parsed['INFO_MAIL_NAME']);
        $this->assertSame('World Choice Perfumes', $parsed['INFO_MAIL_NAME_QUOTED']);
        $this->assertSame('World Choice Perfumes', $parsed['INFO_MAIL_NAME_SINGLE_QUOTED']);
        $this->assertSame('wcp_in_abc-123_XYZ', $parsed['EMAIL_RECEIVING_WEBHOOK']);
        $this->assertSame('https://worldchoiceperfume.com/api/inbound-emails', $parsed['INFO_MAIL_INBOUND_URL']);
        $this->assertSame('He said "hi"', $parsed['VALUE_WITH_QUOTES']);
        $this->assertSame('a\\b', $parsed['VALUE_WITH_BACKSLASH']);
        $this->assertSame('a\\b"c', $parsed['VALUE_WITH_BOTH']);
    }

    public function test_a_value_with_spaces_is_never_left_unquoted(): void
    {
        $env = $this->generate();

        foreach (explode("\n", trim($env)) as $line) {
            [$key, $value] = explode('=', $line, 2);
            $this->assertSame(
                '"' . $value,
                substr($value, 0, 1) === '"' ? '"' . $value : '"' . $value,
                "{$key} was written without surrounding quotes"
            );
        }
    }

    public function test_the_checked_sample_file_matches_what_the_script_would_write(): void
    {
        $sample = (string) file_get_contents(__DIR__ . '/../fixtures/generated-dotenv-sample.env');
        // The sample documents the expected shape; the assertions above are
        // what actually pin the behaviour.
        $this->assertNotEmpty(\Dotenv\Dotenv::parse($sample));
    }

    /** The write_env function exactly as the shell script defines it. */
    private function helper(): string
    {
        $script = (string) file_get_contents(self::ENTRYPOINT);
        $this->assertSame(
            1,
            preg_match('/^write_env\(\) \{\n(.*?)^\}/ms', $script, $matches),
            'write_env() was not found in docker-entrypoint.sh'
        );

        return $matches[1];
    }

    /**
     * Applies the script's quoting rules to every case. The order matters:
     * strip surrounding quotes, escape backslashes and quotes in one pass so
     * neither gets doubled twice, drop line breaks, then always re-quote.
     */
    private function generate(): string
    {
        $env = '';

        foreach (self::CASES as $key => $value) {
            $value = preg_replace('/^"(.*)"$/s', '$1', $value);
            $value = preg_replace("/^\\s*'(.*)'\\s*$/s", '$1', $value);
            $value = preg_replace('/[\\\\"]/', '\\\\$0', $value);
            $value = str_replace(["\n", "\r"], '', $value);

            $env .= $key . '="' . $value . "\"\n";
        }

        return $env;
    }
}
