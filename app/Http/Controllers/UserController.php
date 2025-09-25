<?php

namespace App\Http\Controllers;


use Moggie\Http\JsonResponse;
use Moggie\Http\Request;

/**
 * Class UserController
 *
 * @package \App\Http\Controllers
 */
class UserController extends Controller
{
    protected array $users = [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com']
    ];

    public function index(Request $request): JsonResponse
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 10);
        $search = $request->query('search');

        $users = $this->users;

        // Simple search functionality
        if ($search) {
            $users = array_filter($users, function ($user) use ($search) {
                return stripos($user['name'], $search) !== false
                    || stripos($user['email'], $search) !== false;
            });
        }

        // Simple pagination
        $total = count($users);
        $offset = ($page - 1) * $perPage;
        $users = array_slice($users, $offset, $perPage);

        return response()->json()->pagination(
            $users,
            $total,
            $page,
            $perPage,
            $request->fullUrl()
        );
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        $data = $validator->validate();

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        // Generate new ID
        $data['id'] = max(array_column($this->users, 'id')) + 1;

        // Remove password from response
        unset($data['password']);

        // In a real app, you would save to database
        // $user = User::create($data);

        return response()->json()->created($data, 'User created successfully');
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->findUser($id);

        if (!$user) {
            return response()->json()->notFound('User not found');
        }

        return response()->json()->resource($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->findUser($id);

        if (!$user) {
            return response()->json()->notFound('User not found');
        }

        $validator = validator($request->all(), [
            'name' => 'string|min:2|max:50',
            'email' => 'email',
            'password' => 'string|min:8'
        ]);

        $data = $validator->validate();

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        // Update user data
        $updatedUser = array_merge($user, $data);

        // Remove password from response
        unset($updatedUser['password']);

        // In a real app, you would update in database
        // User::where('id', $id)->update($data);

        return response()->json()->updated($updatedUser, 'User updated successfully');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->findUser($id);

        if (!$user) {
            return response()->json()->notFound('User not found');
        }

        // In a real app, you would delete from database
        // User::destroy($id);

        return response()->json()->deleted('User deleted successfully');
    }

    /**
     * Find user by ID.
     */
    protected function findUser(int $id): ?array
    {
        foreach ($this->users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }

        return null;
    }
}
