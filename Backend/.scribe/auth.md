# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

هذا الـ API يستخدم Laravel Sanctum بنمط SPA Authentication عبر الجلسة (Session/Cookie)، وليس Bearer Token. للمصادقة: (1) قم بزيارة `/sanctum/csrf-cookie` أولاً للحصول على CSRF cookie، (2) سجّل الدخول عبر `POST /auth/login`، (3) بعد ذلك سيقوم المتصفح تلقائيًا بإرفاق الجلسة (Cookie) مع كل طلب لاحق دون الحاجة لأي هيدر يدوي.
