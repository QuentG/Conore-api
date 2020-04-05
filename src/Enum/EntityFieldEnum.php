<?php

namespace App\Enum;

abstract class EntityFieldEnum
{
	// User
	const EMAIL_FIELD = 'email';
	const PASSWORD_FIELD = 'password';
	const USERNAME_FIELD = 'username';
	const FIRSTNAME_FIELD = 'firstname';
	const LASTNAME_FIELD = 'lastname';
	const FIRST_CONNECTION_FIELD = 'first_connection';
	const CLUB_ID_FIELD = 'club_id';

	// ApiToken
	const ACCESS_TOKEN_FIELD = 'accessToken';
	const REFRESH_TOKEN_FIELD = 'refreshToken';

	// Club
	const NAME_FIELD = 'name';
	const ADDRESS_FIELD = 'address';
	const CITY_FIELD = 'city';
	const ZIPCODE_FIELD = 'zip_code';
	const PHONE_FIELD = 'phone';

	// Session
	const EVENT_AT_FIELD = 'event_at';
	const PLACES_FIELD = 'places';
	const DURATION_FIELD = 'duration';

	// PushNotificationToken
	const TOKEN_FIELD = 'token';
	const PREVIOUS_TOKEN_FIELD = 'previous_token';

	// Stripe
	const CODE_FIELD = 'code';
	const PRODUCT_NAME_FIELD = 'product_name';
	const DESCRIPTION_FIELD = 'description';
	const PLAN_NAME_FIELD = 'plan_name';
	const AMOUNT_FIELD = 'amount';
	const INTERVAL_FIELD = 'interval';
	const PAYMENT_ID_FIELD = "payment_method_id";
}