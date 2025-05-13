<?php
include 'stores/credentials.php';
include 'stores/profile.php';

class AuthService
{
	public static function login(string $username, string $password): void
	{
		$_SESSION['username'] = $username;
		$_SESSION['authorized_at'] = time();

		global $credentials;

		$credentials = [
			'username' => $username,
			'password' => $password,
		];

		header("Location: " . "/");
	}

	public static function logout(): void
	{
		global $credentials;
		global $profile;

		unset($_SESSION['username']);
		unset($_SESSION['authorized_at']);
		unset($credentials);
		unset($profile);

		header("Location: " . "/");
	}
}
