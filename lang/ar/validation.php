<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالحًا.',
    'url' => 'يجب أن يكون :attribute رابط HTTP أو HTTPS صالحًا.',
    'unique' => 'قيمة :attribute مستخدمة بالفعل.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'in' => 'القيمة المختارة لحقل :attribute غير صالحة.',
    'min' => [
        'string' => 'يجب ألا يقل :attribute عن :min حرفًا.',
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يزيد :attribute عن :max حرفًا.',
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
    ],
    'attributes' => [
        'name' => 'الاسم', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور', 'remember' => 'تذكرني',
        'url' => 'رابط الخدمة', 'interval_seconds' => 'فاصل الفحص', 'search' => 'البحث',
        'status' => 'الحالة', 'locale' => 'اللغة', 'redirect' => 'صفحة العودة',
    ],
];
