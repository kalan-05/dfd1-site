<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\GalleryItem;
use App\Models\PageBlock;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class InitialContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'ОФД №1 — Отделение функциональной диагностики',
                'phone_1' => '+7 901 309-26-63',
                'phone_2' => null,
                'email' => 'kalan.05@yandex.ru',
                'address_main' => 'Адрес: 197022, Российская Федерация, г. Санкт-Петербург, ул. Льва Толстого, д. 6-8, 3-й этаж, кабинеты 1–7, 38',
                'worktime_main' => 'Ежедневно, кроме субботы и воскресенья, с 09:00 до 16:50',
                'social' => [
                    'tg' => '',
                    'wa' => '',
                ],
                'seo_title' => 'ОФД №1 — Отделение функциональной диагностики ПСПбГМУ им. акад. И.П. Павлова',
                'seo_description' => 'Комплексная функциональная и ультразвуковая диагностика сердца и сосудов в Санкт-Петербурге.',
                'seo_keywords' => 'функциональная диагностика, эхокардиография, суточное мониторирование ЭКГ, Санкт-Петербург',
                'map_lat' => 59.967137,
                'map_lng' => 30.316438,
                'map_zoom' => 16,
            ]
        );

        $pageBlocks = [
            [
                'key' => 'hero',
                'title' => 'Комплексная диагностика',
                'content' => 'сосудов и сердца',
                'is_enabled' => true,
                'sort_order' => 10,
                'meta' => ['section_id' => 'hero'],
            ],
            [
                'key' => 'about',
                'title' => 'О нас',
                'content' => 'Отделение функциональной диагностики №1 ПСПбГМУ им. акад. И.П. Павлова.',
                'is_enabled' => true,
                'sort_order' => 20,
                'meta' => ['section_id' => 'about'],
            ],
            [
                'key' => 'services',
                'title' => 'Диагностика',
                'content' => 'Полный спектр функциональной и ультразвуковой диагностики.',
                'is_enabled' => true,
                'sort_order' => 30,
                'meta' => ['section_id' => 'services'],
            ],
            [
                'key' => 'doctors',
                'title' => 'Наши врачи',
                'content' => 'Сотрудники отделения функциональной диагностики.',
                'is_enabled' => true,
                'sort_order' => 40,
                'meta' => ['section_id' => 'pricing'],
            ],
            [
                'key' => 'gallery',
                'title' => 'Галерея',
                'content' => null,
                'is_enabled' => true,
                'sort_order' => 50,
                'meta' => ['section_id' => 'gallery'],
            ],
            [
                'key' => 'reviews',
                'title' => 'Отзывы',
                'content' => null,
                'is_enabled' => true,
                'sort_order' => 60,
                'meta' => ['section_id' => 'reviews'],
            ],
            [
                'key' => 'contact',
                'title' => 'Контакты',
                'content' => null,
                'is_enabled' => true,
                'sort_order' => 70,
                'meta' => ['section_id' => 'contact'],
            ],
            [
                'key' => 'map',
                'title' => 'Карта',
                'content' => null,
                'is_enabled' => true,
                'sort_order' => 80,
                'meta' => ['section_id' => 'map-section'],
            ],
        ];

        foreach ($pageBlocks as $block) {
            PageBlock::query()->updateOrCreate(
                ['key' => $block['key']],
                $block
            );
        }

        $doctors = [
            ['full_name' => 'Бутомо Мария Игоревна', 'position' => 'Заведующая отделением', 'regalia' => 'к.м.н.'],
            ['full_name' => 'Нифонтов Сергей Евгеньевич', 'position' => 'Врач УЗИ', 'regalia' => null],
            ['full_name' => 'Шихалиев Джошгун Рагимович', 'position' => 'Врач УЗИ, ФД', 'regalia' => null],
            ['full_name' => 'Лозовая Татьяна Александровна', 'position' => 'Врач УЗИ', 'regalia' => 'к.м.н.'],
            ['full_name' => 'Оксас Арвид Евгеньевич', 'position' => 'Врач УЗИ, ФД', 'regalia' => null],
            ['full_name' => 'Козленок Наталья Владимировна', 'position' => 'Врач УЗИ', 'regalia' => null],
            ['full_name' => 'Акименко Алла Федоровна', 'position' => 'Врач ФД', 'regalia' => null],
            ['full_name' => 'Оболенцева Екатерина Юрьевна', 'position' => 'Врач ФД', 'regalia' => null],
            ['full_name' => 'Скуридин Даниил Сергеевич', 'position' => 'Врач УЗИ, ФД', 'regalia' => null],
            ['full_name' => 'Попова Елена Александровна', 'position' => 'Старшая медсестра', 'regalia' => null],
            ['full_name' => 'Медведева Ольга Витальевна', 'position' => 'Медсестра', 'regalia' => null],
            ['full_name' => 'Косогова Александра Анатольевна', 'position' => 'Медсестра', 'regalia' => null],
            ['full_name' => 'Прокопенкова Наталья Андреевна', 'position' => 'Медсестра', 'regalia' => null],
            ['full_name' => 'Калецкая Светлана Яковлевна', 'position' => 'Сестра-хозяйка', 'regalia' => null],
        ];

        $doctorIdsByName = [];

        foreach ($doctors as $index => $doctorData) {
            $doctor = Doctor::query()->updateOrCreate(
                ['full_name' => $doctorData['full_name']],
                [
                    ...$doctorData,
                    'description' => null,
                    'photo' => null,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );

            $doctorIdsByName[$doctor->full_name] = $doctor->id;
        }

        $services = [
            ['title' => 'Электрокардиография', 'group' => 'Кардиодиагностика'],
            ['title' => 'Холтеровское мониторирование (в т.ч. многоканальное)', 'group' => 'Кардиодиагностика'],
            ['title' => 'Суточное мониторирование артериального давления', 'group' => 'Кардиодиагностика'],
            ['title' => 'Синхронное суточное мониторирование ЭКГ и АД', 'group' => 'Кардиодиагностика'],
            ['title' => 'Кардиореспираторное мониторирование', 'group' => 'Кардиодиагностика'],
            ['title' => 'Нагрузочные тесты (велоэргометрия, тредмил-тест)', 'group' => 'Кардиодиагностика'],
            ['title' => 'Эхокардиография, включая 2D Strain, speckle tracking и тканевой допплер', 'group' => 'Кардиодиагностика'],
            ['title' => 'Стресс-ЭХО КГ на горизонтальном велоэргометре', 'group' => 'Кардиодиагностика'],
            ['title' => 'Чреспищеводное ЭХОКГ', 'group' => 'УЗИ и функциональные тесты'],
            ['title' => 'Дуплексное сканирование сосудов конечностей, БЦА и почечных сосудов', 'group' => 'УЗИ и функциональные тесты'],
            ['title' => 'Тилт-тест', 'group' => 'УЗИ и функциональные тесты'],
            ['title' => 'Струп-тест', 'group' => 'УЗИ и функциональные тесты'],
            ['title' => 'Компьютерная пульсоксиметрия', 'group' => 'УЗИ и функциональные тесты'],
            ['title' => 'Общие ультразвуковые исследования (комплексные УЗИ)', 'group' => 'УЗИ и функциональные тесты'],
        ];

        foreach ($services as $index => $serviceData) {
            Service::query()->updateOrCreate(
                ['title' => $serviceData['title']],
                [
                    ...$serviceData,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }

        $galleryItems = [
            ['caption' => 'Бутомо Мария Игоревна', 'alt' => 'Заведующая отделением, к.м.н. Бутомо Мария Игоревна'],
            ['caption' => 'Нифонтов Сергей Евгеньевич', 'alt' => 'Врач УЗИ, Нифонтов Сергей Евгеньевич'],
            ['caption' => 'Шихалиев Джошгун Рагимович', 'alt' => 'Врач УЗИ ФД, Шихалиев Джошгун Рагимович'],
            ['caption' => 'Лозовая Татьяна Александровна', 'alt' => 'Врач УЗИ, к.м.н. Лозовая Татьяна Александровна'],
            ['caption' => 'Оксас Арвид Евгеньевич', 'alt' => 'Врач УЗИ ФД, Оксас Арвид Евгеньевич'],
            ['caption' => 'Козленок Наталья Владимировна', 'alt' => 'Врач УЗИ, Козленок Наталья Владимировна'],
            ['caption' => 'Акименко Алла Федоровна', 'alt' => 'Врач ФД, Акименко Алла Федоровна'],
            ['caption' => 'Оболенцева Екатерина Юрьевна', 'alt' => 'Врач ФД, Оболенцева Екатерина Юрьевна'],
            ['caption' => 'Скуридин Даниил Сергеевич', 'alt' => 'Врач УЗИ ФД, Скуридин Даниил Сергеевич'],
            ['caption' => 'Попова Елена Александровна', 'alt' => 'Старшая медсестра, Попова Елена Александровна'],
            ['caption' => 'Медведева Ольга Витальевна', 'alt' => 'Медсестра, Медведева Ольга Витальевна'],
            ['caption' => 'Косогова Александра Анатольевна', 'alt' => 'Медсестра, Косогова Александра Анатольевна'],
            ['caption' => 'Прокопенкова Наталья Андреевна', 'alt' => 'Медсестра, Прокопенкова Наталья Андреевна'],
            ['caption' => 'Калецкая Светлана Яковлевна', 'alt' => 'Сестра-хозяйка, Калецкая Светлана Яковлевна'],
        ];

        foreach ($galleryItems as $index => $galleryItemData) {
            GalleryItem::query()->updateOrCreate(
                ['caption' => $galleryItemData['caption']],
                [
                    ...$galleryItemData,
                    'image' => null,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }

        $publishedAt = CarbonImmutable::now()->subDay();

        $reviews = [
            [
                'author_name' => 'Анна Петрова',
                'rating' => 5,
                'text' => 'Профессиональный подход, внимательное отношение к пациенту.',
                'doctor' => 'Акименко Алла Федоровна',
            ],
            [
                'author_name' => 'Ольга Ивановна',
                'rating' => 5,
                'text' => 'Отличный результат очень быстро и качественно.',
                'doctor' => 'Оксас Арвид Евгеньевич',
            ],
            [
                'author_name' => 'Сергей Иванович',
                'rating' => 5,
                'text' => 'Лучший специалист, который помог мне, спасибо!',
                'doctor' => 'Нифонтов Сергей Евгеньевич',
            ],
            [
                'author_name' => 'Екатерина',
                'rating' => 5,
                'text' => 'Внимательная, всё объяснила и успокоила. Благодарю!',
                'doctor' => 'Оболенцева Екатерина Юрьевна',
            ],
            [
                'author_name' => 'Ольга',
                'rating' => 5,
                'text' => 'Точная диагностика и эффективное лечение. Спасибо!',
                'doctor' => 'Лозовая Татьяна Александровна',
            ],
            [
                'author_name' => 'Дмитрий',
                'rating' => 5,
                'text' => 'Всё чётко, без очереди, результат сразу. Рекомендую.',
                'doctor' => 'Шихалиев Джошгун Рагимович',
            ],
        ];

        foreach ($reviews as $reviewData) {
            $doctorName = $reviewData['doctor'];
            unset($reviewData['doctor']);

            Review::query()->updateOrCreate(
                [
                    'author_name' => $reviewData['author_name'],
                    'text' => $reviewData['text'],
                ],
                [
                    ...$reviewData,
                    'source' => 'manual',
                    'status' => 'published',
                    'published_at' => $publishedAt,
                    'doctor_id' => $doctorIdsByName[$doctorName] ?? null,
                    'author_contacts' => null,
                ]
            );
        }
    }
}
