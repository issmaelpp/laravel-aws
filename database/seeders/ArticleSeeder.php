<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Editor Principal',
            'email' => 'editor@example.com',
        ]);

        $team = $user->personalTeam() ?? Team::first() ?? Team::factory()->create([
            'name' => 'Comunidad Central',
            'is_personal' => false,
        ]);

        $topics = Topic::all();

        if ($topics->isEmpty()) {
            $this->call(TopicSeeder::class);
            $topics = Topic::all();
        }

        $sampleArticles = [
            [
                'title' => 'Bienvenida a nuestra plataforma de noticias y comunidad',
                'excerpt' => 'Descubre cómo crear artículos, colaborar en tu comunidad y publicar novedades.',
                'content' => 'Nos complace presentar este nuevo espacio donde cada comunidad puede crear sus artículos y compartirlos tanto con sus miembros como con el portal principal de noticias. Esta plataforma integra AWS localmente para una gestión segura y escalable.',
                'status' => ArticleStatus::Published,
                'visibility' => ArticleVisibility::Public,
                'published_at' => now()->subDays(2),
                'topic' => $topics->firstWhere('slug', 'comunidad') ?? $topics->first(),
            ],
            [
                'title' => 'Innovación y desarrollo local con tecnologías modernas',
                'excerpt' => 'El impacto de la tecnología en la comunicación comunitaria y la creación de contenidos.',
                'content' => 'Las herramientas modernas permiten a las comunidades descentralizar la comunicación y crear canales informativos directos y transparentes.',
                'status' => ArticleStatus::Published,
                'visibility' => ArticleVisibility::Public,
                'published_at' => now()->subDay(),
                'topic' => $topics->firstWhere('slug', 'tecnologia') ?? $topics->first(),
            ],
            [
                'title' => 'Borrador interno: Próximos eventos de la comunidad',
                'excerpt' => 'Plan de actividades y talleres para el próximo mes.',
                'content' => 'Este es un artículo en borrador solo visible para los miembros de la comunidad para coordinar la agenda del mes.',
                'status' => ArticleStatus::Draft,
                'visibility' => ArticleVisibility::Community,
                'published_at' => null,
                'topic' => $topics->firstWhere('slug', 'comunidad') ?? $topics->first(),
            ],
        ];

        foreach ($sampleArticles as $articleData) {
            Article::firstOrCreate(
                ['slug' => Str::slug($articleData['title'])],
                [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'topic_id' => $articleData['topic']?->id,
                    'title' => $articleData['title'],
                    'excerpt' => $articleData['excerpt'],
                    'content' => $articleData['content'],
                    'status' => $articleData['status'],
                    'visibility' => $articleData['visibility'],
                    'published_at' => $articleData['published_at'],
                ]
            );
        }
    }
}
