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

namespace idbyii2\models\data;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\IdbAccountId;
use idbyii2\models\db\IdbAuditLog;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * IdbAuditLogSearch represents the model behind the search form of `idbyii2\models\db\IdbAuditLog`.
 */
class IdbAuditLogSearch extends IdbAuditLog
{

    public $usedBy;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['timestamp', 'tag', 'idb_data', 'portal_uuid', 'usedBy', 'business_db_id', 'message'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public function search($params)
    {
        $user = Yii::$app->user->identity;
        $businessId = IdbAccountId::generateBusinessDbId($user->oid, $user->aid, $user->dbid);

        $query = IdbAuditLog::find()->where(['like', 'business_db_id', $businessId]);
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider(
            [
                'query' => $query,
            ]
        );

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $filtersBusinessIds = ['ilike', 'business_db_id', $this->business_db_id];
        if (!empty($params['IdbAuditLogSearch']['usedBy'])) {

            $users = new IdbBusinessUserSearchDataProvider();
            $users->init(['email' => $params['IdbAuditLogSearch']['usedBy']]);

            if (!empty($users->getModels())) {
                $filtersBusinessIds = ['or'];
                foreach ($users->getModels() as $model) {
                    $filtersBusinessIds [] = ['ilike', 'portal_uuid', $model->uid];
                }
            }

        }

        // grid filtering conditions
        $query->andFilterWhere(
            [
                'id' => $this->id,
                'timestamp' => $this->timestamp,
            ]
        );

        $query->andFilterWhere(['ilike', 'tag', $this->tag])
              ->andFilterWhere(['ilike', 'idb_data', $this->idb_data])
              ->andFilterWhere($filtersBusinessIds)
              ->andFilterWhere(['ilike', 'message', $this->message]);

        return $dataProvider;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
