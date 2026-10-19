<?php

namespace Tests\Feature;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * An instructor is one person who can teach several courses, hold several
 * certifications, and have been deployed more than once (built 2026-10-19 —
 * each of those used to be a single column, so a person teaching three
 * courses was three near-duplicate rows).
 *
 * Both admins maintain the roster; a Regional Admin is confined to their own
 * region, and only a Super Admin moves anyone between regions (that part is
 * covered by RegionTransferTest).
 */
class InstructorRosterTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(string $region = 'Region III'): Instructor
    {
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => $region]);
        $instructor->trainingTypes()->create(['training_type' => 'ICS']);

        return $instructor;
    }

    public function test_an_instructor_is_added_with_several_courses_certifications_and_deployments(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.instructors.store'), [
                'name' => 'Aidan Lueilwitz',
                'region' => 'Region III',
                'training_types' => [
                    ['training_type' => 'DRRM Legal Framework and Policies', 'certificate_code' => 'INS-001'],
                    ['training_type' => 'Incident Command System (ICS)', 'certificate_code' => 'INS-002'],
                    ['training_type' => 'Search and Rescue Operations'],
                ],
                'certifications' => [
                    ['title' => 'Accredited ICS Instructor', 'code' => 'CERT-1'],
                    ['title' => 'Certified SAR Trainer', 'code' => 'CERT-2'],
                ],
                'deployments' => [
                    ['deployment' => 'RDANA', 'deployment_date' => '2025-06-01', 'city' => 'City of Legazpi, Albay'],
                    ['deployment' => 'EOC', 'deployment_role' => 'Team Leader'],
                ],
            ])
            ->assertRedirect();

        $instructor = Instructor::sole();

        $this->assertSame('Aidan Lueilwitz', $instructor->name);
        $this->assertSame(3, $instructor->trainingTypes()->count());
        $this->assertSame(2, $instructor->certifications()->count());
        $this->assertSame(2, $instructor->deployments()->count());
        $this->assertSame('City of Legazpi, Albay', $instructor->deployments()->where('deployment', 'RDANA')->sole()->city);
    }

    public function test_blank_repeatable_rows_are_dropped_and_one_course_is_required(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // Every row left empty — the form always submits its first one.
        $this->actingAs($superAdmin)
            ->post(route('admin.instructors.store'), [
                'name' => 'Nobody',
                'training_types' => [['training_type' => '', 'certificate_code' => '']],
            ])
            ->assertSessionHasErrors('training_types');

        $this->assertSame(0, Instructor::count());

        $this->actingAs($superAdmin)
            ->post(route('admin.instructors.store'), [
                'name' => 'Ana Reyes',
                'training_types' => [
                    ['training_type' => 'ICS'],
                    ['training_type' => ''],
                ],
                'deployments' => [['deployment' => '', 'deployment_role' => '']],
            ])
            ->assertRedirect();

        $instructor = Instructor::sole();
        $this->assertSame(1, $instructor->trainingTypes()->count());
        $this->assertSame(0, $instructor->deployments()->count());
    }

    public function test_a_regional_admin_adds_instructors_locked_to_their_own_region(): void
    {
        $this->actingAs(User::factory()->admin('Region V')->create())
            ->post(route('admin.instructors.store'), [
                'name' => 'Ana Reyes',
                // Deliberately posting another region — it must be ignored.
                'region' => 'Region III',
                'training_types' => [['training_type' => 'ICS']],
            ])
            ->assertRedirect();

        $this->assertSame('Region V', Instructor::sole()->region);
    }

    public function test_courses_certifications_and_deployments_can_be_added_and_removed_later(): void
    {
        Storage::fake('public');
        $instructor = $this->instructor();
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)
            ->post(route('admin.instructors.training-types.store', $instructor), ['training_type' => 'CBDRRM'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.instructors.certifications.store', $instructor), [
                'title' => 'Accredited ICS Instructor',
                'file' => UploadedFile::fake()->create('cert.pdf', 40, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.instructors.deployments.store', $instructor), [
                'deployment' => 'RDANA',
                'city' => 'City of Legazpi, Albay',
            ])
            ->assertRedirect();

        $this->assertSame(2, $instructor->trainingTypes()->count());
        $this->assertSame(1, $instructor->certifications()->count());
        $this->assertSame(1, $instructor->deployments()->count());

        $certification = $instructor->certifications()->sole();
        Storage::disk('public')->assertExists($certification->file_path);

        $this->actingAs($admin)
            ->delete(route('admin.instructors.certifications.destroy', $certification))
            ->assertRedirect();

        // Removing a certification takes its scan with it.
        Storage::disk('public')->assertMissing($certification->file_path);
        $this->assertSame(0, $instructor->certifications()->count());

        $this->actingAs($admin)
            ->delete(route('admin.instructors.deployments.destroy', $instructor->deployments()->sole()))
            ->assertRedirect();

        $this->assertSame(0, $instructor->deployments()->count());
    }

    public function test_the_last_training_type_cannot_be_removed(): void
    {
        $instructor = $this->instructor();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('admin.instructors.training-types.destroy', $instructor->trainingTypes()->sole()))
            ->assertSessionHasErrors('training_type');

        $this->assertSame(1, $instructor->trainingTypes()->count());
    }

    public function test_a_regional_admin_cannot_touch_another_regions_instructor(): void
    {
        $theirs = $this->instructor('Region V');
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)->get(route('admin.instructors.show', $theirs))->assertNotFound();
        $this->actingAs($admin)
            ->post(route('admin.instructors.training-types.store', $theirs), ['training_type' => 'CBDRRM'])
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('admin.instructors.deployments.store', $theirs), ['deployment' => 'RDANA'])
            ->assertForbidden();
        $this->actingAs($admin)
            ->delete(route('admin.instructors.training-types.destroy', $theirs->trainingTypes()->sole()))
            ->assertForbidden();

        $this->assertSame(1, $theirs->trainingTypes()->count());
    }

    public function test_the_roster_lists_every_course_and_can_be_searched_by_one(): void
    {
        $instructor = $this->instructor();
        $instructor->trainingTypes()->create(['training_type' => 'Psychological First Aid']);
        Instructor::create(['name' => 'Ben Cruz', 'region' => 'Region III'])
            ->trainingTypes()->create(['training_type' => 'Camp Management']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.instructors.index'))
            ->assertOk()
            ->assertSee('ICS')
            ->assertSee('Psychological First Aid');

        // Searching a course the instructor teaches finds the person, and
        // leaves out whoever doesn't teach it.
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.instructors.index', ['instructors_q' => 'Psychological']))
            ->assertOk()
            ->assertSee('Ana Reyes')
            ->assertDontSee('Ben Cruz');
    }

    public function test_the_overall_rating_is_the_average_across_their_courses(): void
    {
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => 'Region III']);
        $instructor->trainingTypes()->createMany([
            ['training_type' => 'ICS', 'rating' => 4.00],
            ['training_type' => 'CBDRRM', 'rating' => 5.00],
            ['training_type' => 'SAR', 'rating' => null],
        ]);

        $instructor->refreshOverallRating();

        // Unrated courses sit out of the average rather than counting as zero.
        $this->assertEquals(4.5, $instructor->fresh()->rating);
    }
}
