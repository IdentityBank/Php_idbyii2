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

namespace idbyii2\services;

################################################################################
# Use(s)                                                                       #
################################################################################

use DateTime;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessAccountUser;
use idbyii2\models\db\BusinessOrganization;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\idb\BusinessIdbBillingClient;
use xmz\simplelog\SNLog as Log;
use yii\helpers\ArrayHelper;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

class Invoice
{
    const PAGE_SIZE = 20;

    /**
     * @param $oid
     *
     * @return array
     */
    public static function getBillingDataForOrganization($oid)
    {
        $organization = BusinessOrganization::find()->where(['oid' => $oid])->one();
        $account = BusinessAccount::find()->asArray()->select('aid')->where(['oid' => $oid])->one();
        $accountUser = BusinessAccountUser::find()->asArray()->select('uid')->where(['aid' => $account['aid']])->one();

        $firstName = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingFirstName'])[0];
        $lastName = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingLastName'])[0];
        $address1 = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingAddressLine1'])[0];
        $address2 = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingAddressLine2'])[0];
        $city = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingCity'])[0];
        $postCode = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingPostcode'])[0];
        $country = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingCountry'])[0];
        $vatNumber = BusinessUserData::getUserDataByKeys($accountUser['uid'], ['billingVat'])[0];


        return [
            'businessName' => $organization->name,
            'contactName' => $firstName->value . ' '
                . $lastName->value,
            'address' => $address1->value . ' | '
                . $address2->value . ' | '
                . $city->value . ' | '
                . $postCode->value . ' | '
                . $country->value,
            'vatNumber' => $vatNumber->value
        ];
    }

    /**
     * @throws \Exception
     */
    public static function logOutDatedInvoices()
    {
        try {
            $exp = [
                'o' => '<',
                'l' => '#col',
                'r' => ':col'
            ];

            for ($i = 0; $i != -1; $i++) {
                $invoices = BusinessIdbBillingClient::model()->setPagination($i, self::PAGE_SIZE)->findInvoices(
                    $exp,
                    ['#col' => 'timestamp'],
                    [':col' => Localization::getDatabaseDateTime((new \DateTime())->sub(new \DateInterval('P24M')))]
                );

                foreach ($invoices as $invoice) {
                    self::backupInvoice(json_encode($invoice));
                }

                if (empty($invoices) || count($invoices) < 20) {
                    break;
                }
            }
        } catch(\Exception $e) {
            Yii::error('LOG OUTDATED INVOICES ERROR');
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param $log
     *
     * @param $billingData
     *
     * @throws \Exception
     */
    public function createInvoice($log, $billingData)
    {
        $data = json_decode($log['payment_data'], true);
        BusinessIdbBillingClient::model()->createInvoice(
            [
                'payment_id' => $log['id'],
                'invoice_number' => self::getNextInvoiceNumber(),
                'amount' => $log['amount'],
                'invoice_data' => json_encode(
                    array_merge(
                        [
                            'items' => [
                                'name' => ArrayHelper::getValue(
                                    $data,
                                    'package',
                                    Translate::_('idbyii2', 'Subscription')
                                ),
                                'value' => $log['amount'],
                            ]
                        ],
                        $billingData
                    )
                )
            ]
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getNextInvoiceNumber()
    {
        $lastInvoice = BusinessIdbBillingClient::model()->getLastInvoiceNumber();
        $number = '0001';
        $year = (new DateTime())->format('Y');
        $separator = '.';
        if (!empty($lastInvoice)) {
            $lastInvoice = $lastInvoice[0][0];
            $separator = substr($lastInvoice, 4, 1);
            $lastYear = substr($lastInvoice, 0, 4);
            if ($lastYear === $year) {
                $lastNumber = substr($lastInvoice, 5, 4);
                if (intval($lastNumber) < 9999) {
                    $number = str_pad(intval($lastNumber) + 1, 4, '0', STR_PAD_LEFT);
                } elseif (ord($separator) === 46) {
                    $separator = chr(65);
                } elseif (ord($separator) === 90) {
                    $separator = chr(97);
                } elseif (ord($separator) === 122) {
                    $separator = chr(45);
                } else {
                    $separator = chr(ord($separator) + 1);
                }
            }
        }

        return $year . $separator . $number;
    }

    /**
     * @param $message
     */
    private static function backupInvoice($message)
    {
        $logName = "p57b.idb_business-invoice-backup";
        $logPath = "/var/log/p57b/$logName.log";
        registerLogger($logName, $logPath);

        $pid = getmypid();
        Log::debug(
            $logName,
            "$pid - " .
            $message
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
