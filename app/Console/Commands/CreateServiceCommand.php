<?php

namespace App\Console\Commands;

use App\Services\ServiceAuthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('service:create')]
#[Description('Register a new internal service')]
class CreateServiceCommand extends Command
{
    public function __construct(
        protected ServiceAuthService $serviceAuthService
    ) {
        parent::__construct();
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Service name (ex: pinat photo)');
        $slug = $this->ask('Slug (ex: pinat-photo)');
        $bucket = $this->choice('Default bucket', [
            'drive',
            'photos',
            'music',
            'avatars',
            'temp'
        ]);
        $scopes = $this->choice(
            'Scopes',
            [
                'filesystem.read',
                'filesystem.write',
                'filesystem.delete',
                'filesystem.share',
                'filesystem.admin',
            ],
            multiple: true
        );
        $result = $this->serviceAuthService->register([
            'name' => $name,
            'slug' => $slug,
            'default_bucket' => $bucket,
            'allowed_scopes' => array_map('trim', $scopes)
        ]);

        $this->newLine();
        $this->info('Service registered succesfully.');

        $this->table(
            ['Field', 'Value'],
            [
                ['Client ID', $result['credentials']['client_id']],
                ['Client Secret', $result['credentials']['client_secret']],
            ]
        );

        $this->warn(
            'Save the client secret now. It will never be shown again.'
        );

        return self::SUCCESS;
    }
}
