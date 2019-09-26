<?php

namespace Tests\Feature;

use App\Contact;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    /** @test * */
    public function an_unauthenticated_user_should_be_redirected_to_login()
    {
        // $this->withoutExceptionHandling();

        $response = $this->post('/api/contacts',
            array_merge($this->data(),
                ['api_token' => '']));

        $response->assertRedirect('/login');

        $this->assertCount(0,
            Contact::all());
    }

    /** @test * */
    public function a_list_of_contacts_can_be_fetched_for_an_authenticated_user()
    {
//        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();

        $contact = factory(Contact::class)->create(['user_id' => $user->id]);
        factory(Contact::class)->create(['user_id' => $anotherUser->id]);

        $response = $this->get("/api/contacts?api_token={$user->api_token}");

        $response->assertJsonCount(1)
            ->assertJson([
                'data' => [
                    [
                        'data' => [
                            'contact_id' => $contact->id,
                        ],
                    ],
                ],
            ]);
    }

    /** @test * */
    public function an_authenticated_user_can_add_a_contact()
    {
        $this->withoutExceptionHandling();

        $response = $this->post('/api/contacts',
            $this->data());

        $contact = Contact::first();

        $this->assertEquals('Test Name',
            $contact->name);
        $this->assertEquals('test@email.com',
            $contact->email);
        $this->assertEquals('05/14/1988',
            $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC String',
            $contact->company);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data'  => [
                'contact_id' => $contact->id,
            ],
            'links' => [
                'self' => $contact->path(),
            ],
        ]);
    }

    /** @test * */
    public function fields_are_required()
    {
        collect(['name', 'email', 'birthday', 'company'])
            ->each(function ($field) {
                $response = $this->post('/api/contacts',
                    array_merge($this->data(),
                        [$field => '']));

                $response->assertSessionHasErrors($field);

                $this->assertCount(0,
                    Contact::all());
            });
    }

    /** @test * */
    public function email_must_be_a_valid_email()
    {
        // $this->withoutExceptionHandling();

        $response = $this->post('/api/contacts',
            array_merge($this->data(),
                ['email' => 'NOT AN EMAIL']));

        $response->assertSessionHasErrors('email');

        $this->assertCount(0,
            Contact::all());
    }

    /** @test * */
    public function birthdays_are_properly_stored()
    {
//        $this->withoutExceptionHandling();

        $response = $this->post('/api/contacts',
            $this->data());

        $this->assertCount(1,
            Contact::all());

        $this->assertInstanceOf(Carbon::class,
            Contact::first()->birthday);
        $this->assertEquals('05-14-1988',
            Contact::first()->birthday->format('m-d-Y'));
    }

    /** @test * */
    public function a_contact_can_be_retrieved()
    {
        // $this->withoutExceptionHandling();

        $contact
            = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response
            = $this->get("/api/contacts/{$contact->id}?api_token={$this->user->api_token}");

        $response->assertJson([
            'data' => [
                'contact_id'   => $contact->id,
                'name'         => $contact->name,
                'email'        => $contact->email,
                'birthday'     => $contact->birthday->format('m/d/Y'),
                'company'      => $contact->company,
                'last_updated' => $contact->updated_at->diffForHumans(),
            ],
        ]);
    }

    /** @test * */
    public function only_the_users_contacts_can_be_retrieved()
    {
        // $this->withoutExceptionHandling();

        $contact
            = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $anotherUser
            = factory(User::class)->create();
        $response
            = $this->get("/api/contacts/{$contact->id}?api_token={$anotherUser->api_token}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test * */
    public function a_contact_can_be_patched()
    {
//        $this->withoutExceptionHandling();

        $contact
            = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->patch('/api/contacts/' . $contact->id,
            $this->data());

        $contact = $contact->fresh();

        $this->assertEquals('Test Name',
            $contact->name);
        $this->assertEquals('test@email.com',
            $contact->email);
        $this->assertEquals('05/14/1988',
            $contact->birthday->format('m/d/Y'));
        $this->assertEquals('ABC String',
            $contact->company);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data'  => [
                'contact_id' => $contact->id,
            ],
            "links" => [
                'self' => $contact->path(),
            ],
        ]);
    }

    /** @test * */
    public function only_the_owner_of_the_contact_can_patch_the_contact()
    {
        // $this->withoutExceptionHandling();

        $contact
            = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $anotherUser = factory(User::class)->create();

        $response = $this->patch('/api/contacts/' . $contact->id,
            array_merge($this->data(),
                ['api_token' => $anotherUser->api_token]));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test * */
    public function a_contact_can_be_deleted()
    {
        $this->withoutExceptionHandling();

        $contact
            = factory(Contact::class)->create(['user_id' => $this->user->id]);

        $response = $this->delete('/api/contacts/' . $contact->id,
            ['api_token' => $this->user->api_token]);

        $this->assertCount(0,
            Contact::all());
        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    /** @test * */
    public function only_the_owner_of_the_contact_can_delete_the_contact()
    {
        // $this->withoutExceptionHandling();

        $contact = factory(Contact::class)->create();

        $anotherUser = factory(User::class)->create();

        $response = $this->delete('/api/contacts/' . $contact->id,
            ['api_token' => $this->user->api_token]);

        $response->assertStatus(403);
    }

    /**
     * @method data
     * @return array
     */
    private function data(): array
    {
        return [
            'name'      => 'Test Name',
            'email'     => 'test@email.com',
            'birthday'  => '05/14/1988',
            'company'   => 'ABC String',
            'api_token' => $this->user->api_token,
        ];
    }
}
