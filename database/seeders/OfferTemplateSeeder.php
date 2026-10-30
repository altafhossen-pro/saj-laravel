<?php

namespace Database\Seeders;

use App\Models\OfferTemplate;
use Illuminate\Database\Seeder;

class OfferTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'ফ্রি হোম ডেলিভারি অফার',
                'title' => 'অভিনন্দন! ফ্রি হোম ডেলিভারি আনলক হয়েছে 🚚',
                'message' => "প্রিয় গ্রাহক, আজকের জন্য আপনার অর্ডারে পাচ্ছেন সম্পূর্ণ ফ্রি হোম ডেলিভারি! এখনই অর্ডার কনফার্ম করে এই অফারটি উপভোগ করুন।",
                'coupon_code' => 'FREESHIP',
                'button_text' => 'ফ্রি ডেলিভারিতে অর্ডার করুন',
                'button_url' => '/checkout',
                'delay_seconds' => 10,
                'is_active' => true,
            ],
            [
                'name' => '১০% তাৎক্ষণিক স্পেশাল ছাড়',
                'title' => 'আপনার জন্য স্পেশাল ১০% সারপ্রাইজ ডিসকাউন্ট! 🎁',
                'message' => "আপনি আমাদের স্টোরে ভিজিট করায় আমরা আনন্দিত! এই মুহূর্তে অর্ডার করলে পাচ্ছেন ফ্ল্যাট ১০% ইনস্ট্যান্ট ক্যাশ ছাড়। অফারটি সীমিত সময়ের জন্য প্রযোজ্য।",
                'coupon_code' => 'SPECIAL10',
                'button_text' => '১০% ছাড়ে অর্ডার করুন',
                'button_url' => '/checkout',
                'delay_seconds' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'কার্ট রিমাইন্ডার ও আকর্ষণীয় গিফট',
                'title' => 'আপনার শপিং ব্যাগটি অপেক্ষা করছে! ✨',
                'message' => "আপনার পছন্দের পণ্যগুলো স্টক শেষ হওয়ার আগেই অর্ডার কনফার্ম করুন। আজকের প্রতিটি অর্ডারের সাথে পাচ্ছেন একটি আকর্ষণীয় গিফট!",
                'coupon_code' => 'GIFTPLUS',
                'button_text' => 'চেকআউটে গিয়ে অর্ডার করুন',
                'button_url' => '/checkout',
                'delay_seconds' => 12,
                'is_active' => true,
            ],
            [
                'name' => '১০০ টাকা ইনস্ট্যান্ট ক্যাশব্যাক ভাউচার',
                'title' => 'স্পেশাল ক্যাশ ভাউচার: ১০০ টাকা ছাড় 💵',
                'message' => "আপনার পছন্দের যেকোনো অর্ডারে পাচ্ছেন সরাসরি ১০০ টাকার নগদ ডিসকাউন্ট! কুপন কোড ব্যবহার করে চেকআউট সম্পন্ন করুন।",
                'coupon_code' => 'CASH100',
                'button_text' => '১০০ টাকা ডিসকাউন্ট নিন',
                'button_url' => '/checkout',
                'delay_seconds' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $t) {
            OfferTemplate::updateOrCreate(['name' => $t['name']], $t);
        }
    }
}

