<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Предоставляет административные операции с проверкой прав на сервере. */
class AdminController extends Controller
{
    /** Ищет пользователей и сортирует исключительно по белому списку колонок. */
    public function index(Request $request): mixed
    {
        abort_unless($request->user()->is_admin, 403);
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|in:name,email,created_at',
            'direction' => 'nullable|in:asc,desc',
            'page' => 'nullable|integer|min:1',
        ]);
        // Именованные значения по умолчанию гарантируют объект JSON даже без фильтров.
        $filters = [
            'search' => $validated['search'] ?? '',
            'sort' => $validated['sort'] ?? 'created_at',
            'direction' => $validated['direction'] ?? 'desc',
        ];
        $query = User::query();
        if ($search = $filters['search']) {
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('last_name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%'));
        }
        $users = $query->orderBy($filters['sort'], $filters['direction'])
            ->orderBy('id')->paginate(10)->withQueryString()
            ->through(fn (User $user) => $user->only([
                'id', 'name', 'last_name', 'email', 'is_admin', 'is_blocked', 'created_at',
            ]));
        $actions = DB::table('admin_actions')
            ->leftJoin('users', 'users.id', '=', 'admin_actions.actor_id')
            ->select('admin_actions.*', 'users.name as actor_name')
            ->orderByDesc('admin_actions.id')->limit(50)->get()
            ->map(function (object $action): object {
                // ISO 8601 сохраняет часовой пояс при передаче даты браузеру.
                $action->created_at = Carbon::parse($action->created_at, 'UTC')->toIso8601String();

                return $action;
            });

        return Inertia::render('Admin', [
            'users' => $users,
            'filters' => $filters,
            'actions' => $actions,
            'totals' => ['users' => User::count(), 'blocked' => User::where('is_blocked', true)->count()],
        ]);
    }

    /** Атомарно меняет блокировку и записывает действие; администраторов блокировать нельзя. */
    public function toggle(Request $request, string $user): mixed
    {
        abort_unless($request->user()->is_admin, 403);
        // Поиск пользователя выполняется после проверки роли, чтобы не раскрывать наличие чужого id.
        abort_unless(filter_var($user, FILTER_VALIDATE_INT) !== false && (int) $user > 0, 404);
        DB::transaction(function () use ($request, $user) {
            $target = User::whereKey($user)->lockForUpdate()->firstOrFail();
            abort_if($target->is_admin, 403, 'Нельзя блокировать администратора.');
            $target->is_blocked = ! $target->is_blocked;
            $target->save();
            DB::table('admin_actions')->insert([
                'actor_id' => $request->user()->id,
                'subject_id' => $target->id,
                'action' => $target->is_blocked ? 'blocked' : 'unblocked',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Статус пользователя изменён.');
    }
}
