<?php

namespace App\Providers;

use App\Models\Team;
use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $sharedConfig = [
            'version' => 'latest',
            'region' => config('services.aws.region', 'us-east-1'),
            'endpoint' => config('services.aws.endpoint', 'https://127.0.0.1:9297'),
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
            'http' => [
                'verify' => config('services.aws.ca_bundle', true),
            ],
        ];

        $this->app->singleton(S3Client::class, function ($app) use ($sharedConfig) {
            return new S3Client(array_merge($sharedConfig, ['use_path_style_endpoint' => true]));
        });

        $this->app->singleton(SqsClient::class, function ($app) use ($sharedConfig) {
            return new SqsClient($sharedConfig);
        });

        $this->app->singleton(SnsClient::class, function ($app) use ($sharedConfig) {
            return new SnsClient($sharedConfig);
        });

        $this->app->singleton(DynamoDbClient::class, function ($app) use ($sharedConfig) {
            return new DynamoDbClient($sharedConfig);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Route::bind('current_team', fn (string $slug): Team => Team::where('slug', $slug)->firstOrFail());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
