<?php

	namespace Example;
	
	include_once __DIR__ . '/autoload.php';
	include_once __DIR__ . '/example-logic.php';
	include_once __DIR__ . '/ukey1-credentials.php';
	
	use Ukey1\App;
	use Ukey1\Endpoints\Authentication\Connect;
	use Ukey1\Endpoints\Authentication\AccessToken;
	use Ukey1\Endpoints\Authentication\RefreshToken;
	use Ukey1\Endpoints\Authentication\User;
	use Ukey1\Generators\RandomString;
	
	try {
		if ($action == ACTION_CONNECT) {
			// User clicked to "sign-in button"
			// Inits of connection with Ukey1 and redirects user to Ukey1 gateway
			
			$app = new App();
			$app->appId(APP_ID)
				->secretKey(SECRET_KEY);

			$requestId = RandomString::generate(16); // returns string with the length of 32 chars
			$returnUrl = getUrl(ACTION_GET_TOKEN);

			$module = new Connect($app);
			$module->setRequestId($requestId)
				->setReturnUrl($returnUrl)
				->setScope([
					"access_token",
					"refresh_token",
					"email",
					"image"
				]);

			$module->execute();
			$connectId = $module->getId();

			// Note that you need to store these values (at least temporarily)...
			
			saveSession("request_id", $requestId);
			saveSession("connect_id", $connectId);
			
			// Now you can redirect to gateway URL (yourself or via redirect() method)
			
			//$gatewayUrl = $module->getGatewayUrl();
			$module->redirect();
		}
		
		elseif ($action == ACTION_GET_TOKEN) {
			// Checks returned status and gets access token
			
			$app = new App();
			$app->appId(APP_ID)
				->secretKey(SECRET_KEY);
			
			$module = new AccessToken($app);
			$module->setRequestId(getSession("request_id"))
				->setConnectId(getSession("connect_id"));
			
			$check = $module->execute();
			
			if ($check) {
				// Now you can get your access token
				// If you want, you can store it in your database and use it later
				// Refresh token serves for refreshing your current access token (anytime)
				
				$accessToken = $module->getAccessToken(); // you can store the access token in your database (
				$accessTokenExpiration = $module->getAccessTokenExpiration();
				$refreshToken = $module->getRefreshToken();
				$grantedScope = $module->getScope();
				
				// This is only for this example...
				
				saveSession("access_token", $accessToken);
				saveSession("expiration", $accessTokenExpiration);
				saveSession("refresh_token", $refreshToken);
				saveSession("scope", implode(", ", $grantedScope));
				
				header("Location: " . getUrl(ACTION_GET_USER_DETAILS));
			}
			else {
				$action = null;
				$exception = "The request was canceled (by user) or expired.";
			}
		}
		
		elseif ($action == ACTION_REFRESH_TOKEN) {
			// Refreshes the access token
			
			if (getSession("refresh_token")) {
				$app = new App();
				$app->appId(APP_ID)
					->secretKey(SECRET_KEY);

				$module = new RefreshToken($app);
				$module->setRefreshToken(getSession("refresh_token"));

				$module->execute();

				// Now you can get your access token
				// If you want, you can store it in your database and use it later
				// Refresh token serves for refreshing your current access token (anytime)

				$accessToken = $module->getAccessToken(); // you can store the access token in your database (
				$accessTokenExpiration = $module->getAccessTokenExpiration();
				$refreshToken = $module->getRefreshToken();
				$grantedScope = $module->getScope();

				// This is only for this example...

				saveSession("access_token", $accessToken);
				saveSession("expiration", $accessTokenExpiration);
				saveSession("refresh_token", $refreshToken);
				saveSession("scope", implode(", ", $grantedScope));

				header("Location: " . getUrl(ACTION_GET_USER_DETAILS));
			}
		}
		
		elseif ($action == ACTION_GET_USER_DETAILS) {
			// Gets user's data
			
			$app = new App();
			$app->appId(APP_ID)
				->secretKey(SECRET_KEY);
			
			$module = new User($app);
			$module->setAccessToken(getSession("access_token"));
			
			$rawJSON = $module->execute(); // returns raw JSON string
			
			// This is only for this example...
			
			$resultData["json"] = $rawJSON;
			$resultData["array"] = print_r(json_decode($rawJSON, true), true);
			
			// You can also get indiviual fields via the following methods
			
			/*$user = $module->getUser(); // an entity of the user
			
			if ($user->check()) { // checks if you still have authorized access (because user may cancel their consent anytime)
				$user->id();
				$user->fullname();
				$user->firstname();
				$user->surname();
				$user->language();
				$user->country();
				$user->email();
				$user->thumbnailUrl();
				$thumbnail = $user->thumbnailEntity();
				
				if (!$thumbnail->isDefault()) {
					$thumbnail->url();
					$thumbnail->download();
					$thumbnail->width();
					$thumbnail->height();
				}
			}*/
		}
	}
	catch (\Exception $e) {
		$exception = print_r($e, true);
	}