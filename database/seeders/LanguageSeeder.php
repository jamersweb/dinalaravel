<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Top widely-used locales for the mobile picker + bulk translate targets.
     * Run: php artisan db:seed --class=LanguageSeeder
     */
    public function run(): void
    {
        $rows = [
            ['code' => 'en', 'label' => 'English', 'native_label' => 'English', 'is_active' => true, 'sort_order' => 1],
            ['code' => 'ar', 'label' => 'Arabic', 'native_label' => 'العربية', 'is_active' => true, 'sort_order' => 2],
            ['code' => 'es', 'label' => 'Spanish', 'native_label' => 'Español', 'is_active' => true, 'sort_order' => 3],
            ['code' => 'fr', 'label' => 'French', 'native_label' => 'Français', 'is_active' => true, 'sort_order' => 4],
            ['code' => 'de', 'label' => 'German', 'native_label' => 'Deutsch', 'is_active' => true, 'sort_order' => 5],
            ['code' => 'pt', 'label' => 'Portuguese', 'native_label' => 'Português', 'is_active' => true, 'sort_order' => 6],
            ['code' => 'ru', 'label' => 'Russian', 'native_label' => 'Русский', 'is_active' => true, 'sort_order' => 7],
            ['code' => 'zh', 'label' => 'Chinese', 'native_label' => '中文', 'is_active' => true, 'sort_order' => 8],
            ['code' => 'ja', 'label' => 'Japanese', 'native_label' => '日本語', 'is_active' => true, 'sort_order' => 9],
            ['code' => 'ko', 'label' => 'Korean', 'native_label' => '한국어', 'is_active' => true, 'sort_order' => 10],
            ['code' => 'hi', 'label' => 'Hindi', 'native_label' => 'हिन्दी', 'is_active' => true, 'sort_order' => 11],
            ['code' => 'it', 'label' => 'Italian', 'native_label' => 'Italiano', 'is_active' => true, 'sort_order' => 12],
            ['code' => 'tr', 'label' => 'Turkish', 'native_label' => 'Türkçe', 'is_active' => true, 'sort_order' => 13],
            ['code' => 'pl', 'label' => 'Polish', 'native_label' => 'Polski', 'is_active' => true, 'sort_order' => 14],
            ['code' => 'nl', 'label' => 'Dutch', 'native_label' => 'Nederlands', 'is_active' => true, 'sort_order' => 15],
            ['code' => 'sv', 'label' => 'Swedish', 'native_label' => 'Svenska', 'is_active' => true, 'sort_order' => 16],
            ['code' => 'da', 'label' => 'Danish', 'native_label' => 'Dansk', 'is_active' => true, 'sort_order' => 17],
            ['code' => 'fi', 'label' => 'Finnish', 'native_label' => 'Suomi', 'is_active' => true, 'sort_order' => 18],
            ['code' => 'no', 'label' => 'Norwegian', 'native_label' => 'Norsk', 'is_active' => true, 'sort_order' => 19],
            ['code' => 'uk', 'label' => 'Ukrainian', 'native_label' => 'Українська', 'is_active' => true, 'sort_order' => 20],
            ['code' => 'vi', 'label' => 'Vietnamese', 'native_label' => 'Tiếng Việt', 'is_active' => true, 'sort_order' => 21],
            ['code' => 'id', 'label' => 'Indonesian', 'native_label' => 'Bahasa Indonesia', 'is_active' => true, 'sort_order' => 22],
            ['code' => 'th', 'label' => 'Thai', 'native_label' => 'ไทย', 'is_active' => true, 'sort_order' => 23],
            ['code' => 'cs', 'label' => 'Czech', 'native_label' => 'Čeština', 'is_active' => true, 'sort_order' => 24],
            ['code' => 'he', 'label' => 'Hebrew', 'native_label' => 'עברית', 'is_active' => true, 'sort_order' => 25],
        ];

        foreach ($rows as $row) {
            Language::query()->updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
