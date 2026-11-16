<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'endpoint' => env('AWS_ENDPOINT', 'https://127.0.0.1:9297'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => env('AWS_CA_BUNDLE', true),
            ],
        ];

        $this->app->singleton(\Aws\S3\S3Client::class, function ($app) use ($sharedConfig) {
            return new \Aws\S3\S3Client(array_merge($sharedConfig, ['use_path_style_endpoint' => true]));
        });

        $this->app->singleton(\Aws\Sqs\SqsClient::class, function ($app) use ($sharedConfig) {
            return new \Aws\Sqs\SqsClient($sharedConfig);
        });

        $this->app->singleton(\Aws\Sns\SnsClient::class, function ($app) use ($sharedConfig) {
            return new \Aws\Sns\SnsClient($sharedConfig);
        });

        $this->app->singleton(\Aws\DynamoDb\DynamoDbClient::class, function ($app) use ($sharedConfig) {
            return new \Aws\DynamoDb\DynamoDbClient($sharedConfig);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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
