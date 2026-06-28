<?php

return [
    'navigation_group' => 'الإعدادات',
    'navigation_label' => 'واتساب',
    'page_title' => 'إعدادات جسر واتساب',
    'page_heading' => 'إعدادات جسر واتساب',

    'sections' => [
        'api_configuration' => 'إعدادات API',
        'message_settings' => 'إعدادات الرسائل',
        'message_templates' => 'قوالب الرسائل',
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
        'qr_expires_in' => 'ينتهي صلاحية رمز QR خلال :count ثانية',
        'qr_generating' => 'جاري إنشاء رمز QR...',
        'disconnect' => 'قطع الاتصال',
        'connected_title' => 'تم الاتصال بواتساب',
        'connected_phone' => 'رقم الهاتف المتصل: :phone',
        'disconnect_button' => 'قطع الاتصال',
        'test_section_title' => 'إرسال رسالة اختبار',
        'test_phone_label' => 'رقم الهاتف',
        'test_phone_placeholder' => '201000000000',
        'test_message_label' => 'الرسالة',
        'test_sending' => 'جاري الإرسال...',
        'test_send_button' => 'إرسال رسالة اختبار',
        'connect_button' => 'ربط واتساب',
        'description' => 'أنشئ رمز QR لربط حساب واتساب الخاص بك. افتح واتساب على هاتفك، اذهب إلى الإعدادات > الأجهزة المرتبطة > ربط جهاز.',
    ],
];
