<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AvatarStorageService
{
    private const BUCKET_FOLDER = 'avatars';

    /**
     * Upload avatar baru.
     */
    public function store(UploadedFile $file, User $user): string
    {
        $path = sprintf(
            '%s/%s/avatar.webp',
            self::BUCKET_FOLDER,
            $user->id
        );

        Storage::disk('s3')->putFileAs(
            dirname($path),
            $file,
            basename($path),
            [
                'visibility' => 'private',
            ]
        );

        return $path;
    }

    /**
     * Ganti avatar.
     */
    public function replace(UploadedFile $file, User $user): string
    {
        $this->delete($user);

        return $this->store($file, $user);
    }

    /**
     * Hapus semua avatar user.
     */
    public function delete(User $user): bool
    {
        $directory = self::BUCKET_FOLDER . '/' . $user->id;

        $files = Storage::disk('s3')->allFiles($directory);

        if (empty($files)) {
            return true;
        }

        Storage::disk('s3')->delete($files);

        return true;
    }

    /**
     * Ambil URL avatar.
     */
    public function getUrl(User $user): ?string
    {
        if (!$user->avatar_key) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            $user->avatar_key,
            now()->addMinutes(60),
        );
    }


    public function importFromUrl(
        string $url,
        User $user
    ): string {

        $path = sprintf(
            '%s/%s/avatar.webp',
            self::BUCKET_FOLDER,
            $user->id
        );

        $url = preg_replace(
            '/=s\d+-c$/',
            '=s512-c',
            $url
        );

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            throw new \Exception('Failed to download avatar.');
        }

        Storage::disk('s3')->put(
            $path,
            $response->body(),
            [
                'visibility' => 'private',
            ]
        );

        return $path;
    }
}
