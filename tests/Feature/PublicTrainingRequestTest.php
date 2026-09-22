<?php

namespace Tests\Feature;

use App\Mail\TrainingRequestConfirmation;
use App\Mail\TrainingRequestSubmitted;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicTrainingRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A complete, valid submission from an LGU — no account involved.
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'training_slug' => config('trainings.catalog.0.slug'),
            'agency_type' => TrainingRequest::AGENCY_TYPE_LGU,
            'requesting_agency' => 'Municipality of Fictional Town',
            'lgu' => 'Fictional Town',
            'region' => 'Region III',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            // A weekday roughly a month out, matching what the form defaults to.
            'preferred_date' => now()->addMonthNoOverflow()->next('Wednesday')->toDateString(),
            'venue' => 'Municipal Hall Function Room',
            'purpose' => 'Our newly organized MDRRM council has had no formal training yet.',
            'tna_completed' => '1',
            'logistics_acknowledged' => '1',
            'signature_name' => 'Maria Santos',
        ], $overrides);
    }

    public function test_the_request_form_is_reachable_without_logging_in(): void
    {
        $this->get(route('public.training-requests.create'))
            ->assertOk()
            ->assertSee('Request for Training');
    }

    public function test_the_landing_page_links_to_the_request_form(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('public.training-requests.create'));
    }

    public function test_a_guest_agency_can_file_a_request(): void
    {
        Mail::fake();

        $response = $this->post(route('public.training-requests.store'), $this->validPayload());

        $response->assertRedirect(route('public.training-requests.submitted'));

        $request = TrainingRequest::sole();

        // The agency itself is the requester: no account, no filing user.
        $this->assertNull($request->user_id);
        $this->assertSame(TrainingRequest::SOURCE_PUBLIC_PORTAL, $request->source);
        $this->assertSame(TrainingRequest::STATUS_SUBMITTED, $request->status);
        $this->assertSame(TrainingRequest::CATEGORY_TA, $request->category);
        $this->assertSame(TrainingRequest::AGENCY_TYPE_LGU, $request->agency_type);
        $this->assertSame('Fictional Town', $request->lgu);
        $this->assertTrue($request->tna_completed);
        $this->assertTrue($request->logistics_acknowledged);
        $this->assertSame('TR-'.now()->year.'-'.sprintf('%05d', $request->id), $request->reference_number);

        Mail::assertSent(TrainingRequestSubmitted::class);
        Mail::assertSent(TrainingRequestConfirmation::class);
    }

    public function test_the_confirmation_page_shows_the_reference_number(): void
    {
        Mail::fake();

        $this->post(route('public.training-requests.store'), $this->validPayload());

        $this->followingRedirects()
            ->get(route('public.training-requests.submitted'));

        $this->get(route('public.training-requests.submitted'))
            ->assertOk();
    }

    public function test_uploaded_paperwork_is_stored_off_the_public_disk(): void
    {
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');

        $this->post(route('public.training-requests.store'), $this->validPayload([
            'tna_file' => UploadedFile::fake()->create('tna.pdf', 100, 'application/pdf'),
            'signed_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
        ]));

        $request = TrainingRequest::sole();

        $this->assertNotNull($request->tna_file_path);
        $this->assertNotNull($request->signed_letter_path);
        Storage::disk('local')->assertExists($request->tna_file_path);
        Storage::disk('local')->assertExists($request->signed_letter_path);
        Storage::disk('public')->assertMissing($request->tna_file_path);
    }

    public function test_an_lgu_request_must_name_its_lgu(): void
    {
        $this->post(route('public.training-requests.store'), $this->validPayload(['lgu' => null]))
            ->assertSessionHasErrors('lgu');

        $this->assertSame(0, TrainingRequest::count());
    }

    public function test_an_nga_request_does_not_need_an_lgu(): void
    {
        Mail::fake();

        $this->post(route('public.training-requests.store'), $this->validPayload([
            'agency_type' => TrainingRequest::AGENCY_TYPE_NGA,
            'requesting_agency' => 'Department of Fictional Affairs',
            'lgu' => null,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, TrainingRequest::count());
    }

    public function test_weekend_dates_are_rejected(): void
    {
        $this->post(route('public.training-requests.store'), $this->validPayload([
            'preferred_date' => now()->addMonthNoOverflow()->next('Saturday')->toDateString(),
        ]))->assertSessionHasErrors('preferred_date');

        $this->assertSame(0, TrainingRequest::count());
    }

    public function test_the_tna_and_logistics_confirmations_are_required(): void
    {
        $this->post(route('public.training-requests.store'), $this->validPayload([
            'tna_completed' => null,
            'logistics_acknowledged' => null,
        ]))->assertSessionHasErrors(['tna_completed', 'logistics_acknowledged']);
    }

    public function test_a_filled_in_honeypot_is_rejected(): void
    {
        $this->post(route('public.training-requests.store'), $this->validPayload([
            'website' => 'https://spam.example.com',
        ]))->assertSessionHasErrors('website');

        $this->assertSame(0, TrainingRequest::count());
    }

    public function test_a_public_request_shows_up_for_review_with_its_attachments(): void
    {
        Mail::fake();
        Storage::fake('local');

        $this->post(route('public.training-requests.store'), $this->validPayload([
            'tna_file' => UploadedFile::fake()->create('tna.pdf', 100, 'application/pdf'),
        ]));

        $request = TrainingRequest::sole();
        $superAdmin = User::factory()->superAdmin()->create();

        // Summary defaults to newly received requests, so it should be listed there.
        $this->actingAs($superAdmin)
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee('Municipality of Fictional Town');

        $this->actingAs($superAdmin)
            ->get(route('admin.summary.edit', $request))
            ->assertOk()
            ->assertSee(route('admin.summary.attachment', [$request, 'tna']), false);

        $this->actingAs($superAdmin)
            ->get(route('admin.summary.attachment', [$request, 'tna']))
            ->assertOk();
    }

    public function test_an_admin_from_another_region_cannot_open_the_attachments(): void
    {
        Mail::fake();
        Storage::fake('local');

        $this->post(route('public.training-requests.store'), $this->validPayload([
            'tna_file' => UploadedFile::fake()->create('tna.pdf', 100, 'application/pdf'),
        ]));

        $request = TrainingRequest::sole();

        $ownRegionAdmin = User::factory()->admin('Region III')->create();
        $otherRegionAdmin = User::factory()->admin('NCR')->create();

        $this->actingAs($ownRegionAdmin)
            ->get(route('admin.summary.attachment', [$request, 'tna']))
            ->assertOk();

        $this->actingAs($otherRegionAdmin)
            ->get(route('admin.summary.attachment', [$request, 'tna']))
            ->assertForbidden();
    }

    public function test_a_request_without_that_attachment_is_not_found(): void
    {
        Mail::fake();

        $this->post(route('public.training-requests.store'), $this->validPayload());

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.summary.attachment', [TrainingRequest::sole(), 'letter']))
            ->assertNotFound();
    }
}
