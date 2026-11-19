<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml';

    public function handle()
    {
        $sitemap = Sitemap::create();

        // Статические страницы
        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('daily'));
        $sitemap->add(Url::create('/')->setPriority(0.8)->setChangeFrequency('monthly'));

        // Посты
        Post::chunk(100, function ($posts) use ($sitemap) {
            foreach ($posts as $post) {
                $sitemap->add(
                    Url::create("/post/{$post->slug}")
                        ->setLastModificationDate(Carbon::parse($post->updated_at))
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.9)
                );
            }
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('✅ sitemap.xml успешно сгенерирован в папке public/');
    }
}