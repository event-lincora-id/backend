<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'event_id' => \App\Models\Event::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->randomElement([
                'Acaranya sangat bermanfaat, materinya daging semua!',
                'Narasumber sangat kompeten dan penyampaiannya jelas.',
                'Tempatnya nyaman, panitia juga ramah-ramah. Top!',
                'Materi seminar sangat relevan dengan kebutuhan industri saat ini.',
                'Keren banget acaranya, semoga tahun depan ada lagi.',
                'Sedikit molor dari jadwal, tapi overall oke.',
                'Mohon untuk konsumsi lebih diperhatikan lagi ke depannya.',
                'Terima kasih ilmunya, sangat insightful.',
                'Workshopnya seru, langsung praktik jadi lebih paham.',
                'Sangat menginspirasi, sukses terus buat panitia!',
                'Sound system kadang kurang jelas di bagian belakang.',
                'Good job panitia, acaranya rapi dan terstrukur.',
                'Seru banget bisa networking sama teman-teman baru.',
                'Materinya agak terlalu dasar, mungkin next bisa lebih advanced.',
                'Fasilitas venue sangat mendukung jalannya acara.',
            ]),
            'verification_code' => $this->faker->regexify('[A-Z0-9]{10}'),
            'certificate_generated' => $this->faker->boolean(80),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }
}
