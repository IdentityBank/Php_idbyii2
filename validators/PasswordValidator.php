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

namespace idbyii2\validators;

################################################################################
# Use(s)                                                                       #
################################################################################

use app\helpers\Translate;
use idbyii2\models\db\BusinessPasswordPolicy;
use yii\validators\Validator;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class PasswordValidator
 *
 * @package idbyii2\validators
 */
class PasswordValidator extends Validator
{

    public $passwordPolicyName = BusinessPasswordPolicy::DEFAULT_PASSWORD_POLICY_NAME;

    /**
     * @param $model
     * @param $attribute
     *
     * @return void
     */
    public function validateAttribute($model, $attribute)
    {
        $passwordPolicy = BusinessPasswordPolicy::find()->where(['name' => $this->passwordPolicyName])->one();
        if ($passwordPolicy) {
            $pass = $model->{$attribute};
            $pass_length = strlen($pass);
            $type = 0;
            $errors = [];

            preg_match_all("/[a-z]/", $pass, $caps_match_lower);
            $caps_count_lower = count($caps_match_lower[0]);
            if ($caps_count_lower < $passwordPolicy->lowercase) {
                $errors['lowercase'][] = Translate::_(
                    'idbyii2',
                    'The password has too few lowercase letters. The minimum is {lowercase}',
                    $passwordPolicy->attributes
                );
            } else {
                $type++;
            }

            preg_match_all("/[A-Z]/", $pass, $caps_match_upper);
            $caps_count_upper = count($caps_match_upper[0]);
            if ($caps_count_upper < $passwordPolicy->uppercase) {
                $errors['uppercase'][] = Translate::_(
                    'idbyii2',
                    'The password has not enough uppercase letters. The minimum is {uppercase}',
                    $passwordPolicy->attributes
                );
            } else {
                $type++;
            }

            preg_match_all("/[0-9]/", $pass, $caps_match_digit);
            $caps_count_digit = count($caps_match_digit[0]);
            if ($caps_count_digit < $passwordPolicy->digit) {
                $errors['digit'][] = Translate::_(
                    'idbyii2',
                    'The password has too few digits. The minimum is {digit}',
                    $passwordPolicy->attributes
                );
            } else {
                $type++;
            }

            preg_match_all("/[" . preg_quote($passwordPolicy->special_chars_set) . "]/", $pass, $caps_match_special);
            $caps_count_special = count($caps_match_special[0]);
            if ($caps_count_special < $passwordPolicy->special) {
                $errors['special_chars_set'][] = Translate::_(
                    'idbyii2',
                    'The password contains too few special characters. you can use: {special_chars_set}',
                    $passwordPolicy->attributes
                );
            } else {
                $type++;
            }

            if ($type < $passwordPolicy->min_types) {
                $errors['min_types'][] = Translate::_(
                    'idbyii2',
                    'The password  contains too few required types of characters. the minimum is {min_types} ',
                    $passwordPolicy->attributes
                );
            } else {
                $errors = [];
            }

            if ($pass_length < $passwordPolicy->min_length) {
                $errors['min_length'][] = Translate::_(
                    'idbyii2',
                    'The password is too short. The minimum is {min_length}',
                    $passwordPolicy->attributes
                );
            }

            if ($pass_length > $passwordPolicy->max_length) {
                $errors['max_length'][] = Translate::_(
                    'idbyii2',
                    'The password is too long. The maximum is {max_length}',
                    $passwordPolicy->attributes
                );
            }

            if (0 < count($errors)) {
                $errors[$attribute][] = Translate::_('idbyii2', 'The password does not meet the requirements.');
            }
        } else {
            $errors[$attribute][] = Translate::_('idbyii2', 'The Password Policy does not exist');
        }
        $model->addErrors($errors);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
