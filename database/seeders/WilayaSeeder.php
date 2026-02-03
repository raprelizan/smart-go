<?php

namespace Database\Seeders;

use App\Models\Wilaya;
use Illuminate\Database\Seeder;

class WilayaSeeder extends Seeder
{
    public function run(): void
    {
        $wilayas = [
            [1, 'أدرار', 'Adrar'],
            [2, 'الشلف', 'Chlef'],
            [3, 'الأغواط', 'Laghouat'],
            [4, 'أم البواقي', 'Oum El Bouaghi'],
            [5, 'باتنة', 'Batna'],
            [6, 'بجاية', 'Bejaia'],
            [7, 'بسكرة', 'Biskra'],
            [8, 'بشار', 'Bechar'],
            [9, 'البليدة', 'Blida'],
            [10, 'البويرة', 'Bouira'],
            [11, 'تمنراست', 'Tamanrasset'],
            [12, 'تبسة', 'Tebessa'],
            [13, 'تلمسان', 'Tlemcen'],
            [14, 'تيارت', 'Tiaret'],
            [15, 'تيزي وزو', 'Tizi Ouzou'],
            [16, 'الجزائر', 'Algiers'],
            [17, 'الجلفة', 'Djelfa'],
            [18, 'جيجل', 'Jijel'],
            [19, 'سطيف', 'Setif'],
            [20, 'سعيدة', 'Saida'],
            [21, 'سكيكدة', 'Skikda'],
            [22, 'سيدي بلعباس', 'Sidi Bel Abbes'],
            [23, 'عنابة', 'Annaba'],
            [24, 'قالمة', 'Guelma'],
            [25, 'قسنطينة', 'Constantine'],
            [26, 'المدية', 'Medea'],
            [27, 'مستغانم', 'Mostaganem'],
            [28, 'المسيلة', 'M'Sila'],
            [29, 'معسكر', 'Mascara'],
            [30, 'ورقلة', 'Ouargla'],
            [31, 'وهران', 'Oran'],
            [32, 'البيض', 'El Bayadh'],
            [33, 'إليزي', 'Illizi'],
            [34, 'برج بوعريريج', 'Bordj Bou Arreridj'],
            [35, 'بومرداس', 'Boumerdes'],
            [36, 'الطارف', 'El Tarf'],
            [37, 'تندوف', 'Tindouf'],
            [38, 'تيسمسيلت', 'Tissemsilt'],
            [39, 'الوادي', 'El Oued'],
            [40, 'خنشلة', 'Khenchela'],
            [41, 'سوق أهراس', 'Souk Ahras'],
            [42, 'تيبازة', 'Tipaza'],
            [43, 'ميلة', 'Mila'],
            [44, 'عين الدفلى', 'Ain Defla'],
            [45, 'النعامة', 'Naama'],
            [46, 'عين تموشنت', 'Ain Temouchent'],
            [47, 'غرداية', 'Ghardaia'],
            [48, 'غليزان', 'Relizane'],
            [49, 'تيميمون', 'Timimoun'],
            [50, 'برج باجي مختار', 'Bordj Badji Mokhtar'],
            [51, 'أولاد جلال', 'Ouled Djellal'],
            [52, 'بني عباس', 'Beni Abbes'],
            [53, 'إن صالح', 'In Salah'],
            [54, 'إن قزام', 'In Guezzam'],
            [55, 'تقرت', 'Touggourt'],
            [56, 'جانت', 'Djanet'],
            [57, 'المغير', 'El Mghair'],
            [58, 'المنيعة', 'El Menia']
        ];

        foreach ($wilayas as [$code, $nameAr, $nameEn]) {
            Wilaya::updateOrCreate(
                ['code' => $code],
                ['name_ar' => $nameAr, 'name_en' => $nameEn]
            );
        }
    }
}
