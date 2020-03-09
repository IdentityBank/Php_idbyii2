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

use app\helpers\Translate;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbPasswordPolicy
 *
 * @package idbyii2\models\db
 */
abstract class IdbPasswordPolicy extends IdbModel
{

    const DEFAULT_PASSWORD_POLICY_NAME = 'default';

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'password_policy';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return
            [
                [['name'], 'required'],
                [
                    [
                        'lowercase',
                        'uppercase',
                        'digit',
                        'special',
                        'min_types',
                        'reuse_count',
                        'min_recovery_age',
                        'max_age',
                        'min_length',
                        'max_length',
                        'change_initial',
                        'level'
                    ],
                    'default',
                    'value' => null
                ],
                [
                    [
                        'lowercase',
                        'uppercase',
                        'digit',
                        'special',
                        'min_types',
                        'reuse_count',
                        'min_recovery_age',
                        'max_age',
                        'min_length',
                        'max_length',
                        'level'
                    ],
                    'integer'
                ],
                ['change_initial', 'boolean', 'trueValue' => true, 'falseValue' => false, 'strict' => false],
                [
                    [
                        'lowercase',
                        'uppercase',
                        'digit',
                        'special',
                        'min_types',
                        'reuse_count',
                        'min_recovery_age',
                        'max_age',
                        'min_length',
                        'max_length',
                        'level'
                    ],
                    'compare',
                    'compareValue' => 0,
                    'operator' => '>=',
                    'type' => 'number'
                ],
                ['level', 'compare', 'compareValue' => 1000, 'operator' => '<=', 'type' => 'number'],
                ['min_types', 'compare', 'compareValue' => 4, 'operator' => '<=', 'type' => 'number'],
                [['name', 'special_chars_set'], 'string', 'max' => 255],
                [['name'], 'unique'],
            ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'name' => Translate::_('idbyii2', 'Name'),
                'lowercase' => Translate::_('idbyii2', 'Lowercase'),
                'uppercase' => Translate::_('idbyii2', 'Uppercase'),
                'digit' => Translate::_('idbyii2', 'Digit'),
                'special' => Translate::_('idbyii2', 'Special'),
                'special_chars_set' => Translate::_('idbyii2', 'Special Characters'),
                'min_types' => Translate::_('idbyii2', 'Minimum number of different symbol types'),
                'reuse_count' => Translate::_('idbyii2', 'Reuse Count'),
                'min_recovery_age' => Translate::_('idbyii2', 'Minimum recovery age'),
                'max_age' => Translate::_('idbyii2', 'Max Age'),
                'min_length' => Translate::_('idbyii2', 'Minimum password length'),
                'max_length' => Translate::_('idbyii2', 'Max password length'),
                'change_initial' => Translate::_('idbyii2', 'Change initial'),
                'level' => Translate::_('idbyii2', 'Level'),
            ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return
            [
                'lowercase' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Number of lowercase letters required for the password.')
                ),
                'uppercase' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Number of uppercase letters required for the password.')
                ),
                'digit' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Number of digits required for the password.')
                ),
                'special' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Number of special characters required for the password.')
                ),
                'special_chars_set' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Set of special characters allowed to use for the password.')
                ),
                'min_types' => $this->attributeHintTemplate(
                    Translate::_(
                        'idbyii2',
                        'Minimum different symbol types required for the password. An empty value or "0" mean there is no required minimum. The maximum is 4 and this means use all types: uppercase, lowercase, digit, special character.'
                    )
                ),
                'reuse_count' => $this->attributeHintTemplate(
                    Translate::_(
                        'idbyii2',
                        'Number of passwords after which it can be used again. An empty value of "0" mean password can be reused.'
                    )
                ),
                'min_recovery_age' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Number of minutes after we can regenerate token to recover password.')
                ),
                'max_age' => $this->attributeHintTemplate(
                    Translate::_(
                        'idbyii2',
                        'The number of days after which the password expires. An empty value of "0" mean never expire.'
                    )
                ),
                'change_initial' => $this->attributeHintTemplate(
                    Translate::_('idbyii2', 'Force user to change the password after first login.')
                ),
                'level' => $this->attributeHintTemplate(
                    Translate::_(
                        'idbyii2',
                        'Security level for the password policy. That will be use to find the highest security level password policy applicable to the user by group. Min is 0, Max is 1000.'
                    )
                ),
            ];
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        $helpEnglish = Translate::_('idbyii2', 'IdbPasswordPolicy::getHelp', $this->getAttributes(), 'en-GB');
        $help = Translate::_('idbyii2', 'IdbPasswordPolicy::getHelp', $this->getAttributes());
        if (!empty($help) && ($help !== 'IdbPasswordPolicy::getHelp')) {
            return $help;
        }
        if (
            !empty($helpEnglish)
            && ($helpEnglish !== 'IdbPasswordPolicy::getHelp')
            && !empty($help)
            && ($help === 'IdbPasswordPolicy::getHelp')
        ) {
            return $helpEnglish;
        }

        $countTypes = 0;
        $text = [];
        $text[] = Translate::_('idbyii2', "Do not include your name or email address in the password.");

        $text[] = Translate::_(
            'idbyii2',
            "The password length must be between {min_length} and {max_length} characters.",
            $this->getAttributes()
        );
        if ($this->lowercase > 0) {
            $countTypes++;
            $text[] = Translate::_(
                'idbyii2',
                "Include at least {lowercase} lowercase letter(s).",
                $this->getAttributes()
            );
        }

        if ($this->uppercase > 0) {
            $countTypes++;
            $text[] = Translate::_(
                'idbyii2',
                "Include at least {uppercase} uppercase letter(s).",
                $this->getAttributes()
            );
        }

        if ($this->digit > 0) {
            $countTypes++;
            $text[] = Translate::_(
                'idbyii2',
                "Include at least {digit} digit(s).",
                $this->getAttributes()
            );
        }

        if ($this->special > 0) {
            $countTypes++;
            $text[] = Translate::_(
                'idbyii2',
                'Include at least {special} of the special characters: "{special_chars_set}".',
                $this->getAttributes()
            );
        }

        if (!empty($this->min_types) && ($this->min_types < $countTypes)) {
            $text[] = Translate::_(
                'idbyii2',
                'Password must have at least {min_types} out the {count_types} categories listed above.',
                ArrayHelper::merge($this->getAttributes(), ['count_types' => $countTypes])
            );
        }

        if (!is_null($this->reuse_count)) {
            $text[] = Translate::_(
                'idbyii2',
                "Your {reuse_count} recent passwords are remembered, you cannot reuse these.",
                $this->getAttributes()
            );
        }

        if (!empty($this->max_age)) {
            $text[] = Translate::_(
                'idbyii2',
                "Password must be changed at least every {max_age} days. Identity Bank recommends to do this at least once per month.",
                $this->getAttributes()
            );
        }

        $text[] = Translate::_(
            'idbyii2',
            "Identity Bank recommends the use of password managers to create and store very secure passwords.",
            $this->getAttributes()
        );

        $help = Translate::_('idbyii2', "Password requirements") . ':<br>';
        $help .= '<ul class="no-margin-top">';
        foreach ($text AS $bullet) {
            $help .= '<li>' . $bullet . '</li>';
        }
        $help .= '</ul>';

        return $help;
    }

    /**
     * @param string $passwordPolicyName
     *
     * @return |null
     */
    public static function getPasswordPolicyJSONByName(
        $passwordPolicyName = IdbPasswordPolicy::DEFAULT_PASSWORD_POLICY_NAME
    ) {
        $passwordPolicy = self::find()->where(['name' => $passwordPolicyName])->one();

        if ($passwordPolicy) {
            return json_encode($passwordPolicy->getAttributes());
        }

        return null;
    }

    /**
     * @param string $passwordPolicyName
     *
     * @return null
     */
    public static function getHelpByName($passwordPolicyName = IdbPasswordPolicy::DEFAULT_PASSWORD_POLICY_NAME)
    {
        $passwordPolicy = self::find()->where(['name' => $passwordPolicyName])->one();
        if ($passwordPolicy) {
            return $passwordPolicy->getHelp();
        }

        return null;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
