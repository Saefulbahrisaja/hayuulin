<?php

namespace Tests\Feature;

use Tests\TestCase;

class LanguageTest extends TestCase
{
    public function test_language_choice_is_saved_and_used_on_the_next_page(): void
    {
        $this->from('/')
            ->post(route('language.update'), ['locale' => 'en'])
            ->assertRedirect('/');

        $this->assertSame('en', session('locale'));

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('Plan a trip');
    }

    public function test_sundanese_language_can_be_selected(): void
    {
        $this->post(route('language.update'), ['locale' => 'su'])
            ->assertRedirect(route('home'));

        $this->assertSame('su', session('locale'));
        $this->get('/')->assertOk()->assertSee('Ulin ka Sukabumi!');
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->from('/')
            ->post(route('language.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }

    public function test_language_change_does_not_redirect_to_an_external_host(): void
    {
        $this->from('https://example.org/outside')
            ->post(route('language.update'), ['locale' => 'en'])
            ->assertRedirect(route('home'));
    }
}
