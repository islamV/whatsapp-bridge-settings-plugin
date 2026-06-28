<?php

return [
    'navigation_group' => 'الإعدادات',
    'navigation_label' => 'واتساب',
    'page_title' => 'إعدادات واتساب',
    'page_heading' => 'إعدادات واتساب',

    'tabs' => [
        'general' => 'عام',
        'bridge' => 'جسر واتساب',
        'meta' => 'Meta API',
        'twilio' => 'Twilio',
        'status' => 'الحالة',
    ],

    'providers' => [
        'bridge' => [
            'label' => 'جسر واتساب',
            'description' => 'جسر واتساب ويب مُستضاف ذاتياً',
        ],
        'meta' => [
            'label' => 'Meta Cloud API',
            'description' => 'واجهة Meta الرسمية لواتساب',
        ],
        'twilio' => [
            'label' => 'Twilio واتساب',
            'description' => 'واجهة Twilio لرسائل واتساب',
        ],
    ],

    'general' => [
        'select_provider' => 'اختر المزود النشط',
        'settings' => 'الإعدادات العامة',
        'provider_hint' => 'اختر المزود الذي سيعتمد عليه النظام في إرسال رموز التحقق والرسائل الصادرة.',
        'controls_title' => 'التحكم بالرسائل',
        'controls_description' => 'هذه الخيارات تؤثر على إرسال OTP والرسائل العادية على مستوى النظام.',
        'otp_enabled_help' => 'يتحكم في إرسال رموز التحقق.',
        'messages_enabled_help' => 'يتحكم في الرسائل الصادرة عبر واتساب.',
        'otp_template' => 'قالب رسالة OTP',
        'otp_template_placeholder' => 'رمز التحقق الخاص بك هو: {otp}',
        'otp_template_helper' => 'استخدم {otp} كمتغير لرمز التحقق.',
        'otp_enabled' => 'تفعيل رسائل OTP',
        'messages_enabled' => 'تفعيل الرسائل العادية',
        'save' => 'حفظ الإعدادات',
    ],

    'bridge' => [
        'title' => 'إعدادات جسر واتساب',
        'description' => 'تكوين اتصال جسر واتساب ويب المُستضاف ذاتياً',
        'card_title' => 'اتصال الجسر',
        'card_description' => 'أدخل رابط الخادم والرمز الخاص بمزود الجسر.',
        'api_base_url' => 'رابط API الأساسي',
        'api_token' => 'رمز API',
        'api_token_placeholder' => 'أدخل رمز API',
        'sender' => 'المرسل / معرف المثيل',
        'sender_placeholder' => 'معرف المرسل',
        'timeout' => 'المهلة (بالثواني)',
    ],

    'meta' => [
        'title' => 'واجهة Meta Cloud API',
        'description' => 'تكوين بيانات اعتماد Meta WhatsApp Business API',
        'card_title' => 'اتصال Meta Cloud',
        'card_description' => 'تكوين بيانات اعتماد واجهة Meta الرسمية.',
        'phone_number_id' => 'معرف رقم الهاتف',
        'phone_number_id_placeholder' => '1234567890',
        'access_token' => 'رمز الوصول',
        'access_token_placeholder' => 'أدخل رمز الوصول',
        'business_account_id' => 'معرف حساب الأعمال',
        'business_account_id_placeholder' => '1234567890',
        'verify_token' => 'رمز التحقق',
        'verify_token_placeholder' => 'أدخل رمز التحقق',
        'app_secret' => 'سر التطبيق',
        'app_secret_placeholder' => 'أدخل سر التطبيق',
        'timeout' => 'المهلة (بالثواني)',
    ],

    'twilio' => [
        'title' => 'إعدادات Twilio واتساب',
        'description' => 'تكوين بيانات اعتماد Twilio WhatsApp API',
        'card_title' => 'اتصال Twilio',
        'card_description' => 'تكوين بيانات اعتماد خدمة Twilio لواتساب.',
        'account_sid' => 'معرف الحساب (SID)',
        'account_sid_placeholder' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'auth_token' => 'رمز المصادقة',
        'auth_token_placeholder' => 'أدخل رمز المصادقة',
        'from_number' => 'رقم المرسل (واتساب)',
        'from_number_placeholder' => '+1234567890',
        'timeout' => 'المهلة (بالثواني)',
    ],

    'fields' => [
        'provider_name' => 'اسم المزود',
        'provider_name_placeholder' => 'افتراضي',
        'api_base_url' => 'رابط API الأساسي',
        'api_base_url_placeholder' => 'https://api.whatsapp.example.com',
        'api_token' => 'رمز API / رمز الوصول',
        'api_token_placeholder_keep' => 'اتركه فارغاً للاحتفاظ بالرمز الحالي',
        'api_token_placeholder_enter' => 'أدخل رمز API',
        'sender' => 'المرسل / معرف المثيل / معرف رقم الهاتف',
        'sender_placeholder' => 'معرف المرسل',
        'default_country_code' => 'رمز الدولة الافتراضي',
        'default_country_code_placeholder' => '20',
        'timeout' => 'المهلة (بالثواني)',
        'otp_enabled' => 'تفعيل رسائل OTP',
        'messages_enabled' => 'تفعيل الرسائل العادية',
        'otp_template' => 'قالب رسالة OTP',
        'otp_template_placeholder' => 'رمز التحقق الخاص بك هو: {otp}',
        'otp_template_helper' => 'استخدم {otp} كمتغير لرمز OTP.',
    ],

    'actions' => [
        'save' => 'حفظ الإعدادات',
        'send_test' => 'إرسال رسالة اختبار',
    ],

    'test_form' => [
        'phone' => 'رقم هاتف الاختبار',
        'phone_placeholder' => '201000000000',
        'message' => 'رسالة الاختبار',
        'message_placeholder' => 'مرحباً من جسر واتساب!',
    ],

    'notifications' => [
        'saved' => 'تم حفظ إعدادات واتساب بنجاح.',
        'test_sent' => 'تم إرسال رسالة الاختبار بنجاح!',
        'test_failed' => 'فشل إرسال رسالة الاختبار. تحقق من السجلات للمزيد من التفاصيل.',
    ],

    'qr' => [
        'qr_scan_title' => 'امسح رمز QR بتطبيق واتساب',
        'qr_image_alt' => 'رمز QR الخاص بواتساب',
        'qr_expires_in' => 'ينتهي صلاحية رمز QR خلال :count ثانية',
        'qr_generating' => 'جاري إنشاء رمز QR...',
        'disconnect' => 'قطع الاتصال',
        'connected_title' => 'تم الاتصال بواتساب',
        'connected_phone' => 'متصل بـ: :phone',
        'disconnect_button' => 'قطع الاتصال',
        'test_section_title' => 'إرسال رسالة اختبار',
        'test_phone_label' => 'رقم الهاتف',
        'test_phone_placeholder' => '201000000000',
        'test_message_label' => 'الرسالة',
        'test_sending' => 'جاري الإرسال...',
        'test_send_button' => 'إرسال رسالة اختبار',
        'connect_button' => 'ربط واتساب',
        'description' => 'أنشئ رمز QR لربط حساب واتساب الخاص بك.',
    ],
];
