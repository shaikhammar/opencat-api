<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * Create a new API token.
     *
     * @group Authentication
     * @bodyParam email string required User email. Example: user@example.com
     * @bodyParam password string required User password. Example: secret
     * @bodyParam name string required Token name/description. Example: my-script
     * @bodyParam abilities string[] Token ability scopes. Example: ["process","tm:read"]
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|string',
            'name'      => 'required|string|max:255',
            'abilities' => 'sometimes|array',
            'abilities.*' => 'string',
        ]);

        $user = \App\Models\User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $abilities = $data['abilities'] ?? ['*'];
        $token = $user->createToken($data['name'], $abilities);

        return response()->json([
            'data' => [
                'tokenId'   => $token->accessToken->id,
                'token'     => $token->plainTextToken,
                'abilities' => $abilities,
            ],
        ], 201);
    }

    /**
     * List all tokens for the authenticated user.
     *
     * @group Authentication
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->select(['id', 'name', 'abilities', 'last_used_at', 'created_at'])->get();

        return response()->json(['data' => $tokens]);
    }

    /**
     * Revoke a token.
     *
     * @group Authentication
     * @authenticated
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json(null, 204);
    }
}
