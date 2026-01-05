<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Feedback;
use App\Models\Notification;

class DummyDataSeeder extends Seeder
{
    // Use Indonesian locale
    protected $faker;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('id_ID');

        // $this->clearExistingData(); // DISABLED to preserve data
        
        // 1. Seed Users (Admins & Participants)
        $admins = $this->seedAdmins();
        $participants = $this->seedParticipants();
        
        // 2. Seed Categories
        $categories = $this->seedCategories();
        
        // 3. Seed Events (Real Data)
        $events = $this->seedRealEvents($admins, $categories);
        
        // 4. Seed Random Events (to fill up)
        $this->seedRandomEvents($admins, $categories);
        $allEvents = Event::all();

        // 5. Seed Event Participants (Registrations)
        $this->seedEventParticipants($allEvents, $participants);
        
        // 6. Seed Feedbacks
        $this->seedFeedbacks($allEvents, $participants);
        
        // 7. Seed Notifications
        $this->seedNotifications($participants, $allEvents);

        $this->command->info('✅ Dummy data seeded successfully (Additive Mode)!');
    }

    private function seedAdmins()
    {
        $this->command->info('👥 Seeding admins (Checking existence)...');

        $admins = [
            [
                'name' => 'Admin TUP',
                'full_name' => 'Administrator IT Telkom',
                'email' => 'admin@ittelkom-pwt.ac.id',
                'role' => 'admin',
                'bio' => 'Administrator Sistem Informasi IT Telkom Purwokerto',
                'phone' => '081234567890',
            ],
            [
                'name' => 'BEM Kema',
                'full_name' => 'BEM Kema Telkom University',
                'email' => 'bem@ittelkom-pwt.ac.id',
                'role' => 'admin',
                'bio' => 'Badan Eksekutif Mahasiswa Keluarga Mahasiswa',
                'phone' => '081298765432',
            ],
            [
                'name' => 'Hima IF',
                'full_name' => 'Himpunan Mahasiswa Informatika',
                'email' => 'himaif@ittelkom-pwt.ac.id',
                'role' => 'admin',
                'bio' => 'Himpunan Mahasiswa S1 Informatika',
                'phone' => '081345678901',
            ],
            [
                'name' => 'Pak Budi',
                'full_name' => 'Budi Santoso, M.Kom',
                'email' => 'budi@dosen.ittelkom-pwt.ac.id',
                'role' => 'admin',
                'bio' => 'Dosen Pembina Kemahasiswaan',
                'phone' => '081456789012',
            ],
        ];

        $createdAdmins = collect();

        foreach ($admins as $adminData) {
            $admin = User::firstOrCreate(
                ['email' => $adminData['email']],
                array_merge($adminData, [
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ])
            );
            $createdAdmins->push($admin);
        }

        $this->command->info('✅ ' . $createdAdmins->count() . ' admins seeded (or already existed)');
        return $createdAdmins;
    }

    private function seedParticipants()
    {
        $this->command->info('👥 Seeding participants...');
        
        $participants = collect();
        
        // Known participant
        $participants->push(User::firstOrCreate(
            ['email' => 'budi@mhs.ittelkom-pwt.ac.id'],
            [
                'name' => 'Mhs Budi',
                'full_name' => 'Budi Pratama',
                'role' => 'participant',
                'password' => Hash::make('password123'),
                'phone' => '081211112222',
                'email_verified_at' => now(),
            ]
        ));

        // Create NEW random participants (Batch of 20 to avoid overcrowding if run multiple times)
        for ($i = 0; $i < 20; $i++) {
            $participants->push(User::factory()->create([
                'name' => $this->faker->userName . rand(100, 999),
                'full_name' => $this->faker->name,
                'email' => $this->faker->unique()->userName . rand(100, 999) . '@example.com',
                'role' => 'participant',
                'password' => Hash::make('password123'),
                'phone' => '08' . $this->faker->numerify('##########'),
                'bio' => 'Mahasiswa aktif Telkom University Purwokerto',
            ]));
        }

        $this->command->info('✅ ' . $participants->count() . ' new participants seeded');
        // Return all existing participants to include them in event registration
        return User::where('role', 'participant')->get();
    }

    private function seedCategories()
    {
        $this->command->info('📂 Seeding categories (Checking existence)...');

        $categories = [
            ['Teknologi', 'Seminar dan workshop teknologi, coding, dan AI', '#3B82F6'],
            ['Bisnis', 'Kewirausahaan, startup, dan manajemen bisnis', '#10B981'],
            ['Pendidikan', 'Workshop akademik dan pengembangan soft skill', '#F59E0B'],
            ['Kesehatan', 'Kesehatan mental dan fisik mahasiswa', '#EF4444'],
            ['Seni & Budaya', 'Pentas seni, pameran, dan kebudayaan Banyumas', '#8B5CF6'],
            ['Olahraga', 'Turnamen futsal, basket, dan e-sport', '#06B6D4'],
            ['Kuliner', 'Festival jajanan banyumasan dan kewirausahaan makanan', '#84CC16'],
            ['Musik', 'Konser musik kampus dan festival band', '#F97316'],
            ['Lingkungan', 'Kegiatan pecinta alam dan kebersihan lingkungan', '#6B7280'],
        ];

        $createdCategories = collect();

        foreach ($categories as $cat) {
            $createdCategories->push(Category::firstOrCreate(
                ['name' => $cat[0]],
                [
                    'description' => $cat[1],
                    'color' => $cat[2],
                    'is_active' => true,
                ]
            ));
        }

        $this->command->info('✅ ' . $createdCategories->count() . ' categories seeded (or already existed)');
        return $createdCategories;
    }

    private function seedRealEvents($admins, $categories)
    {
        $this->command->info('🎉 Seeding REAL events (Checking existence)...');

        $events = collect();
        
        $locations = [
            'Telkom University Purwokerto, Gedung IoT',
            'Auditorium Telkom University Purwokerto',
            'Alun-alun Purwokerto',
            'GOR Satria Purwokerto',
            'Hotel Java Heritage Purwokerto',
            'Coworking Space Hetero Space Banyumas',
            'Kampus 2 Telkom University Purwokerto'
        ];

        $realEvents = [
            [
                'title' => 'Seminar Nasional: Masa Depan AI di Indonesia',
                'desc' => 'Membahas perkembangan Artificial Intelligence dan peluang karir bagi mahasiswa informatika.',
                'cat' => 'Teknologi',
                'loc' => 'Auditorium Telkom University Purwokerto',
                'price' => 50000,
                'quota' => 300,
                'status' => 'published',
                'date_mod' => '+1 week'
            ],
            [
                'title' => 'Workshop Laravel 11 & React JS',
                'desc' => 'Pelatihan hands-on membangun aplikasi fullstack modern dengan Laravel dan React.',
                'cat' => 'Teknologi',
                'loc' => 'Lab Komputer Gedung IoT',
                'price' => 75000,
                'quota' => 50,
                'status' => 'published',
                'date_mod' => '+2 weeks'
            ],
            [
                'title' => 'Banyumas Startup Festival 2026',
                'desc' => 'Pameran startup lokal Banyumas dan sesi pitching dengan investor.',
                'cat' => 'Bisnis',
                'loc' => 'Hotel Java Heritage Purwokerto',
                'price' => 100000,
                'quota' => 500,
                'status' => 'published',
                'date_mod' => '+1 month'
            ],
            [
                'title' => 'E-Sport Mobile Legends Championship',
                'desc' => 'Turnamen Mobile Legends antar prodi se-Telkom University Purwokerto.',
                'cat' => 'Olahraga',
                'loc' => 'Aula Kawasan Pendidikan Telkom',
                'price' => 25000,
                'quota' => 64, // Tim
                'status' => 'published',
                'date_mod' => '+3 days'
            ],
            [
                'title' => 'Festival Budaya Banyumasan: Ebeg & Lengger',
                'desc' => 'Pagelaran seni tradisional khas Banyumas untuk melestarikan budaya lokal.',
                'cat' => 'Seni & Budaya',
                'loc' => 'Alun-alun Purwokerto',
                'price' => 0,
                'quota' => 1000,
                'status' => 'published',
                'date_mod' => '+2 months'
            ],
            [
                'title' => 'Seminar Kesehatan Mental Mahasiswa',
                'desc' => 'Pentingnya menjaga kesehatan mental di tengah tekanan akademik.',
                'cat' => 'Kesehatan',
                'loc' => 'Kampus 2 Telkom University Purwokerto',
                'price' => 15000,
                'quota' => 100,
                'status' => 'completed',
                'date_mod' => '-1 week'
            ],
            [
                'title' => 'Workshop Digital Marketing UMKM Banyumas',
                'desc' => 'Membantu UMKM lokal Go Digital dengan strategi pemasaran online.',
                'cat' => 'Bisnis',
                'loc' => 'Coworking Space Hetero Space Banyumas',
                'price' => 50000,
                'quota' => 30,
                'status' => 'completed',
                'date_mod' => '-2 weeks'
            ],
            [
                'title' => 'Pelatihan UI/UX Design Fundamental',
                'desc' => 'Belajar dasar desain antarmuka pengguna menggunakan Figma.',
                'cat' => 'Teknologi',
                'loc' => 'Lab Multimedia',
                'price' => 35000,
                'quota' => 40,
                'status' => 'draft',
                'date_mod' => '+1 month'
            ]
        ];

        foreach ($realEvents as $evt) {
            $category = $categories->firstWhere('name', $evt['cat']);
            $admin = $admins->random();
            
            // Check if event with same title exists to avoid duplication
            $existing = Event::where('title', $evt['title'])->first();
            if ($existing) {
                $events->push($existing);
                continue;
            }

            $startDate = date('Y-m-d H:i:s', strtotime($evt['date_mod']));
            $endDate = date('Y-m-d H:i:s', strtotime($evt['date_mod'] . ' + 4 hours'));

            $event = Event::factory()->create([
                'title' => $evt['title'],
                'description' => $evt['desc'],
                'location' => $evt['loc'],
                'category_id' => $category->id,
                'user_id' => $admin->id,
                'price' => $evt['price'],
                'is_paid' => $evt['price'] > 0,
                'quota' => $evt['quota'],
                'status' => $evt['status'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                // Image can be handled by factory default or set null
            ]);
            $events->push($event);
        }

        $this->command->info('✅ ' . $events->count() . ' REAL events seeded (or already existed)');
        return $events;
    }

    private function seedRandomEvents($admins, $categories)
    {
        $locations = [
            'Telkom University Purwokerto',
            'Purwokerto City Center',
            'Baturraden',
            'GOR Satria',
            'Universitas Jenderal Soedirman',
            'Taman Kota Andhang Pangrenan'
        ];

        // Ensure we don't spam too many events if there are already many
        if (Event::count() > 100) {
            $this->command->info('⚠️ Skipping random events seeding, too many events already exist.');
            return;
        }

        Event::factory()->count(10)->published()->make()->each(function ($event) use ($admins, $categories, $locations) {
            $event->user_id = $admins->random()->id;
            $event->category_id = $categories->random()->id;
            $event->location = $this->faker->randomElement($locations);
            $event->title = $this->faker->sentence(3) . ' (Di Purwokerto)';
            $event->save();
        });
        $this->command->info('✅ 10 random events seeded.');
    }

    private function seedEventParticipants($events, $participants)
    {
        $this->command->info('👥 Seeding registrations...');
        
        $count = 0;
        
        foreach ($events as $event) {
            if ($event->status === 'draft') continue;

            // Pick 3-10 participants, filtering out those already registered
            $registeredUserIds = EventParticipant::where('event_id', $event->id)->pluck('user_id')->toArray();
            $availableParticipants = $participants->whereNotIn('id', $registeredUserIds);

            if ($availableParticipants->isEmpty()) continue;

            $newParticipants = $availableParticipants->random(min($availableParticipants->count(), rand(3, 10)));
            
            foreach ($newParticipants as $user) {
                // Logic registrasi based on status, ensuring NO DUPLICATES handled by availableParticipants check
                $status = $this->faker->randomElement(['registered', 'attended', 'cancelled']);
                if ($event->status === 'completed' && $status === 'registered') {
                    $status = 'attended';
                }

                $factory = EventParticipant::factory()
                    ->state([
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                    ]);

                 if ($status === 'attended') {
                    $factory = $factory->attended();
                    if ($event->is_paid) {
                        $factory = $factory->paid($event->price);
                    }
                } elseif ($status === 'cancelled') {
                    $factory = $factory->cancelled();
                } else {
                     if ($event->is_paid && $this->faker->boolean(70)) {
                         $factory = $factory->paid($event->price);
                     }
                }
                
                $factory->create();
                $event->increment('registered_count');
                $count++;
            }
        }

        $this->command->info('✅ ' . $count . ' new registrations seeded');
    }

    private function seedFeedbacks($events, $participants)
    {
        $this->command->info('💬 Seeding feedbacks...');
        
        $count = 0;
        $completedEvents = $events->where('status', 'completed');

        // Context-aware comments
        $comments = [
            'Teknologi' => [
                'Materi coding-nya sangat insightful, speaker juga expert!',
                'Demo aplikasinya keren, membuka wawasan baru soal AI.',
                'Sesi live coding agak terlalu cepat, tapi overall oke.',
                'Teknologi yang dibahas sangat relevan dengan industri saat ini.',
                'Next time mungkin bisa lebih banyak sesi hands-on programming.',
                'Sangat bermanfaat buat nambah portofolio developer.',
            ],
            'Bisnis' => [
                'Tips membangun startup-nya sangat praktis dan bisa langsung diterapkan.',
                'Networking session-nya bagus banget, ketemu banyak founder lokal.',
                'Analisis pasar yang dipaparkan sangat tajam.',
                'Materinya daging semua, cocok buat yang mau mulai usaha.',
                'Sangat inspiratif mendengar cerita jatuh bangun para pengusaha Banyumas.',
            ],
            'Seni & Budaya' => [
                'Pertunjukan ebeg-nya magis banget, merinding!',
                'Salut buat panitia yang terus melestarikan budaya Banyumasan.',
                'Tata panggung dan lighting sangat mendukung suasana.',
                'Keren, senimannya sangat totalitas dalam berkarya.',
                'Semoga makin banyak event budaya seperti ini di Purwokerto.',
            ],
            'Olahraga' => [
                'Turnamennya kompetitif dan fair play terjaga.',
                'Venue GOR Satria memang paling pas buat event ginian.',
                'Jadwal pertandingan on time, wasit juga tegas.',
                'Seru banget pertandingannya, suporter juga tertib.',
                'Hadiahnya lumayan, persaingan jadi makin ketat.',
            ],
            'Kesehatan' => [
                'Penjelasannya menenangkan, jadi lebih aware soal mental health.',
                'Sangat edukatif, banyak mitos kesehatan yang diluruskan di sini.',
                'Pembicaranya dokter spesialis yang sangat komunikatif.',
                'Materi diet dan nutrisinya mudah dipahami orang awam.',
                'Terima kasih tips kesehatan mentalnya, sangat relate dengan mahasiswa.',
            ],
            'Pendidikan' => [
                'Metode belajarnya asik, ga bikin ngantuk.',
                'Sangat memotivasi untuk lanjut studi S2.',
                'Tips beasiswanya sangat membantu, detail banget.',
                'Diskusi akademiknya hidup, banyak perspektif baru.',
                'Worksheet yang dikasih sangat membantu evaluasi diri.',
            ],
            'Kuliner' => [
                'Makanannya enak-enak, banyak jajanan unik.',
                'Demonstrasi masaknya seru, chef-nya lucu.',
                'Sampel makanannya kurang banyak hehe, tapi enak!',
                'Tenant yang hadir variatif, puas jajan di sini.',
                'Tips bisnis kulinernya sangat berharga.',
            ],
            'Musik' => [
                'Sound system nendang banget, pecahh!',
                'Guest star-nya gokil, crowd control-nya jago.',
                'Lineup band lokalnya juga gak kalah keren.',
                'Visual stage-nya estetik parah.',
                'Crowd-nya asik, sing along terus dari awal sampe akhir.',
            ],
            'Lingkungan' => [
                'Aksi bersih-bersihnya seru, capek tapi puas.',
                'Jadi lebih paham soal pilah sampah dan daur ulang.',
                'Semoga impact-nya berkelanjutan buat Banyumas.',
                'Edukasi lingkungannya dikemas dengan fun.',
                'Gerakan positif yang harus didukung terus.',
            ],
            'Default' => [
                'Acara yang sangat bagus dan well-organized.',
                'Panitia ramah dan sangat membantu peserta.',
                'Fasilitas venue cukup memadai dan nyaman.',
                'Semoga tahun depan diadakan lagi dengan skala lebih besar.',
                'Terima kasih atas ilmunya, sangat bermanfaat.',
            ]
        ];

        foreach ($completedEvents as $event) {
            // Get attendees
            $attendees = EventParticipant::where('event_id', $event->id)
                ->where('status', 'attended')
                ->get();
            
            if ($attendees->isEmpty()) continue;
            
            $categoryName = $event->category ? $event->category->name : 'Default';
            $categoryComments = $comments[$categoryName] ?? $comments['Default'];

            foreach ($attendees as $attendee) {
                // Check if user already gave feedback for this event
                if (Feedback::where('user_id', $attendee->user_id)->where('event_id', $event->id)->exists()) {
                    continue;
                }

                if ($this->faker->boolean(40)) { // Lower chance since we run it multiple times
                    Feedback::factory()->create([
                        'event_id' => $event->id,
                        'user_id' => $attendee->user_id,
                        'comment' => $this->faker->randomElement($categoryComments),
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info('✅ ' . $count . ' new feedbacks seeded');
    }

    private function seedNotifications($users, $events)
    {
        $this->command->info('🔔 Seeding notifications...');
        
        // Add just a few new notifications
        foreach ($users->random(min($users->count(), 20)) as $user) {
            // Ensure we don't create too many notifications for a single user/event combination
            if (Notification::where('user_id', $user->id)->count() < 5) { // Limit to 5 notifications per user
                Notification::factory()->count(1)->create([
                    'user_id' => $user->id,
                    'event_id' => $events->random()->id ?? 1,
                ]);
                $this->command->info('🔔 Notification created for user ' . $user->id);
            }
        }
    }
}