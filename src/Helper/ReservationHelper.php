<?php

namespace App\Helper;

use App\Entity\Reservation;
use App\Entity\Session;
use App\Entity\User;

class ReservationHelper
{
    /**
     * @param Session $session
     * @param User $currentUser
     *
     * @return bool
     */
    public function hasAlreadyReservated(Session $session, User $currentUser)
    {
        foreach ($session->getReservations() as $reservation) {
            foreach ($reservation->getUsers() as $user) {
                if ($user->getEmail() == $currentUser->getEmail()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $reservations
     *
     * @return array
     */
    public function getSessionReservations($reservations)
    {
        $tabReservation = [];

        /** @var Reservation $reservation */
        foreach ($reservations as $reservation) {
            foreach ($reservation->getUsers() as $user) {
                $tabReservation[] = [
                    'id' => $reservation->getId(),
                    'created_at' => $reservation->getCreatedAt()->getTimestamp(),
                    'user' => [
                        'id' => $user->getId(),
                        'firstname' => $user->getFirstName(),
                        'lastname' => $user->getLastName()
                    ]
                ];
            }
        }

        return $tabReservation;
    }
}