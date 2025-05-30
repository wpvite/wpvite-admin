<?php

namespace App\Helpers;

use Faker\Factory as Faker;
use Illuminate\Support\Str;

class CustomHelper
{
    /**
     * Generate Username
     * @param int $maxLength
     * @param bool $randomSuffix
     * @return string
     */
    public static function generateRandomUsername(int $maxLength = 12, string $prefix = '')
    {
        $faker = Faker::create();
        $nb = round(max([$maxLength / 5, 1]), 0);

        do {
            $username = $prefix . ucfirst($faker->userName);
            $username .= ucwords($faker->words($nb, true));

            // Filter out non-alphanumeric characters
            $username = preg_replace('/[^a-zA-Z0-9]/', '', $username);

            // Truncate the username if it exceeds the max length
            if (strlen($username) > $maxLength) {
                $username = substr($username, 0, $maxLength);
            }
        } while (empty($username)); // Ensure a valid username is generated

        return $username;
    }

    /**
     * Generate a random secure password.
     *
     * @return string
     */
    public static function generateRandomPassword(int $length = 16): string
    {
        // Ensure minimum password length is 8
        if ($length < 8) {
            throw new \InvalidArgumentException('Password length must be at least 8 characters.');
        }

        // Define character sets
        $characterSets = [
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'numbers' => '0123456789',
            // 'special' => '!@#$%^&*()-_+=<>?'
            'special' => '!@#$&*-_+'
        ];

        // Step 1: Start with an alphabet (uppercase or lowercase)
        $alphabets = $characterSets['uppercase'] . $characterSets['lowercase'];
        $password = [$alphabets[random_int(0, strlen($alphabets) - 1)]];

        // Step 2: Ensure at least one character from each remaining set
        foreach (['numbers', 'special'] as $set) {
            $password[] = $characterSets[$set][random_int(0, strlen($characterSets[$set]) - 1)];
        }

        // Step 3: Combine all characters into one pool for random selection
        $allCharacters = implode('', $characterSets);

        // Step 4: Fill remaining characters to meet the desired length
        for ($i = count($password); $i < $length; $i++) {
            $password[] = $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        // Step 5: Shuffle the password array to randomize order (excluding the first character)
        $middle = array_slice($password, 1);
        shuffle($middle);

        // Combine the first character (alphabet) with the shuffled middle
        return $password[0] . implode('', $middle);
    }

    /**
     * Summary of isSiteHttpsWorking
     * @param string $domain
     * @return bool
     */
    public static function isSiteHttpsWorking(string $domain): bool
    {
        $url = "https://{$domain}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true, // no content, just headers
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, // verify SSL
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $sslError = curl_errno($ch);
        curl_close($ch);

        // HTTP 200 or 301/302 + no SSL error => SSL is working
        return in_array($httpCode, [200, 301, 302]) && $sslError === 0;
    }
}
