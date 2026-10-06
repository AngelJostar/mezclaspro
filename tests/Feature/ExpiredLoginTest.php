<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class ExpiredLoginTest extends TestCase
{
    public function test_expired_login_redirects_to_a_new_form_without_preserving_password(): void
    {
        config(['session.driver' => 'array']);
        $session = app('session')->driver();
        $session->start();
        $request = Request::create('/login', 'POST', ['username' => 'test-user', 'password' => 'test-password']);
        $request->setLaravelSession($session);
        $response = app(Handler::class)->render($request, new TokenMismatchException());
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/login', $response->headers->get('Location'));
        $this->assertStringContainsString('formulario venció', $session->get('status'));
        $this->assertNull($session->get('_old_input.password'));
    }

    public function test_other_csrf_failures_keep_the_419_response(): void
    {
        $request = Request::create('/admin/users', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = app(Handler::class)->render($request, new TokenMismatchException());
        $this->assertSame(419, $response->getStatusCode());
    }
}
