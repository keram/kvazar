<?php



/**
 * Users authenticator.
 */
class Users extends Object implements IAuthenticator
{

	/**
	 * Performs an authentication
	 * @param  array
	 * @return void
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		$email 	  = addslashes(strtolower($credentials["extra"]["email"]));
		$password = sha1($credentials[self::PASSWORD]);

		$row = dibi::select('*')->from('user')->where('email=%s', $email)->fetch();

		if (!$row) {
			throw new AuthenticationException("User not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $credentials[self::PASSWORD]) {
			throw new AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		unset($row->password);
		return new Identity($row->nick, NULL, $row);
	}

}
