<?php

final class User
{
    public static function getById(int $userId): ?array {
        $sql = "SELECT user_id, username, email, status,
                       billing_full_name, billing_address1, billing_address2,
                       billing_city, billing_postal_code, billing_country
                  FROM users
                 WHERE user_id = :id
                 LIMIT 1";
        $st = db()->prepare($sql);
        $st->execute([':id' => $userId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function updateProfile(int $userId, array $data): void {
        $sql = "UPDATE users
                   SET username = :username,
                       email = :email,
                       billing_full_name   = :billing_full_name,
                       billing_address1    = :billing_address1,
                       billing_address2    = :billing_address2,
                       billing_city        = :billing_city,
                       billing_postal_code = :billing_postal_code,
                       billing_country     = :billing_country
                 WHERE user_id = :id";
        $st = db()->prepare($sql);
        $st->execute([
            ':username'            => $data['username'],
            ':email'               => $data['email'],
            ':billing_full_name'   => $data['billing_full_name'],
            ':billing_address1'    => $data['billing_address1'],
            ':billing_address2'    => $data['billing_address2'],
            ':billing_city'        => $data['billing_city'],
            ':billing_postal_code' => $data['billing_postal_code'],
            ':billing_country'     => $data['billing_country'],
            ':id'                  => $userId,
        ]);
    }
}
