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

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Service\Checkout;
use Adyen\Service\CheckoutUtility;
use app\helpers\BusinessConfig;
use DateInterval;
use DateTime;
use Exception;
use idbyii2\enums\NotificationTopic;
use idbyii2\enums\NotificationType;
use idbyii2\enums\PaymentResultCode;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessAccountUser;
use idbyii2\models\db\BusinessNotification;
use idbyii2\models\db\BusinessOrganization;
use idbyii2\models\db\BusinessSignup;
use idbyii2\models\idb\BusinessIdbBillingClient;
use Throwable;
use xmz\simplelog\SNLog as Log;
use Yii;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

class Payment
{

    const BUSINESS_PACKAGE_ID_INDEX = 1;

    private $apiKey;
    private $environment;
    private $merchant;
    private $currency;
    private $invoiceService;

    private $client;
    private $service;
    private $model;

    /**
     * Payment constructor.
     */
    public function __construct()
    {
        $this->apiKey = BusinessConfig::get()->getPaymentApiKey();
        $this->environment = BusinessConfig::get()->getPaymentEnvironment();
        $this->merchant = BusinessConfig::get()->getPaymentMerchant();
        $this->currency = BusinessConfig::get()->getPaymentCurrency();

        $this->client = new Client();
        $this->client->setEnvironment($this->environment);
        $this->client->setXApiKey($this->apiKey);

        $this->invoiceService = new Invoice();
        $this->service = new Checkout($this->client);

        $this->model = BusinessIdbBillingClient::model();
    }

    /**
     * Notify users about outdated payment.
     */
    public static function rechargeOrNotifyOutdated()
    {
        $outdated = BusinessIdbBillingClient::model()->getOutdatedBusinessPackages();
        foreach ($outdated as $businessPackage) {
            self::rechargeOrNotifyOrganization(
                ArrayHelper::getValue($businessPackage, self::BUSINESS_PACKAGE_ID_INDEX, ''),
                $businessPackage
            );
        }
    }

    /**
     * @param      $oid
     * @param      $businessPackage
     *
     * @return bool
     */
    public static function rechargeOrNotifyOrganization($oid, $businessPackage)
    {
        $payments = new self();

        $organization = BusinessOrganization::find()->where(
            ['oid' => $oid]
        )->one();

        if (!empty($organization)) {
            if ($payments->recharge($organization->oid, $businessPackage)) {
                self::deletePaymentNotification($organization->oid);

                return true;
            } else {
                self::notifyOutdated($organization->oid);
            }
        } else {
            $id = ArrayHelper::getValue($businessPackage, '0', "ID MISSING");
            self::logPaymentError("MISSING ORGANIZATION FOR BUSINESS PACKAGE WITH ID: $id");
        }

        return false;
    }

    /**
     * @param      $oid
     * @param      $businessPackage
     *
     * @param bool $updateBusinessPackage
     *
     * @return bool
     */
    public function recharge($oid, $businessPackage, $updateBusinessPackage = true)
    {
        try {
            /** @var BusinessOrganization $organization */
            $organization = BusinessOrganization::find()->where(['oid' => $oid])->one();
            $package = $this->model->getPackage(ArrayHelper::getValue($businessPackage, '2'));

            if (empty($organization)) {
                return false;
            }
            $params = json_decode($organization->payment_token, true);

            if (empty($params)) {
                return false;
            }

            $params = $this->makePayment(
                ArrayHelper::getValue($package, '0.5', 0),
                Invoice::getNextInvoiceNumber(),
                $organization,
                $params
            );

            if (empty($params)) {
                return false;
            }
            list($params, $response) = $params;

            if ($this->checkStatus($response)) {
                list($startDate, $endDate) = $this->getStartAndEndDate($package[0][3]);

                $log = $this->logPayment(
                    [
                        'organization' => $organization->oid,
                        'params' => $params,
                        'status' => $response['resultCode'],
                        'pspReference' => $response['pspReference'],
                        'package' => ArrayHelper::getValue(
                            $package,
                            '0.4',
                            Translate::_('idbyii2', 'Subscription')
                        )
                    ]
                );

                $this->invoiceService->createInvoice(
                    $log,
                    array_merge(
                        [
                            'startDate' => $startDate,
                            'endDate' => $endDate
                        ],
                        Invoice::getBillingDataForOrganization($organization->oid)
                    )
                );

                if ($updateBusinessPackage) {
                    $this->model->updateBusinessPackage(
                        ArrayHelper::getValue($businessPackage, '0'),
                        [
                            'last_payment' => $startDate,
                            'end_date' => $endDate,
                            'next_payment' => $endDate
                        ]
                    );
                }

                return true;
            }
        } catch (Exception $e) {
            Yii::error('RECHARGE ERROR');
            Yii::error($e->getMessage());
        }

        return false;
    }

    /**
     * @param $value
     * @param $reference
     * @param $organization
     * @param $data
     *
     * @return array|null
     * @throws AdyenException
     */
    private function makePayment($value, $reference, BusinessOrganization &$organization, $data)
    {
        $state = $data;
        if (!empty($data['paymentState'])) {
            $state = json_decode($data['paymentState'], true);
            $state["storeDetails"] = true;
        }
        $params = [
            'amount' => [
                'currency' => $this->currency,
                'value' => $value
            ],
            'reference' => $reference,
            "shopperReference" => $organization->name,
            'paymentMethod' => $state,
            'merchantAccount' => $this->merchant
        ];

        $response = $this->service->payments($params);

        if (!empty($response['additionalData'])) {
            if (
                !empty($response['additionalData']['recurring.recurringDetailReference'])
                || !empty($response['additionalData']['sepadirectdebit.mandateId'])
            ) {
                $organization->payment_token = json_encode($state);
                $organization->save();
            }

            return [$params, $response];
        } else {
            return null;
        }
    }

    /**
     * @param $response
     *
     * @return bool
     */
    private function checkStatus($response)
    {
        return $response['resultCode'] == PaymentResultCode::AUTHORISED
            || $response['resultCode'] == PaymentResultCode::RECEIVED;
    }

    /**
     * @param $period
     *
     * @return array
     * @throws Exception
     */
    private function getStartAndEndDate($period)
    {
        $startDate = Localization::getDatabaseDateTime(new DateTime());
        $endDate = Localization::getDatabaseDateTime((new DateTime())->add(new DateInterval('P' . $period . 'D')));

        return [$startDate, $endDate];
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function logPayment($data)
    {
        $log = [
            'oid' => $data['organization'],
            'amount' => $data['params']['amount']['value'],
            'status' => $data['status'],
            'psp_reference' => $data['pspReference'],
            'payment_data' => json_encode(
                [
                    'type' => $data['params']['paymentMethod']['type'],
                    'package' => $data['package']
                ]
            )
        ];
        $response = $this->model->logPayment($log);

        $log['id'] = $response[0][0];

        return $log;
    }

    /**
     * @param $oid
     */
    public static function deletePaymentNotification($oid)
    {
        try {
            $accounts = BusinessAccount::find()->asArray()->select('aid')->where(['oid' => $oid])->all();

            foreach ($accounts as $account) {
                $accountUser = BusinessAccountUser::find()->asArray()->select('uid')
                    ->where(['aid' => $account['aid']])
                    ->one();
                if (
                BusinessNotification::find()->where(
                    ['topic' => NotificationTopic::PAYMENT, 'uid' => $accountUser['uid']]
                )->exists()
                ) {
                    BusinessNotification::deleteAll(
                        ['topic' => NotificationTopic::PAYMENT, 'uid' => $accountUser['uid']]
                    );
                }
            }
        } catch (Exception $e) {
            Yii::error('PAYMENT NOTIFICATION ERROR');
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param $oid
     */
    public static function notifyOutdated($oid)
    {
        try {
            $accounts = BusinessAccount::find()->asArray()->select('aid')->where(['oid' => $oid])->all();

            foreach ($accounts as $account) {
                $accountUser = BusinessAccountUser::find()->asArray()->select('uid')
                    ->where(['aid' => $account['aid']])
                    ->one();
                if (
                !BusinessNotification::find()->where(
                    ['topic' => NotificationTopic::PAYMENT, 'uid' => $accountUser['uid']]
                )->exists()
                ) {
                    $tmp = new BusinessNotification();
                    $tmp->uid = $accountUser['uid'];
                    $tmp->type = NotificationType::RED;
                    $tmp->topic = NotificationTopic::PAYMENT;
                    $tmp->data = json_encode(
                        [
                            'title' => Translate::_('idbyii2', 'Payment problem.'),
                            'body' => Translate::_(
                                'idbyii2',
                                'We can\'t renew your subscription, please update your payment details'
                            ),
                            'url' => '/billing/billing-information',
                            'action_name' => Translate::_('idbyii2', 'go to billing')
                        ]
                    );
                    $tmp->save();
                }
            }
        } catch (Exception $e) {
            Yii::error('PAYMENT NOTIFICATION ERROR');
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param $message
     */
    private static function logPaymentError($message)
    {
        $logName = "p57b.idb_business-payment-errors";
        $logPath = "/var/log/p57b/$logName.log";
        registerLogger($logName, $logPath);

        $pid = getmypid();
        Log::debug(
            $logName,
            "$pid - " .
            $message
        );
    }

    /**
     * @return mixed
     * @throws AdyenException
     * @throws NotFoundHttpException
     */
    public function getOriginKey()
    {
        $checkoutUtility = new CheckoutUtility($this->client);
        $parsed_url = parse_url(Url::base('https'));
        $url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

        $response = $checkoutUtility->originKeys(
            [
                'originDomains' => [$url]
            ]
        );

        if (!empty($response['originKeys'][$url])) {
            if (strpos($response['originKeys'][$url], 'pub.') !== false) {
                return $response['originKeys'][$url];
            }
        }

        throw new NotFoundHttpException(403);
    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws AdyenException
     */
    public function paymentCheck($data)
    {
        if (ArrayHelper::getValue($data, 'paymentState', false)) {
            $state = json_decode($data['paymentState'], true);
            $params = [
                'amount' => [
                    'currency' => $data['currency'] ?? $this->currency,
                    'value' => intval($data['value']) * 100 ?? 100
                ],
                'reference' => Invoice::getNextInvoiceNumber(),
                "shopperReference" => 'test',
                'paymentMethod' => $state,
                'merchantAccount' => $this->merchant
            ];

            return $this->service->payments($params);
        }

        return false;
    }

    /**
     * @param $data
     *
     * @return bool
     * @throws AdyenException
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function changePaymentMethod($data)
    {
        $organization = BusinessOrganization::find()->where(['oid' => Yii::$app->user->identity->oid])->one();

        $paymentNotification = BusinessNotification::find()->where(
            [
                'uid' => Yii::$app->user->identity->id,
                'topic' => NotificationTopic::PAYMENT
            ]
        )->one();

        $businessPackage = null;
        $package = null;
        $reference = Translate::_('idbyii2', 'Check payment method IDBank');
        $value = 0;
        if (!empty($paymentNotification)) {
            $businessPackage = $this->model->getBusinessPackage(Yii::$app->user->identity->oid);
            $package = $this->model->getPackage(ArrayHelper::getValue($businessPackage, '0.2'));
            $reference = Invoice::getNextInvoiceNumber();
            $value = ArrayHelper::getValue($package, '0.5');
        }

        $params = $this->makePayment($value, $reference, $organization, $data);
        if (empty($params)) {
            return false;
        }
        list($params, $response) = $params;

        if (!empty($response['resultCode'])) {
            $log = $this->logPayment(
                [
                    'organization' => $organization->oid,
                    'params' => $params,
                    'status' => $response['resultCode'],
                    'pspReference' => $response['pspReference'],
                    'package' => ArrayHelper::getValue(
                        $package,
                        '0.4',
                        Translate::_('idbyii2', 'Subscription')
                    )
                ]
            );

            if (
                !empty($paymentNotification)
                && $this->checkStatus($response)
            ) {
                list($startDate, $endDate) = $this->getStartAndEndDate($package[0][3]);
                $this->invoiceService->createInvoice(
                    $log,
                    array_merge(
                        [
                            'startDate' => $startDate,
                            'endDate' => $endDate
                        ],
                        Invoice::getBillingDataForOrganization($organization->oid)
                    )
                );
                $paymentNotification->delete();
                $this->model->updateBusinessPackage(
                    ArrayHelper::getValue($businessPackage, '0.0'),
                    [
                        'last_payment' => $startDate,
                        'end_date' => $endDate,
                        'next_payment' => $endDate
                    ]
                );

                return true;
            } elseif ($this->checkStatus($response)) {

                return true;
            }
        }

        return false;
    }

    /**
     * @param BusinessSignup $signupModel
     * @param                $data
     *
     * @return bool
     * @throws Exception
     */
    public function makeFirstPayment(BusinessSignup $signupModel, $data)
    {
        $organization = BusinessOrganization::find()->where(['oid' => $signupModel->getDataChunk('oid')])->one();
        $package = $this->model->getPackage($signupModel->getDataChunk('package'));

        $params = $this->makePayment(
            intval($package[0][5]) * 100,
            Invoice::getNextInvoiceNumber(),
            $organization,
            $data
        );
        if (empty($params)) {
            return false;
        }

        list($params, $response) = $params;

        if (!empty($response['resultCode'])) {
            $log = $this->logPayment(
                [
                    'organization' => $organization->oid,
                    'params' => $params,
                    'status' => $response['resultCode'],
                    'pspReference' => $response['pspReference'],
                    'package' => ArrayHelper::getValue(
                        $package,
                        '0.4',
                        Translate::_('idbyii2', 'Subscription')
                    )
                ]
            );

            if ($this->checkStatus($response)) {
                $this->model->addBusiness(
                    [
                        'bid' => $signupModel->getDataChunk('uid'),
                        'data' => '',
                        'credits' => $package[0][2]
                    ]
                );

                list($startDate, $endDate) = $this->getStartAndEndDate($package[0][3]);

                $this->model->assignPackageToBusiness(
                    [
                        'business_id' => $signupModel->getDataChunk('oid'),
                        'package_id' => intval($signupModel->getDataChunk('package')),
                        'payment_log_id' => $log['id'],
                        'credits' => $package[0][2],
                        'base_credits' => $package[0][2],
                        'additional_credits' => 0,
                        'duration' => $package[0][3],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'last_payment' => $startDate,
                        'next_payment' => $endDate
                    ]
                );

                try {
                    $this->invoiceService->createInvoice(
                        $log,
                        [
                            'contactName' => $signupModel->getDataChunk('billingFirstName') . ' '
                                . $signupModel->getDataChunk('billingLastName'),
                            'businessName' => $signupModel->getDataChunk('billingName'),
                            'address' => $signupModel->getDataChunk('billingAddressLine1') . ' | '
                                . $signupModel->getDataChunk('billingAddressLine2') . ' | '
                                . $signupModel->getDataChunk('billingCity') . ' | '
                                . $signupModel->getDataChunk('billingPostcode') . ' | '
                                . $signupModel->getDataChunk('billingCountry'),
                            'vatNumber' => $signupModel->getDataChunk('billingVat'),
                            'startDate' => $startDate,
                            'endDate' => $endDate
                        ]
                    );
                } catch (Exception $e) {

                    var_dump($e->getMessage());
                    exit();
                }

                return true;
            }
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
