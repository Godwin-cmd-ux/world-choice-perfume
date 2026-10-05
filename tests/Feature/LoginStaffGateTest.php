<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The staff login page is hidden behind the company secret code.
 *
 * Visiting /login must prompt for the code and must not render the email and
 * password form until the code has been accepted, and the form must not be
 * submittable by posting to /login directly either.
 */
class LoginStaffGateTest extends TestCase
{
    public function test_login_page_prompts_for_the_staff_code_when_not_verified(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.staff-code');
    }

    public function test_the_login_form_is_not_exposed_before_verification(): void
    {
        $response = $this->get('/login');

        $response->assertDontSee('Sign in to your staff account');
        $response->assertDontSee('name="password"', false);
    }

    public function test_login_page_shows_the_form_once_the_code_is_verified(): void
    {
        $response = $this->withSession(['staff_access_verified' => true])->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('Sign in to your staff account');
    }

    public function test_posting_login_without_the_code_does_not_authenticate(): void
    {
        $response = $this->post('/login', [
            'email' => 'someone@example.co.tz',
            'password' => 'secret-password',
        ]);

        $response->assertOk();
        $response->assertViewIs('auth.staff-code');
        $this->assertGuest();
    }
}
