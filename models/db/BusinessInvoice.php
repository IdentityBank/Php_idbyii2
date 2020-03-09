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

namespace idbyii2\models\db;

################################################################################
# Use(s)                                                                       #
################################################################################

use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "p57b_business.invoices".
 *
 * @property int    $id
 * @property int    $payment_id
 * @property string $invoice_data
 * @property string $invoice_number
 * @property int    $amount
 * @property string $timestamp
 */
class BusinessInvoice extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_business.invoices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payment_id', 'amount'], 'default', 'value' => null],
            [['payment_id', 'amount'], 'integer'],
            [['invoice_data'], 'string'],
            [['invoice_number', 'amount'], 'required'],
            [['timestamp'], 'safe'],
            [['invoice_number'], 'string', 'max' => 255],
            [
                ['payment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => IdbPaymentLog::class,
                'targetAttribute' => ['payment_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'payment_id' => 'Payment ID',
            'invoice_data' => 'Invoice Data',
            'invoice_number' => 'Invoice Number',
            'amount' => 'Amount',
            'timestamp' => 'Timestamp',
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
