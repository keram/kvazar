<?php



/**
 * Users authenticator.
 */
class Users extends NObject implements IAuthenticator
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
			throw new NAuthenticationException("User not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $credentials[self::PASSWORD]) {
			throw new NAuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		unset($row->password);
		return new NIdentity($row->nick, NULL, $row);
	}

}
