<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_photos_use_the_configured_filesystem_disk(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);

        $user = User::factory()->create(['avatar_path' => 'avatars/old.gif']);
        Storage::disk('s3')->put($user->avatar_path, 'old photo');
        $photo = UploadedFile::fake()->createWithContent(
            'new.gif',
            base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=')
        );

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $photo,
        ])->assertRedirect();

        $user->refresh();

        $this->assertNotSame('avatars/old.gif', $user->avatar_path);
        Storage::disk('s3')->assertExists($user->avatar_path);
        Storage::disk('s3')->assertMissing('avatars/old.gif');
    }
}
