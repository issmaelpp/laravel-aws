<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultTopics = [
            [
                'name' => 'Tecnología',
                'description' => 'Noticias sobre innovación, software, hardware e inteligencia artificial.',
                'color' => '#3b82f6',
            ],
            [
                'name' => 'Deportes',
                'description' => 'Actualidad deportiva local, nacional e internacional.',
                'color' => '#10b981',
            ],
            [
                'name' => 'Política & Sociedad',
                'description' => 'Debates comunitarios, actualidad política y sociedad civil.',
                'color' => '#ef4444',
            ],
            [
                'name' => 'Cultura & Arte',
                'description' => 'Música, literatura, cine, eventos culturales y expresiones artísticas.',
                'color' => '#8b5cf6',
            ],
            [
                'name' => 'Ciencia & Medio Ambiente',
                'description' => 'Descubrimientos científicos, ecología y sostenibilidad.',
                'color' => '#06b6d4',
            ],
            [
                'name' => 'Comunidad',
                'description' => 'Historias, iniciativas y anuncios de las comunidades locales.',
                'color' => '#f59e0b',
            ],
        ];

        foreach ($defaultTopics as $topicData) {
            Topic::firstOrCreate(
                ['slug' => Str::slug($topicData['name'])],
                [
                    'name' => $topicData['name'],
                    'description' => $topicData['description'],
                    'color' => $topicData['color'],
                    'is_active' => true,
                    'team_id' => null,
                ]
            );
        }
    }
}
