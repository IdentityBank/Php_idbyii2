<?php
# * ********************************************************************* *
# *                                                                       *
# *   Yii2 Models and Modules                                             *
# *   This file is part of idbyii2. This project may be found at:         *
# *   https://github.com/IdentityBank/Php_idbyii2.                        *
# *                                                                       *
# *   Copyright (C) 2020 by Identity Bank. All Rights Reserved.           *
# *   https://www.identitybank.eu - You belong to you                     *
# *                                                                       *
# *   This program is free software: you can redistribute it and/or       *
# *   modify it under the terms of the GNU Affero General Public          *
# *   License as published by the Free Software Foundation, either        *
# *   version 3 of the License, or (at your option) any later version.    *
# *                                                                       *
# *   This program is distributed in the hope that it will be useful,     *
# *   but WITHOUT ANY WARRANTY; without even the implied warranty of      *
# *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the        *
# *   GNU Affero General Public License for more details.                 *
# *                                                                       *
# *   You should have received a copy of the GNU Affero General Public    *
# *   License along with this program. If not, see                        *
# *   https://www.gnu.org/licenses/.                                      *
# *                                                                       *
# * ********************************************************************* *

################################################################################
# Namespace                                                                    #
################################################################################

namespace idbyii2\helpers;

################################################################################
# Use(s)                                                                       #
################################################################################

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbAccountNumber
 *
 * @package idbyii2\helpers
 */
class IdbAccountNumber
{

    const length = 24;
    const destinationPosition = 20;
    const AccountNumberFormatRegex = '/(\d{4})-([A-Z]{4})-(\d{4})-([A-Z]{4})-(\d{4})/';
    private $accountNumber;

    /**
     * IdbAccountNumber constructor.
     *
     * @param string $accountNumber
     */
    public function __construct(string $accountNumber)
    {
        $this->accountNumber = strtoupper($accountNumber);
    }

    /**
     * @return string
     */
    private static function generateChar()
    {
        $vowels = [ord('A'), ord('E'), ord('I'), ord('O'), ord('U'), ord('Y')];
        do {
            $value = ((rand(65, 90) + mt_rand(65, 90)) / 2);
        } while (in_array($value, $vowels));

        return chr($value);
    }

    /**
     * @param \idbyii2\helpers\IdbAccountNumberDestination $destination
     *
     * @return \idbyii2\helpers\IdbAccountNumber|string
     */
    public static function generate(IdbAccountNumberDestination $destination)
    {
        $random_number = intval(
            "0" . mt_rand(1, 9) . rand(1, 9) . mt_rand(1, 9) . rand(1, 9) . mt_rand(1, 9) . rand(1, 9) . mt_rand(1, 9)
            . rand(1, 9)
        );
        $random_number = strval($random_number);
        $random_string = self::generateChar() . self::generateChar() . self::generateChar() . self::generateChar()
            . self::generateChar() . self::generateChar() . self::generateChar() . self::generateChar();

        // Account number - Format:
        // Easy to copy type without errors, e.g. 1234-BCDF-1234-GHJK-CKSM
        // Exclude the number ‘0’ and all vowels
        // CKSM = checksum base 9

        return self::customAccountNumber(
            substr($random_number, 0, 4) . '-' . substr($random_string, 0, 4) . '-' . substr($random_number, 4, 4) . '-'
            . substr($random_string, 4, 4),
            $destination
        );
    }

    /**
     * @param                                              $accountId
     * @param \idbyii2\helpers\IdbAccountNumberDestination $destination
     *
     * @return \idbyii2\helpers\IdbAccountNumber|string
     */
    public static function customAccountNumber($accountId, IdbAccountNumberDestination $destination)
    {
        $idbAccountNumber = $accountId . '-' . $destination->toId();
        $checksum = self::calculateChecksum($idbAccountNumber);
        $idbAccountNumber .= $checksum;
        $idbAccountNumber = strtoupper($idbAccountNumber);
        $idbAccountNumber = new IdbAccountNumber($idbAccountNumber);

        return $idbAccountNumber;
    }

    /**
     * @param $accountNumber
     *
     * @return int|number|string
     */
    private static function calculateChecksum($accountNumber)
    {
        $checksum = abs(crc32($accountNumber));
        if ($checksum & 0x80000000) {
            $checksum ^= 0xffffffff;
            $checksum += 1;
        }
        $checksum &= 999;
        $checksum = str_pad($checksum, 3, "0");
        $checksum = base_convert($checksum, 10, 9);
        $checksum = strval($checksum);
        $checksum = (intval($checksum[2]) + 1) . (intval($checksum[1]) + 1) . (intval($checksum[0]) + 1);

        return $checksum;
    }

    /**
     * @return bool
     */
    private function isFormatValid()
    {
        return (preg_match(self::AccountNumberFormatRegex, $this->accountNumber, $matches, PREG_OFFSET_CAPTURE) == 1);
    }

    /**
     * @return bool
     */
    private function isChecksumValid()
    {
        if ($this->isFormatValid()) {
            if (preg_match(self::AccountNumberFormatRegex, $this->accountNumber, $matches, PREG_OFFSET_CAPTURE) == 1) {
                $idbAccountNumber = $matches[1][0] . '-' . $matches[2][0] . '-' . $matches[3][0] . '-' . $matches[4][0]
                    . '-' . substr($matches[5][0], 0, 1);
                $checksum = self::calculateChecksum($idbAccountNumber);

                return ($checksum === substr($matches[5][0], 1, 3));
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isChecksumValid();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->accountNumber;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * @return \idbyii2\helpers\IdbAccountNumberDestination|null
     */
    public function getDestination()
    {
        $destination = null;
        if ($this->isValid()) {
            $destinationId = intval($this->accountNumber[IdbAccountNumber::destinationPosition]);
            $destination = IdbAccountNumberDestination::fromId($destinationId);
        }

        return $destination;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
