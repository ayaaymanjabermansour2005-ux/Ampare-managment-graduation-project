<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\ChangePasswordAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutOtherDevicesAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\DTOs\Auth\LoginCredentials;
use App\DTOs\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\DeleteOwnAccountRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\SessionService;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @group  المصادقة وإدارة الحساب
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->execute(
            RegisterUserData::fromArray($request->validated())
        );

        return $this->success(
            message: 'تم إنشاء الحساب بنجاح.',
            data: new UserResource($user),
            code: 201
        );
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $user = $action->execute(
            LoginCredentials::fromArray($request->validated())
        );

        return $this->success(
            message: 'تم تسجيل الدخول بنجاح.',
            data: new UserResource($user)
        );
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute();

        return $this->success(message: 'تم تسجيل الخروج بنجاح.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            message: 'بيانات المستخدم الحالي.',
            data: new UserResource($request->user())
        );
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->user(),
            $request->validated('password')
        );

        return $this->success(
            message: $result->supported
                ? 'تم تغيير كلمة المرور بنجاح، وتم تسجيل الخروج من كل الأجهزة الأخرى.'
                : 'تم تغيير كلمة المرور بنجاح، لكن تعذّر إبطال الجلسات على الأجهزة الأخرى تلقائيًا — يُنصح بالتواصل مع الدعم الفني إذا كنت تشك بوصول غير مصرَّح به لحسابك.',
            data: ['other_sessions_invalidated' => $result->supported]
        );
    }

    public function logoutOtherDevices(Request $request, LogoutOtherDevicesAction $action): JsonResponse
    {
        $result = $action->execute($request->user());

        if (! $result->supported) {
            return $this->success(
                message: 'تعذّر تسجيل الخروج من الأجهزة الأخرى تلقائيًا بهذا الإعداد — يرجى التواصل مع الدعم الفني.',
                data: ['other_sessions_invalidated' => false]
            );
        }

        return $this->success(
            message: "تم تسجيل الخروج من {$result->deletedCount} جلسة أخرى.",
            data: ['other_sessions_invalidated' => true]
        );
    }

    public function sessions(Request $request, SessionService $sessionService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        // FIX (تدقيق شامل — E5): كانت تعيد data:[] فقط عند عدم الدعم، فيبدو
        // للأدمن وكأنه "لا جلسات أخرى" بدل "الميزة غير مدعومة بهذه البيئة".
        if (! $sessionService->isSupported()) {
            return $this->success(message: 'جلساتك النشطة.', data: ['sessions' => [], 'is_supported' => false]);
        }

        return $this->success(
            message: 'جلساتك النشطة.',
            data: ['sessions' => $sessionService->activeSessions($request, $user), 'is_supported' => true]
        );
    }

    public function revokeSession(Request $request, string $sessionId, SessionService $sessionService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $sessionService->isSupported()) {
            return $this->error(message: 'هذه الميزة غير مدعومة حاليًا بإعداد الجلسات.', code: 422);
        }

        if (! $sessionService->revokeSession($user, $sessionId)) {
            return $this->error(message: 'الجلسة غير موجودة.', code: 404);
        }

        return $this->success(message: 'تم إنهاء الجلسة بنجاح.');
    }

    public function loginLog(Request $request, SessionService $sessionService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $this->success(
            message: 'سجل دخولك.',
            data: $sessionService->loginLogFor($user)
        );
    }

    public function deleteAccount(DeleteOwnAccountRequest $request, UserService $userService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return $this->error(
                message: 'لا يمكن للأدمن حذف حسابه ذاتيًا — تواصل مع أدمن آخر لهذا الإجراء.',
                code: 403
            );
        }

        $userService->delete($user);

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success(message: 'تم حذف حسابك بنجاح.');
    }
}
