<?php

use App\Models\User;

$user = User::factory()->create(['password' => bcrypt('password')]);

$request = \Illuminate\Http\Request::create('/login', 'POST', [
    'email' => $user->email,
    'password' => 'password',
]);

$response = app()->handle($request);

return [
    'status' => $response->getStatusCode(),
    'redirect' => $response->headers->get('Location'),
    'content' => $response->getContent(),
];
